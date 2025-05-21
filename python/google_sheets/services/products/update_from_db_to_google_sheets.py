from db.db import Database
from db.models.category import Category
import os
import requests
from ...manager import GoogleSheetsManager
from ...helpers.find_table_cell import TableCellFinder
from ...helpers.get_table_schema import TableSchema

class UpdateProductsFromDbToGoogleSheets:
    def __init__(self):
        print('herr')
        self.manager = GoogleSheetsManager()
        self.db = Database()

    async def get_sheet(self):
        sheet_id = os.getenv('GOOGLE_SHEETS_ID_PRODUCTS')
        self.sheet_id = sheet_id

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

    async def get_products_table_columns(self):
        TableSchemaInitiatedClass = TableSchema()
        result = await TableSchemaInitiatedClass.get_products_table_columns()

        return result

    async def get_products_from_db_api(self):
        res = [32]

        return res

    async def run(self):
        sheet = await self.parse_sheet()
#         self.products_table_columns = await self.get_products_table_columns()
#
#         productsDbData = await self.get_products_from_db_api()

#         categories = await self.filter_categories_from_db(categoriesId)
#         result = await self.save_to_sheets(categories)

        return sheet

UpdateProductsFromDbToGoogleSheets = UpdateProductsFromDbToGoogleSheets()