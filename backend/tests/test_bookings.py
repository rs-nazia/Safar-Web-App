from unittest.mock import MagicMock
from decimal import Decimal
from .. import models
from ..services.pricing_strategies import PricingContext, TourPricingStrategy, HotelPricingStrategy
from ..services.notification_service import BookingStatusSubject, BookingStatusObserver

def test_pricing_strategy_tours():
    # Tour pricing: $100 base * 3 guests = $300
    strategy = TourPricingStrategy()
    context = PricingContext(strategy)
    total = context.calculate(Decimal("100.00"), 3)
    assert total == Decimal("300.00")

def test_pricing_strategy_hotels():
    # Hotel pricing: ($100 base * 2 guests) + 10% daily resort fee = $200 + $20 = $220
    strategy = HotelPricingStrategy()
    context = PricingContext(strategy)
    total = context.calculate(Decimal("100.00"), 2)
    assert total == Decimal("220.00")

def test_observer_pattern_notification():
    class TestObserver(BookingStatusObserver):
        def __init__(self):
            self.notified = False
            self.booking_id = None
            self.status = None

        def update(self, booking_id: int, status: str, traveler_name: str, traveler_email: str) -> None:
            self.notified = True
            self.booking_id = booking_id
            self.status = status

    subject = BookingStatusSubject()
    observer = TestObserver()
    subject.register_observer(observer)

    # Trigger update
    subject.notify_observers(booking_id=42, status="approved", traveler_name="Jane Doe", traveler_email="jane@test.com")
    
    assert observer.notified is True
    assert observer.booking_id == 42
    assert observer.status == "approved"
