from sqlalchemy import Column, Integer, String, Numeric, Text, ForeignKey, DateTime
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from .database import Base

class User(Base):
    __tablename__ = "users"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(100), nullable=False)
    email = Column(String(100), unique=True, index=True, nullable=False)
    password = Column(String(255), nullable=False)
    role = Column(String(20), default="traveler")
    profile_image = Column(String(255), nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    # Relationships
    agencies = relationship("Agency", back_populates="user", cascade="all, delete-orphan")
    bookings = relationship("Booking", back_populates="traveler", cascade="all, delete-orphan")

class Agency(Base):
    __tablename__ = "agencies"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id", ondelete="CASCADE"), nullable=False)
    company_name = Column(String(150), nullable=False)
    phone = Column(String(20), nullable=True)
    status = Column(String(20), default="pending")

    # Relationships
    user = relationship("User", back_populates="agencies")
    packages = relationship("Package", back_populates="agency", cascade="all, delete-orphan")

class Package(Base):
    __tablename__ = "packages"

    id = Column(Integer, primary_key=True, index=True)
    agency_id = Column(Integer, ForeignKey("agencies.id", ondelete="CASCADE"), nullable=False)
    title = Column(String(200), nullable=False)
    location = Column(String(150), nullable=False)
    price = Column(Numeric(10, 2), nullable=False)
    description = Column(Text, nullable=False)
    image_url = Column(String(255), nullable=True)
    type = Column(String(20), default="tour")
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    # Relationships
    agency = relationship("Agency", back_populates="packages")
    bookings = relationship("Booking", back_populates="package", cascade="all, delete-orphan")

class Booking(Base):
    __tablename__ = "bookings"

    id = Column(Integer, primary_key=True, index=True)
    traveler_id = Column(Integer, ForeignKey("users.id", ondelete="CASCADE"), nullable=False)
    package_id = Column(Integer, ForeignKey("packages.id", ondelete="CASCADE"), nullable=False)
    status = Column(String(20), default="pending")
    booking_date = Column(DateTime(timezone=True), server_default=func.now())

    # Relationships
    traveler = relationship("User", back_populates="bookings")
    package = relationship("Package", back_populates="bookings")
