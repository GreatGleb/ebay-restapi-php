from sqlalchemy import Column, Integer, String, DateTime, func
from ..db import Base

class Category(Base):
    __tablename__ = 'categories'

    id = Column(Integer, primary_key=True, autoincrement=True)
    name_ru = Column(String(255), nullable=False)
    full_name_ru = Column(String(255), nullable=False)
    name_de = Column(String(255), nullable=False)
    full_name_de = Column(String(255), nullable=False)
    ebay_de_id = Column(Integer, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    def __repr__(self):
        return f"<Category(id={self.id}, name_de='{self.name_de}', ebay_de_id={self.ebay_de_id})"

    def to_dict(self):
        return {
            'id': self.id,
            'name_ru': self.name_ru,
            'full_name_ru': self.full_name_ru,
            'name_de': self.name_de,
            'full_name_de': self.full_name_de,
            'ebay_de_id': self.ebay_de_id,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }
