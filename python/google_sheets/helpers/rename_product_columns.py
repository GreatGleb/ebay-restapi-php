import math

class RenameProductColumns:
    @staticmethod
    async def run(list_of_dicts, columns, type):
        data = [await RenameProductColumns.rename_properties_in_dict(d, columns, type) for d in list_of_dicts]

        return data

    @staticmethod
    async def rename_properties_in_dict(old_dict, columns, type):
        new_dict = {}
        for key, value in columns.items():
            if type == 'fromDbToSheets':
                old_key = value['db_column_name']
                new_key = value['sheet_column_name']
            elif type == 'fromSheetsToDb':
                old_key = value['sheet_column_name']
                new_key = value['db_column_name']

            value = old_dict.get(old_key)

            if key in ("stock_quantity_pl", "stock_quantity_pruszkow"):
                # Преобразовать '>10 ' и т.п. в int, иначе 0
                if isinstance(value, str):
                    value = value.strip()
                    if value.startswith(">"):
                        try:
                            value = int(value[1:])
                        except ValueError:
                            value = 0
                    else:
                        try:
                            value = int(value)
                        except ValueError:
                            value = 0
                elif value is None:
                    value = 0
            elif key in ("retail_price_net", "retail_price_gross", "weight"):
                if isinstance(value, str):
                    value = value.replace(",", ".").strip()
                    try:
                        value = float(value)
                    except ValueError:
                        value = None
            elif key in ("sold_in_general"):
                if isinstance(value, str):
                    try:
                        value = int(math.ceil(float(value)))
                    except ValueError:
                        continue
                elif value is None:
                    value = 0
            elif key in ("has_hologram", "no_photo", "published_ebay_de"):
                if isinstance(value, str):
                    val_low = value.lower()
                    if val_low in ("1", "yes", "true", '+'):
                        value = True
                    else:
                        value = False
                else:
                    value = bool(value)
            if key in ("photos"):
                product_id = old_dict.get('#')
                if isinstance(value, str):
                    try:
                        value = [{"product_id": product_id, "original_photo_url": url.strip()} for url in value.split(",") if url.strip()]
                    except ValueError:
                        value = []

            new_dict[new_key] = value

        return new_dict
