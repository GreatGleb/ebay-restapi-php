from db.db import Database
from db.models.category import Category
import os
import requests
from ...manager import GoogleSheetsManager
from ...helpers.rename_product_columns import RenameProductColumns
from ...helpers.prepare_product_columns import PrepareProductColumns
from ...helpers.find_table_cell import TableCellFinder
from ...helpers.get_table_schema import TableSchema

class UpdateProductsFromDbToGoogleSheets:
    def __init__(self):
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

    async def get_products_table_columns(self):
        TableSchemaInitiatedClass = TableSchema()
        result = await TableSchemaInitiatedClass.get_products_table_columns()

        return result

    async def get_products_from_db_api(self):
        data = []

        url = f"http://ebay_restapi_nginx/api/get/products"
        response = requests.get(url)

        if response.status_code == 200:
            data = response.json()

        return data

    def filter_updating_columns(self, products_db_data):
        allowed_props = {'#', 'Reference', 'TecDoc number', 'Supplier price without VAT', 'Supplier price with VAT', 'Retail price without VAT', 'Retail price with VAT', 'Quantity PL', 'Quantity Pruszkow', 'Category eBay.de Russian', 'Installation position Russian',  'Installation position English',  'Installation position German', 'Specifics Russian', 'Specifics English', 'Specifics German', 'Product type Russian', 'Product type English', 'Product type German', 'Description to eBay.de', 'Specifics to eBay.de', 'Category id eBay.de', 'Photo links', 'Photo', 'No photo', 'EAN', 'weight gram', 'Oe codes', 'Car compatibilities', 'Published to eBay.de?', 'Last update to eBay.de'}#'Part of eBay.de name - product type', 'Part of eBay name - for cars', 'eBay name Russian', 'eBay name English', 'eBay name German',

        filtered_data = []

        for item in products_db_data:
            filtered_item = {}
            for key, value in item.items():
                if key in allowed_props:
                    filtered_item[key] = value
            filtered_data.append(filtered_item)

        return filtered_data

    async def save_to_sheets(self, sheet, products_table_columns, products_db_data):
        finder = TableCellFinder(sheet)
        id_column_name = products_table_columns['id']['sheet_column_name']

        dataItems = []

        for item in products_db_data:
            product_id = item[id_column_name]

            for key, value in item.items():
                current_column_name = key
                thisProductCellIndexes = finder.get_cell_by_column_and_value(id_column_name, product_id)

                if thisProductCellIndexes is None:
                    continue

                currentColumnIndex = finder.get_cell_by_column_and_value(current_column_name)[0]
                currentCellIndex = currentColumnIndex + str(thisProductCellIndexes[1])

                item = {
                    'range': f"{self.sheet_name}!{currentCellIndex}",
                    'values': [[value]]
                }

                dataItems.append(item)

        dataForUploadToSheets = {
            "data": dataItems
        }

        result = self.manager.write_to_cells(
            spreadsheet_id=self.sheet_id,
            body=dataForUploadToSheets
        )

        return result

    async def run(self):
        sheet = await self.get_sheet()
        products_table_columns = await self.get_products_table_columns()

        products_db_data = await self.get_products_from_db_api()
        products_db_data = await PrepareProductColumns.run(products_db_data, products_table_columns, 'fromDbToSheets')
        products_db_data = await RenameProductColumns.run(products_db_data, products_table_columns, 'fromDbToSheets')
        products_db_data = self.filter_updating_columns(products_db_data)

        result = await self.save_to_sheets(sheet, products_table_columns, products_db_data)

        return result

UpdateProductsFromDbToGoogleSheets = UpdateProductsFromDbToGoogleSheets()