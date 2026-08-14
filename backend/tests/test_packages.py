from unittest.mock import MagicMock
from .. import models
from ..services.package_factory import PackageFactory

def test_package_factory_formatting():
    # Test hotel title suffix generation
    hotel_pkg = PackageFactory.build_package(
        package_type="hotel",
        title="Hilton",
        location="London",
        price=300.0,
        description="Luxury stay"
    )
    assert hotel_pkg["title"] == "Hilton Hotel"
    assert hotel_pkg["type"] == "hotel"

    # Test tour title suffix generation
    tour_pkg = PackageFactory.build_package(
        package_type="tour",
        title="Sahara Safari",
        location="Morocco",
        price=150.0,
        description="Desert trip"
    )
    assert tour_pkg["title"] == "Sahara Safari Tour"
    assert tour_pkg["type"] == "tour"

def test_get_all_packages(client, mock_db):
    agency = models.Agency(id=1, user_id=2, company_name="Global Travels", status="verified")
    pkg = models.Package(id=1, agency_id=1, title="Swiss Alps Adventure", location="Zermatt", price=1200.0, description="Swiss alps", type="tour")
    pkg.agency = agency
    
    mock_db.query.return_value.order_by.return_value.all.return_value = [pkg]
    
    response = client.get("/api/packages")
    assert response.status_code == 200
    assert len(response.json()) == 1
    assert response.json()[0]["title"] == "Swiss Alps Adventure"
    assert response.json()[0]["company_name"] == "Global Travels"

def test_get_single_package_success(client, mock_db):
    agency = models.Agency(id=1, user_id=2, company_name="Global Travels", status="verified")
    pkg = models.Package(id=1, agency_id=1, title="Swiss Alps Adventure", location="Zermatt", price=1200.0, description="Swiss alps", type="tour")
    pkg.agency = agency
    
    mock_db.query.return_value.filter.return_value.first.return_value = pkg
    
    response = client.get("/api/packages/1")
    assert response.status_code == 200
    assert response.json()["title"] == "Swiss Alps Adventure"

def test_get_single_package_not_found(client, mock_db):
    mock_db.query.return_value.filter.return_value.first.return_value = None
    
    response = client.get("/api/packages/99")
    assert response.status_code == 404
    assert response.json()["detail"] == "Package not found"
