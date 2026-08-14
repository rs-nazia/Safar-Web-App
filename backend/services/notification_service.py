from abc import ABC, abstractmethod
from typing import List

class BookingStatusObserver(ABC):
    """
    Observer Interface for receiving notification updates.
    """
    @abstractmethod
    def update(self, booking_id: int, status: str, traveler_name: str, traveler_email: str) -> None:
        pass

class EmailNotificationObserver(BookingStatusObserver):
    """
    Concrete Observer simulating email dispatch.
    """
    def __init__(self):
        self.dispatched_logs = []

    def update(self, booking_id: int, status: str, traveler_name: str, traveler_email: str) -> None:
        log_msg = f"Email sent to {traveler_email} ({traveler_name}): Booking #{booking_id} status has been updated to '{status}'."
        print(log_msg)
        self.dispatched_logs.append(log_msg)

class ConsoleNotificationObserver(BookingStatusObserver):
    """
    Concrete Observer logging notification to administrative console.
    """
    def __init__(self):
        self.dispatched_logs = []

    def update(self, booking_id: int, status: str, traveler_name: str, traveler_email: str) -> None:
        log_msg = f"Console Audit Log - Booking #{booking_id} status updated to '{status}' for user '{traveler_name}'."
        print(log_msg)
        self.dispatched_logs.append(log_msg)

class BookingStatusSubject:
    """
    Subject (Publisher) maintaining active subscribers and notifying them.
    """
    def __init__(self):
        self._observers: List[BookingStatusObserver] = []

    def register_observer(self, observer: BookingStatusObserver):
        if observer not in self._observers:
            self._observers.append(observer)

    def remove_observer(self, observer: BookingStatusObserver):
        if observer in self._observers:
            self._observers.remove(observer)

    def notify_observers(self, booking_id: int, status: str, traveler_name: str, traveler_email: str):
        for observer in self._observers:
            observer.update(booking_id, status, traveler_name, traveler_email)

# Singleton helper for global notifications publisher
notifier_publisher = BookingStatusSubject()
# Register initial observers
email_observer = EmailNotificationObserver()
console_observer = ConsoleNotificationObserver()
notifier_publisher.register_observer(email_observer)
notifier_publisher.register_observer(console_observer)
