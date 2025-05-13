from fastapi import HTTPException
from .manager import GoogleSheetsManager
from .helpers.save_to_db_from_google_sheets_categories import SaveToDbFromGoogleSheetsCategories
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

    async def save_to_db_from_google_sheets_categories(self):
        """
        Import categories from Google Sheets to DB
        """

        return await SaveToDbFromGoogleSheetsCategories.run()

sheets_controller = GoogleSheetsController()
