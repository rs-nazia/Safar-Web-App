from sqlalchemy.orm import Session
from decimal import Decimal
from .. import models
from .pricing_strategies import PricingContext, TourPricingStrategy, HotelPricingStrategy
from .notification_service import notifier_publisher

class BookingFacade:
    """
    Facade Pattern to simplify booking creation.
    Coordinates:
    - Package price lookup
    - Pricing Strategy application
    - Payment simulation
    - Database insertion
    - Event notifications dispatch
    """
    @staticmethod
    def process_booking(db: Session, traveler_id: int, package_id: int, guests: int = 1) -> models.Booking:
        # 1. Retrieve package
        pkg = db.query(models.Package).filter(models.Package.id == package_id).first()
        if not pkg:
            raise ValueError("Package not found")

        # 2. Retrieve user
        traveler = db.query(models.User).filter(models.User.id == traveler_id).first()
        if not traveler:
            raise ValueError("Traveler not found")

        # 3. Apply appropriate Pricing Strategy
        if pkg.type.lower() == "hotel":
            strategy = HotelPricingStrategy()
        else:
            strategy = TourPricingStrategy()

        context = PricingContext(strategy)
        total_price = context.calculate(pkg.price, guests)

        # 4. Simulate Payment Gateway Check
        # (In production, this would communicate with Stripe/PayPal)
        payment_simulation_success = True
        if not payment_simulation_success:
            raise RuntimeError("Payment transaction failed")

        # 5. Record booking transaction
        booking = models.Booking(
            traveler_id=traveler_id,
            package_id=package_id,
            status="pending"
        )
        db.add(booking)
        db.commit()
        db.refresh(booking)

        # 6. Dispatch status notifications to registered observers
        notifier_publisher.notify_observers(
            booking_id=booking.id,
            status="pending",
            traveler_name=traveler.name,
            traveler_email=traveler.email
        )

        return booking
