import requests

class TableSchema:
    async def get_schema(self):
        schema = {}

        url = f"http://ebay_restapi_nginx/api/getTableSchema"

        response = requests.get(url)

        if response.status_code == 200:
            schema = response.json()

        return schema

    async def get_products_table_columns(self):
        data = {}

        self.schema = await self.get_schema()
        if self.schema:
            columns = self.schema["tables"]
            columns = next((item for item in columns if item["table_name"] == "products"), None)
            columns = columns["columns"]

            for item in columns:
                data[item["name"]] = item["sheet_column_name"]

        return data
