from sqlalchemy.orm import Session
from sqlalchemy import or_
from typing import List, Optional
from . import models, schemas
from .services.package_factory import PackageFactory

# ==========================================
# User CRUD
# ==========================================
def get_user(db: Session, user_id: int) -> Optional[models.User]:
    return db.query(models.User).filter(models.User.id == user_id).first()

def get_user_by_email(db: Session, email: str) -> Optional[models.User]:
    return db.query(models.User).filter(models.User.email == email).first()

def create_user(db: Session, user: schemas.UserCreate) -> models.User:
    db_user = models.User(
        name=user.name,
        email=user.email,
        password=user.password,  # Pre-hashed
        role=user.role
    )
    db.add(db_user)
    db.commit()
    db.refresh(db_user)
    return db_user

def update_user(db: Session, user_id: int, user_data: schemas.UserUpdate) -> Optional[models.User]:
    db_user = get_user(db, user_id)
    if not db_user:
        return None
    for key, value in user_data.model_dump(exclude_unset=True).items():
        setattr(db_user, key, value)
    db.commit()
    db.refresh(db_user)
    return db_user

# ==========================================
# Agency CRUD
# ==========================================
def get_agency_by_user_id(db: Session, user_id: int) -> Optional[models.Agency]:
    return db.query(models.Agency).filter(models.Agency.user_id == user_id).first()

def create_agency(db: Session, agency: schemas.AgencyCreate) -> models.Agency:
    db_agency = models.Agency(
        user_id=agency.user_id,
        company_name=agency.company_name,
        phone=agency.phone,
        status=agency.status
    )
    db.add(db_agency)
    db.commit()
    db.refresh(db_agency)
    return db_agency

def get_all_agencies(db: Session) -> List[models.Agency]:
    return db.query(models.Agency).all()

def update_agency_status(db: Session, agency_id: int, status: str) -> Optional[models.Agency]:
    db_agency = db.query(models.Agency).filter(models.Agency.id == agency_id).first()
    if not db_agency:
        return None
    db_agency.status = status
    db.commit()
    db.refresh(db_agency)
    return db_agency

# ==========================================
# Package CRUD (Using Factory Method)
# ==========================================
def get_packages(
    db: Session, 
    type_filter: Optional[str] = None, 
    location: Optional[str] = None, 
    price_max: Optional[float] = None
) -> List[models.Package]:
    query = db.query(models.Package)
    
    if type_filter and type_filter != "all":
        query = query.filter(models.Package.type == type_filter)
        
    if location:
        query = query.filter(models.Package.location.ilike(f"%{location}%"))
        
    if price_max is not None:
        query = query.filter(models.Package.price <= price_max)
        
    # Join with agencies to get company name
    return query.order_by(models.Package.created_at.desc()).all()

def get_package(db: Session, package_id: int) -> Optional[models.Package]:
    return db.query(models.Package).filter(models.Package.id == package_id).first()

def create_package(db: Session, agency_id: int, package: schemas.PackageCreate) -> models.Package:
    # Build package dictionary via Factory Method Pattern
    pkg_data = PackageFactory.build_package(
        package_type=package.type,
        title=package.title,
        location=package.location,
        price=float(package.price),
        description=package.description,
        image_url=package.image_url
    )
    
    db_package = models.Package(
        agency_id=agency_id,
        **pkg_data
    )
    db.add(db_package)
    db.commit()
    db.refresh(db_package)
    return db_package

def update_package(db: Session, package_id: int, package_data: schemas.PackageUpdate) -> Optional[models.Package]:
    db_package = get_package(db, package_id)
    if not db_package:
        return None
        
    for key, value in package_data.model_dump(exclude_unset=True).items():
        setattr(db_package, key, value)
        
    db.commit()
    db.refresh(db_package)
    return db_package

def delete_package(db: Session, package_id: int) -> bool:
    db_package = get_package(db, package_id)
    if not db_package:
        return False
    db.delete(db_package)
    db.commit()
    return True

# ==========================================
# Booking CRUD
# ==========================================
def get_booking(db: Session, booking_id: int) -> Optional[models.Booking]:
    return db.query(models.Booking).filter(models.Booking.id == booking_id).first()

def get_bookings_by_traveler(db: Session, traveler_id: int) -> List[models.Booking]:
    return db.query(models.Booking).filter(models.Booking.traveler_id == traveler_id).order_by(models.Booking.booking_date.desc()).all()

def get_bookings_for_agency(db: Session, agency_id: int) -> List[models.Booking]:
    return db.query(models.Booking)\
             .join(models.Package)\
             .filter(models.Package.agency_id == agency_id)\
             .order_by(models.Booking.booking_date.desc()).all()

def get_all_bookings(db: Session) -> List[models.Booking]:
    return db.query(models.Booking).order_by(models.Booking.booking_date.desc()).all()

def update_booking_status(db: Session, booking_id: int, status: str) -> Optional[models.Booking]:
    db_booking = get_booking(db, booking_id)
    if not db_booking:
        return None
    db_booking.status = status
    db.commit()
    db.refresh(db_booking)
    return db_booking
