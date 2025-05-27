import math

class PrepareProductColumns:
    @staticmethod
    async def run(list_of_dicts, columns, source_type):
        data = []

        if source_type == 'fromDbToSheets':
            id_column_name = 'id'
        elif source_type == 'fromSheetsToDb':
            id_column_name = columns['id']['sheet_column_name']

        for d in list_of_dicts:
            prepared_item = await PrepareProductColumns.prepare_properties_in_dict(d, columns, source_type)
            if len(prepared_item) > 1 and id_column_name in prepared_item:
                data.append(prepared_item)

        all_keys = set().union(*data)

        for d in data:
            for key in all_keys:
                column = None
                if source_type == 'fromDbToSheets':
                    column = columns.get(key)
                elif source_type == 'fromSheetsToDb':
                    for column_key, column_value in columns.items():
                        if key == column_value['sheet_column_name']:
                            column = column_value
                            break
                if column:
                    column_type = column['db_column_type']
                    if column_type == 'unsignedInteger' or column_type == 'integer' or column_type == 'decimal':
                        d.setdefault(key, 0)
                        continue
                    elif column_type == 'boolean':
                        d.setdefault(key, 0)
                        continue

                d.setdefault(key, None)

        return data

    @staticmethod
    async def prepare_properties_in_dict(old_dict, columns, source_type):
        prepared_dict = {}

        for key, value in old_dict.items():
            column = None
            if source_type == 'fromDbToSheets':
                column = columns.get(key)
            elif source_type == 'fromSheetsToDb':
                for column_key, column_value in columns.items():
                    if key == column_value['sheet_column_name']:
                        column = column_value
                        break
            if not column:
                continue

            db_key = column['db_column_name']
            column_type = column['db_column_type']

            if isinstance(value, list):
                if source_type == 'fromDbToSheets':
                    value = ", ".join(map(str, value))
                if not value:
                    value = None
            if isinstance(value, str):
                value = value.strip()
                if not value:
                    value = None
            if column_type == 'unsignedInteger' or column_type == 'integer':
                if isinstance(value, str):
                    value = value.strip()
                    try:
                        value = int(math.ceil(float(value)))
                    except ValueError:
                        value = None
            elif column_type == 'decimal':
                if source_type == 'fromSheetsToDb':
                    if isinstance(value, str):
                        value = value.replace(",", ".").strip()
                if value:
                    try:
                        value = float(value)
                    except ValueError:
                        value = None
            elif column_type == 'boolean':
                if source_type == 'fromSheetsToDb':
                    if isinstance(value, str):
                        val_low = value.lower()
                        if val_low in ("1", "yes", "true", '\'+'):
                            value = True
                        else:
                            value = False
                    else:
                        value = bool(value)
                elif source_type == 'fromDbToSheets':
                    if value == True:
                        value = '\'+'
                    else:
                        value = None
            if db_key == 'oe_codes':
                if source_type == 'fromDbToSheets':
                    if value:
                        value = 'saved to db from tecdoc'
                    else:
                        value = None
            if db_key == 'car_compatibilities':
                if source_type == 'fromDbToSheets':
                    if value:
                        value = 'saved to db from tecdoc'
                    else:
                        value = None
            if db_key == 'photos':
                if source_type == 'fromSheetsToDb':
                    id_column_name = columns.get('id')['sheet_column_name']
                    product_id = old_dict.get(id_column_name)
                    if isinstance(value, str):
                        try:
                            value = [{"product_id": product_id, "original_photo_url": url.strip()} for url in value.split(",") if url.strip()]
                        except ValueError:
                            value = []
                elif source_type == 'fromDbToSheets':
                    if value and isinstance(value, dict) and value['links']:
                        withLogo = value['withLogo']
                        photos = value['links']
                        photo = photos[0]

                        if photo:
                            prepared_dict['photo'] = f'=IMAGE("{photo}")'

                        if withLogo:
                            value = 'saved to github with logo'
                        else:
                            value = 'saved to db from tecdoc'
                    else:
                        value = None

            if value is None:
                continue

            prepared_dict[key] = value

        return prepared_dict