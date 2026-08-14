from fastapi import FastAPI, Depends, HTTPException, status, Query
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy.orm import Session
from typing import List, Optional
from decimal import Decimal
import os

from . import models, schemas, crud, auth_utils, database
from .services.booking_facade import BookingFacade
from .services.notification_service import notifier_publisher

app = FastAPI(title="SAFAR API Backend Service", version="2.0")

# Enable CORS for frontend and gateway
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Auto create database schema in development (if not exist)
@app.on_event("startup")
def startup_event():
    models.Base.metadata.create_all(bind=database.session_manager.engine)

# =========================================================================
# Auth Router
# =========================================================================
@app.post("/api/auth/register", response_model=schemas.UserOut)
def register(user: schemas.UserCreate, db: Session = Depends(database.get_db)):
    db_user = crud.get_user_by_email(db, user.email)
    if db_user:
        raise HTTPException(status_code=400, detail="Email already registered")
    
    hashed_pwd = auth_utils.get_password_hash(user.password)
    user.password = hashed_pwd
    
    created_user = crud.create_user(db, user)
    
    # If signing up as an agency, register details
    if user.role == "agency":
        if not user.company_name:
            raise HTTPException(status_code=400, detail="Company name is required for agency registration")
        agency_data = schemas.AgencyCreate(
            user_id=created_user.id,
            company_name=user.company_name,
            phone=user.phone,
            status="pending"
        )
        crud.create_agency(db, agency_data)
        
    return created_user

@app.post("/api/auth/login", response_model=schemas.Token)
def login(login_data: schemas.UserLogin, db: Session = Depends(database.get_db)):
    user = crud.get_user_by_email(db, login_data.email)
    if not user or not auth_utils.verify_password(login_data.password, user.password):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Incorrect email or password",
            headers={"WWW-Authenticate": "Bearer"},
        )
    
    # Check if user is agency and pending
    if user.role == "agency":
        agency = crud.get_agency_by_user_id(db, user.id)
        if agency and agency.status == "pending":
            raise HTTPException(status_code=403, detail="Your agency account is pending approval by administrators.")
        elif agency and agency.status == "rejected":
            raise HTTPException(status_code=403, detail="Your agency registration request was rejected.")

    access_token = auth_utils.create_access_token(
        data={"user_id": user.id, "role": user.role}
    )
    return {"access_token": access_token, "token_type": "bearer"}

@app.get("/api/auth/profile", response_model=schemas.UserOut)
def get_profile(current_user: models.User = Depends(auth_utils.get_current_user)):
    return current_user

@app.put("/api/auth/profile", response_model=schemas.UserOut)
def update_profile(
    user_data: schemas.UserUpdate, 
    current_user: models.User = Depends(auth_utils.get_current_user),
    db: Session = Depends(database.get_db)
):
    if user_data.password:
        user_data.password = auth_utils.get_password_hash(user_data.password)
    return crud.update_user(db, current_user.id, user_data)

# =========================================================================
# Admin Panel Agency Moderation Router
# =========================================================================
@app.get("/api/agencies", response_model=List[schemas.AgencyOut])
def list_agencies(
    current_user: models.User = Depends(auth_utils.require_role(["admin"])),
    db: Session = Depends(database.get_db)
):
    return crud.get_all_agencies(db)

@app.put("/api/agencies/{agency_id}/status", response_model=schemas.AgencyOut)
def modify_agency_status(
    agency_id: int,
    status_update: schemas.AgencyUpdate,
    current_user: models.User = Depends(auth_utils.require_role(["admin"])),
    db: Session = Depends(database.get_db)
):
    if status_update.status not in ["pending", "verified", "rejected"]:
        raise HTTPException(status_code=400, detail="Invalid status value")
    updated = crud.update_agency_status(db, agency_id, status_update.status)
    if not updated:
        raise HTTPException(status_code=404, detail="Agency not found")
    return updated

# =========================================================================
# Packages Router (Using Factory Method)
# =========================================================================
@app.get("/api/packages", response_model=List[schemas.PackageOut])
def get_all_packages(
    type: Optional[str] = Query(None),
    location: Optional[str] = Query(None),
    price_max: Optional[float] = Query(None),
    db: Session = Depends(database.get_db)
):
    packages = crud.get_packages(db, type, location, price_max)
    # Map model responses and inject company name
    result = []
    for pkg in packages:
        out = schemas.PackageOut.model_validate(pkg)
        out.company_name = pkg.agency.company_name if pkg.agency else "SAFAR"
        result.append(out)
    return result

@app.get("/api/packages/{package_id}", response_model=schemas.PackageOut)
def get_single_package(package_id: int, db: Session = Depends(database.get_db)):
    pkg = crud.get_package(db, package_id)
    if not pkg:
        raise HTTPException(status_code=404, detail="Package not found")
    out = schemas.PackageOut.model_validate(pkg)
    out.company_name = pkg.agency.company_name if pkg.agency else "SAFAR"
    return out

@app.post("/api/packages", response_model=schemas.PackageOut)
def create_package(
    package: schemas.PackageCreate,
    current_user: models.User = Depends(auth_utils.require_role(["agency", "admin"])),
    db: Session = Depends(database.get_db)
):
    if current_user.role == "agency":
        agency = crud.get_agency_by_user_id(db, current_user.id)
        if not agency:
            raise HTTPException(status_code=400, detail="No agency details registered for this user")
        agency_id = agency.id
    else:
        # If admin creates, link to first agency (or default dummy agency id)
        first_agency = db.query(models.Agency).first()
        if not first_agency:
            raise HTTPException(status_code=400, detail="Create an agency first to associate packages")
        agency_id = first_agency.id

    db_pkg = crud.create_package(db, agency_id, package)
    out = schemas.PackageOut.model_validate(db_pkg)
    out.company_name = db_pkg.agency.company_name if db_pkg.agency else "SAFAR"
    return out

@app.put("/api/packages/{package_id}", response_model=schemas.PackageOut)
def update_package(
    package_id: int,
    package_update: schemas.PackageUpdate,
    current_user: models.User = Depends(auth_utils.require_role(["agency", "admin"])),
    db: Session = Depends(database.get_db)
):
    # Verify ownership
    pkg = crud.get_package(db, package_id)
    if not pkg:
        raise HTTPException(status_code=404, detail="Package not found")
        
    if current_user.role == "agency":
        agency = crud.get_agency_by_user_id(db, current_user.id)
        if not agency or pkg.agency_id != agency.id:
            raise HTTPException(status_code=403, detail="Not authorized to edit this package")

    updated = crud.update_package(db, package_id, package_update)
    out = schemas.PackageOut.model_validate(updated)
    out.company_name = updated.agency.company_name if updated.agency else "SAFAR"
    return out

@app.delete("/api/packages/{package_id}")
def delete_package(
    package_id: int,
    current_user: models.User = Depends(auth_utils.require_role(["agency", "admin"])),
    db: Session = Depends(database.get_db)
):
    # Verify ownership
    pkg = crud.get_package(db, package_id)
    if not pkg:
        raise HTTPException(status_code=404, detail="Package not found")
        
    if current_user.role == "agency":
        agency = crud.get_agency_by_user_id(db, current_user.id)
        if not agency or pkg.agency_id != agency.id:
            raise HTTPException(status_code=403, detail="Not authorized to delete this package")

    crud.delete_package(db, package_id)
    return {"message": "Package deleted successfully"}

# =========================================================================
# Bookings Router (Using Facade Pattern & Strategy Pattern)
# =========================================================================
@app.post("/api/bookings", response_model=schemas.BookingOut)
def create_booking(
    booking_req: schemas.BookingBase,
    guests: int = Query(1),
    current_user: models.User = Depends(auth_utils.require_role(["traveler"])),
    db: Session = Depends(database.get_db)
):
    try:
        # Utilize the Facade pattern to process booking creation & calculations
        booking = BookingFacade.process_booking(
            db=db,
            traveler_id=current_user.id,
            package_id=booking_req.package_id,
            guests=guests
        )
        
        # Format response
        out = schemas.BookingOut.model_validate(booking)
        out.traveler_name = current_user.name
        out.traveler_email = current_user.email
        out.package_title = booking.package.title
        out.package_type = booking.package.type
        out.package_price = booking.package.price
        return out
    except ValueError as ve:
        raise HTTPException(status_code=404, detail=str(ve))
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))

@app.get("/api/bookings", response_model=List[schemas.BookingOut])
def get_bookings(
    current_user: models.User = Depends(auth_utils.get_current_user),
    db: Session = Depends(database.get_db)
):
    if current_user.role == "traveler":
        bookings = crud.get_bookings_by_traveler(db, current_user.id)
    elif current_user.role == "agency":
        agency = crud.get_agency_by_user_id(db, current_user.id)
        if not agency:
            return []
        bookings = crud.get_bookings_for_agency(db, agency.id)
    else:  # Admin
        bookings = crud.get_all_bookings(db)

    result = []
    for b in bookings:
        out = schemas.BookingOut.model_validate(b)
        out.traveler_name = b.traveler.name
        out.traveler_email = b.traveler.email
        out.package_title = b.package.title
        out.package_type = b.package.type
        out.package_price = b.package.price
        result.append(out)
        
    return result

@app.put("/api/bookings/{booking_id}/status", response_model=schemas.BookingOut)
def update_booking_status(
    booking_id: int,
    status_update: schemas.BookingUpdate,
    current_user: models.User = Depends(auth_utils.require_role(["agency", "admin"])),
    db: Session = Depends(database.get_db)
):
    if status_update.status not in ["approved", "rejected"]:
        raise HTTPException(status_code=400, detail="Invalid booking status update")
        
    booking = crud.get_booking(db, booking_id)
    if not booking:
        raise HTTPException(status_code=404, detail="Booking not found")

    # If agency user, verify ownership of package
    if current_user.role == "agency":
        agency = crud.get_agency_by_user_id(db, current_user.id)
        if not agency or booking.package.agency_id != agency.id:
            raise HTTPException(status_code=403, detail="Not authorized to moderate this booking")

    updated = crud.update_booking_status(db, booking_id, status_update.status)
    
    # Notify registered observers of status modification
    notifier_publisher.notify_observers(
        booking_id=updated.id,
        status=updated.status,
        traveler_name=updated.traveler.name,
        traveler_email=updated.traveler.email
    )

    out = schemas.BookingOut.model_validate(updated)
    out.traveler_name = updated.traveler.name
    out.traveler_email = updated.traveler.email
    out.package_title = updated.package.title
    out.package_type = updated.package.type
    out.package_price = updated.package.price
    return out
