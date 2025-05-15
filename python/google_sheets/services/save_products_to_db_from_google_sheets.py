from ..manager import GoogleSheetsManager
from db.db import Database
from db.models.category import Category
import os

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

    async def get_products_from_sheet(self, sheet):
        result = []

        return sheet

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

    async def run(self):
        products = await self.parse_sheet()
        products = await self.get_products_from_sheet(sheet)

        return products

SaveProductsToDbFromGoogleSheets = SaveProductsToDbFromGoogleSheets()