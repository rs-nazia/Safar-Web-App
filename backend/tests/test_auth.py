from unittest.mock import MagicMock
from .. import models, auth_utils

def test_register_user_success(client, mock_db):
    # Mock crud.get_user_by_email returning None (email is free)
    mock_db.query.return_value.filter.return_value.first.return_value = None
    
    # Mock last insert user id
    mock_db.lastInsertId = MagicMock(return_value=1)
    
    response = client.post("/api/auth/register", json={
        "name": "Test Traveler",
        "email": "traveler@test.com",
        "password": "testpassword",
        "role": "traveler"
    })
    
    assert response.status_code == 200
    assert response.json()["email"] == "traveler@test.com"
    assert response.json()["role"] == "traveler"

def test_register_user_duplicate_email(client, mock_db):
    # Mock user exists
    existing_user = models.User(id=1, name="Existing", email="traveler@test.com", password="hash", role="traveler")
    mock_db.query.return_value.filter.return_value.first.return_value = existing_user
    
    response = client.post("/api/auth/register", json={
        "name": "Test Traveler",
        "email": "traveler@test.com",
        "password": "testpassword",
        "role": "traveler"
    })
    
    assert response.status_code == 400
    assert response.json()["detail"] == "Email already registered"

def test_login_user_success(client, mock_db):
    hashed_pwd = auth_utils.get_password_hash("testpassword")
    user = models.User(id=2, name="Test traveler", email="traveler@test.com", password=hashed_pwd, role="traveler")
    mock_db.query.return_value.filter.return_value.first.return_value = user
    
    response = client.post("/api/auth/login", json={
        "email": "traveler@test.com",
        "password": "testpassword"
      })
      
    assert response.status_code == 200
    assert "access_token" in response.json()
    assert response.json()["token_type"] == "bearer"

def test_login_user_invalid_credentials(client, mock_db):
    mock_db.query.return_value.filter.return_value.first.return_value = None
    
    response = client.post("/api/auth/login", json={
        "email": "traveler@test.com",
        "password": "wrongpassword"
    })
    
    assert response.status_code == 401
    assert response.json()["detail"] == "Incorrect email or password"
