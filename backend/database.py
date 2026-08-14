import os
from sqlalchemy import create_engine
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker

# Default database connection string (PostgreSQL)
DATABASE_URL = os.getenv("DATABASE_URL", "postgresql://postgres:postgres@localhost:5432/safar_db")

class DatabaseSessionManager:
    """
    Singleton Pattern implementation for database connection pool management.
    Ensures only a single engine and sessionmaker instance are created during runtime.
    """
    _instance = None

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super(DatabaseSessionManager, cls).__new__(cls)
            cls._instance.engine = create_engine(DATABASE_URL, pool_pre_ping=True)
            cls._instance.SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=cls._instance.engine)
        return cls._instance

# Retrieve the Singleton instance
session_manager = DatabaseSessionManager()

Base = declarative_base()

# Dependency for database sessions
def get_db():
    db = session_manager.SessionLocal()
    try:
        yield db
    finally:
        db.close()
