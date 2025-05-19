from fastapi import HTTPException
from .manager import GoogleSheetsManager
from .services.categories.save_to_db_from_google_sheets import SaveCategoriesToDbFromGoogleSheets
from .services.products.update_categories_in_google_sheets import UpdateProductCategoriesInGoogleSheets
from .services.products.save_to_db_from_google_sheets import SaveProductsToDbFromGoogleSheets
import os

class GoogleSheetsController:
    def __init__(self):
        self.manager = GoogleSheetsManager()

    async def get_all_sheets(self):
        """
        Get all available Google Sheets for the authenticated user
        """
        try:
            sheets = self.manager.list_all_spreadsheets()
            return {
                "status": "success",
                "data": sheets
            }
        except Exception as e:
            raise HTTPException(status_code=500, detail=str(e))

    async def get_sheet(self):
        """
        Get all available Google Sheets for the authenticated user
        """

        sheet_id = os.getenv('GOOGLE_SHEETS_ID_PRODUCTS')

        try:
            sheets = self.manager.get_spreadsheet_by_id(sheet_id)
            return {
                "status": "success",
                "data": sheets
            }
        except Exception as e:
            raise HTTPException(status_code=500, detail=str(e))

    async def categories_save_to_db_from_google_sheets(self):
        """
        Import categories from Google Sheets to DB
        """

        return await SaveCategoriesToDbFromGoogleSheets.run()

    async def products_update_categories_in_google_sheets(self):
        """
        Import categories from Google Sheets to DB
        """

        return await UpdateProductCategoriesInGoogleSheets.run()

    async def products_save_to_db_from_google_sheets(self):
        """
        Import categories from Google Sheets to DB
        """

        return await SaveProductsToDbFromGoogleSheets.run()

sheets_controller = GoogleSheetsController()
