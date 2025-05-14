from ..manager import GoogleSheetsManager
from db.db import Database
from db.models.category import Category
from ..helpers.find_table_cell import TableCellFinder
import os
import requests

class UpdateCategoriesInProductInGoogleSheets:
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

    async def parse_sheet(self):
        sheet = await self.get_sheet()

        headers = sheet[0]
        data_rows = sheet[1:]
        result = [dict(zip(headers, row)) for row in data_rows]

        return result

    async def save_to_sheets(self, categories):
        sheet = await self.get_sheet()

        finder = TableCellFinder(sheet)

        for item in categories:
            if item['category']:
                column = '#'
                value = item['product_id']

                productCellIndexes = finder.get_cell_by_column_and_value(column, value)
                categoryColumnIndex = finder.get_cell_by_column_and_value('Category')[0]
                categoryIdColumnIndex = finder.get_cell_by_column_and_value('Category id eBay.de')[0]

                categoryCellIndex = categoryColumnIndex + str(productCellIndexes[1])
                categoryIdCellIndex = categoryIdColumnIndex + str(productCellIndexes[1])

                self.manager.write_to_cell(
                    spreadsheet_id=self.sheet_id,
                    sheet_name=self.sheet_name,
                    cell=categoryCellIndex,
                    value=item['category']['full_name_ru']
                )

                self.manager.write_to_cell(
                    spreadsheet_id=self.sheet_id,
                    sheet_name=self.sheet_name,
                    cell=categoryIdCellIndex,
                    value=item['category']['ebay_de_id']
                )

        result = []

        return result

    async def get_categories_from_api(self, data):
        result = []

        for i, value in enumerate(data):
            if value['eBay name German']:
                name = value['eBay name German']
                encoded_name = requests.utils.quote(name)

                url = f"http://ebay_restapi_nginx/api/ebay/getCategoryByName/{encoded_name}"

                response = requests.get(url)

                if response.status_code == 200:
                    data = response.json()

                    categoriesId = []

                    if data['categorySuggestions']:
                        for categ in data['categorySuggestions']:
                            if categ['category']['categoryId']:
                                categoriesId.append(categ['category']['categoryId'])

                    if categoriesId:
                        productId = value['#']
                        result.append({
                            'product_id': productId,
                            'categories_id': categoriesId,
                        })
                else:
                    print("Ошибка:", response.status_code, response.text)

        return result

    async def filter_categories_from_db(self, categoriesId):
        all_categories = Category.get_all()

        for i, value in enumerate(categoriesId):
            ids = value['categories_id']

            valid_ids = set(map(int, ids))
            filtered_categories = [cat for cat in all_categories if cat['ebay_de_id'] in valid_ids]
            categoryId = filtered_categories[0] if filtered_categories[0] else null
            categoriesId[i]['category'] = categoryId

        return categoriesId

    async def run(self):
        sheet = await self.parse_sheet()
        categories = await self.get_categories_from_api(sheet)
        categories = await self.filter_categories_from_db(categories)
        result = await self.save_to_sheets(categories)

        return result

UpdateCategoriesInProductInGoogleSheets = UpdateCategoriesInProductInGoogleSheets()