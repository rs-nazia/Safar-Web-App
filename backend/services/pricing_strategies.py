from abc import ABC, abstractmethod
from decimal import Decimal

class PricingStrategy(ABC):
    """
    Strategy Pattern Interface for booking total calculations.
    """
    @abstractmethod
    def calculate_total(self, base_price: Decimal, guests: int) -> Decimal:
        pass

class TourPricingStrategy(PricingStrategy):
    """
    Tour Pricing Strategy: Flat fee per person.
    """
    def calculate_total(self, base_price: Decimal, guests: int) -> Decimal:
        # Simple multiplication by guest count
        return base_price * Decimal(max(1, guests))

class HotelPricingStrategy(PricingStrategy):
    """
    Hotel Pricing Strategy: Multiplies base price by guest count
    and includes a standard 10% daily resort fee.
    """
    def calculate_total(self, base_price: Decimal, guests: int) -> Decimal:
        guests_count = Decimal(max(1, guests))
        subtotal = base_price * guests_count
        resort_fee = subtotal * Decimal("0.10")
        return subtotal + resort_fee

class PricingContext:
    """
    Context for executing pricing calculation strategies.
    """
    def __init__(self, strategy: PricingStrategy):
        self._strategy = strategy

    def set_strategy(self, strategy: PricingStrategy):
        self._strategy = strategy

    def calculate(self, base_price: Decimal, guests: int) -> Decimal:
        return self._strategy.calculate_total(base_price, guests)
