import os
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker, Session
from sqlalchemy.ext.declarative import declarative_base
from dotenv import load_dotenv

load_dotenv('/.env')

Base = declarative_base()

class Database:
    _instance = None
    _engine = None
    _SessionLocal = None

    def __new__(cls):
        if cls._instance is None:
            cls._instance = super(Database, cls).__new__(cls)
            cls._instance._initialize()
        return cls._instance

    def _initialize(self):
        """Initialize SQLAlchemy engine and session factory"""

        if not self._engine:
            DATABASE_URL = f"postgresql://{os.getenv('DB_USERNAME')}:{os.getenv('DB_PASSWORD')}@{os.getenv('DB_HOST')}:{os.getenv('DB_PORT')}/{os.getenv('DB_DATABASE')}"
            self._engine = create_engine(DATABASE_URL)
            self._SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=self._engine)
            Base.metadata.create_all(bind=self._engine)

    def get_session(self) -> Session:
        """Get a new database session"""
        if not self._SessionLocal:
            self._initialize()
        return self._SessionLocal()

    def get_engine(self):
        """Get SQLAlchemy engine"""
        if not self._engine:
            self._initialize()
        return self._engine

    def execute_query(self, query, params=None):
        """Execute a raw SQL query and return results"""
        session = self.get_session()
        try:
            result = session.execute(query, params)
            return result.fetchall()
        finally:
            session.close()

    def execute_update(self, query, params=None):
        """Execute a raw SQL update query"""
        session = self.get_session()
        try:
            session.execute(query, params)
            session.commit()
        except Exception as e:
            session.rollback()
            raise e
        finally:
            session.close()

    def bulk_save_objects(self, objects):
        """Bulk save a list of SQLAlchemy model objects"""
        session = self.get_session()
        try:
            session.bulk_save_objects(objects)
            session.commit()
        except Exception as e:
            session.rollback()
            raise e
        finally:
            session.close()

    def close(self):
        """Close all connections"""
        if self._engine:
            self._engine.dispose()
            self._engine = None
            self._SessionLocal = None