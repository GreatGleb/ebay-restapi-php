from ..helpers.rename_product_from_sheet_to_db_style import RenameProductFromSheetToDbStyle
from ..manager import GoogleSheetsManager
from db.db import Database
from db.models.category import Category
import os
import requests

class SaveProductsToDbFromGoogleSheets:
    def __init__(self):
        self.manager = GoogleSheetsManager()
        self.db = Database()

    async def get_sheet(self):
        """
        Get Google Sheet
        """

        sheet_id = os.getenv('GOOGLE_SHEETS_ID_PRODUCTS')

        sheet = self.manager.get_spreadsheet_by_id(sheet_id)
        sheet = sheet['sheets'][0]

        self.sheet_name = sheet['properties']['title']
        sheet = sheet['data']

        return sheet

    async def parse_sheet(self):
        sheet = await self.get_sheet()

        headers = sheet[0]
        data_rows = sheet[1:]
        result = [dict(zip(headers, row)) for row in data_rows]

        return result

    async def updateProductsInDB(self, products):
        data = 'false'

        url = f"http://ebay_restapi_nginx/api/update/products"
        response =  requests.post(url, json=products)

        if response.status_code == 200:
            data = response.json()
        else:
            print("Ошибка:", response.status_code, response.text)

        return data

    async def run(self):
        list_of_dicts = await self.parse_sheet()
        products = RenameProductFromSheetToDbStyle.run(list_of_dicts)
        response = await self.updateProductsInDB(products)

        return response

SaveProductsToDbFromGoogleSheets = SaveProductsToDbFromGoogleSheets()