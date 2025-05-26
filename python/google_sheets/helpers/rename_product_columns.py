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

            if old_key not in old_dict.keys():
                continue

            value = old_dict.get(old_key)
            new_dict[new_key] = value

        return new_dict
