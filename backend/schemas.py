from pydantic import BaseModel, EmailStr
from typing import Optional, List
from decimal import Decimal
from datetime import datetime

# ==========================================
# User Schemas
# ==========================================
class UserBase(BaseModel):
    name: str
    email: EmailStr
    role: str = "traveler"

class UserCreate(UserBase):
    password: str
    company_name: Optional[str] = None  # Needed if signing up as an agency
    phone: Optional[str] = None         # Needed if signing up as an agency

class UserLogin(BaseModel):
    email: EmailStr
    password: str

class UserUpdate(BaseModel):
    name: Optional[str] = None
    email: Optional[EmailStr] = None
    password: Optional[str] = None
    profile_image: Optional[str] = None

class UserOut(BaseModel):
    id: int
    name: str
    email: EmailStr
    role: str
    profile_image: Optional[str] = None
    created_at: datetime

    class Config:
        from_attributes = True

# ==========================================
# Token Schemas
# ==========================================
class Token(BaseModel):
    access_token: str
    token_type: str

class TokenData(BaseModel):
    user_id: Optional[int] = None
    role: Optional[str] = None

# ==========================================
# Agency Schemas
# ==========================================
class AgencyBase(BaseModel):
    company_name: str
    phone: Optional[str] = None
    status: str = "pending"

class AgencyCreate(AgencyBase):
    user_id: int

class AgencyUpdate(BaseModel):
    company_name: Optional[str] = None
    phone: Optional[str] = None
    status: Optional[str] = None

class AgencyOut(AgencyBase):
    id: int
    user_id: int

    class Config:
        from_attributes = True

# ==========================================
# Package Schemas
# ==========================================
class PackageBase(BaseModel):
    title: str
    location: str
    price: Decimal
    description: str
    image_url: Optional[str] = None
    type: str = "tour"

class PackageCreate(PackageBase):
    pass

class PackageUpdate(BaseModel):
    title: Optional[str] = None
    location: Optional[str] = None
    price: Optional[Decimal] = None
    description: Optional[str] = None
    image_url: Optional[str] = None
    type: Optional[str] = None

class PackageOut(PackageBase):
    id: int
    agency_id: int
    company_name: Optional[str] = None  # Injected for frontend display
    created_at: datetime

    class Config:
        from_attributes = True

# ==========================================
# Booking Schemas
# ==========================================
class BookingBase(BaseModel):
    package_id: int

class BookingCreate(BookingBase):
    pass

class BookingUpdate(BaseModel):
    status: str

class BookingOut(BaseModel):
    id: int
    traveler_id: int
    package_id: int
    status: str
    booking_date: datetime
    traveler_name: Optional[str] = None
    traveler_email: Optional[str] = None
    package_title: Optional[str] = None
    package_type: Optional[str] = None
    package_price: Optional[Decimal] = None

    class Config:
        from_attributes = True
