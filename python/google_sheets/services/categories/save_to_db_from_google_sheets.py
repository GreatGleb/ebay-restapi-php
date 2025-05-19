from db.db import Database
from db.models.category import Category
import os
from ...manager import GoogleSheetsManager

class SaveCategoriesToDbFromGoogleSheets:
    def __init__(self):
        self.manager = GoogleSheetsManager()
        self.db = Database()

    async def get_sheet(self):
        """
        Get all available Google Sheets for the authenticated user
        """

        sheet_id = os.getenv('GOOGLE_SHEETS_ID_CATEGORIES')

        sheet = self.manager.get_spreadsheet_by_id(sheet_id)
        sheet = sheet['sheets']

        return sheet

    def parse_category_string(self, category_str: str) -> dict:
        parts = category_str.split("→")
        parts = [part.strip() for part in parts]

        category_id = parts[-1]
        category_name = parts[-2]

        full_name = " → ".join(parts[:-1])

        return {
            "id": category_id,
            "name": category_name,
            "full_name": full_name
        }

    async def parse_categories(self):
        result = []
        sheet = await self.get_sheet()

        sheetDe = sheet[0]['data']
        sheetRu = sheet[1]['data']

        for i, deName in enumerate(sheetDe):
            if i == 0:
                continue

            ruName = sheetRu[i]

            de_data = self.parse_category_string(deName[0])
            ru_data = self.parse_category_string(ruName[0])

            result.append({
                'name_de': de_data['name'],
                'full_name_de': de_data['full_name'],
                'name_ru': ru_data['name'],
                'full_name_ru': ru_data['full_name'],
                'ebay_de_id': ru_data['id'],
            })

        return result

    async def save_categories(self, categories):
        """Save categories to database using SQLAlchemy"""
        db_session = self.db.get_session()
        try:
            
            db_categories = []
            for category in categories:
                db_category = Category(
                    name_de=category['name_de'],
                    full_name_de=category['full_name_de'],
                    name_ru=category['name_ru'],
                    full_name_ru=category['full_name_ru'],
                    ebay_de_id=int(category['ebay_de_id'])
                )

                db_categories.append(db_category)

            if not db_categories:
                print("Hasn't new categories")
                return

            db_session.query(Category).delete()
            db_session.commit()

            self.db.bulk_save_objects(db_categories)
            db_session.commit()
            
            total_categories = db_session.query(Category).count()
            return {
                "status": "success",
                "message": f"Successfully imported {len(db_categories)} categories",
                "data": {
                    "inserted_count": len(db_categories),
                    "total_count": total_categories
                }
            }
        except Exception as e:
            db_session.rollback()
            return {
                "status": "error",
                "message": str(e)
            }
        finally:
            db_session.close()

    async def run(self):
        categories = await self.parse_categories()
        result = await self.save_categories(categories)

        return result

SaveCategoriesToDbFromGoogleSheets = SaveCategoriesToDbFromGoogleSheets()