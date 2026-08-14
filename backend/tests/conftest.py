import pytest
from unittest.mock import MagicMock
from fastapi.testclient import TestClient
from sqlalchemy.orm import Session
from ..main import app
from ..database import get_db

@pytest.fixture
def mock_db():
    """
    Mock Session fixture to isolate DB transactions.
    """
    return MagicMock(spec=Session)

@pytest.fixture
def client(mock_db):
    """
    TestClient fixture with overridden DB dependency.
    """
    app.dependency_overrides[get_db] = lambda: mock_db
    with TestClient(app) as c:
        yield c
    app.dependency_overrides.clear()
