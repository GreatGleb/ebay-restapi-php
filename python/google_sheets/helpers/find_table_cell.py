from .find_table_index import TableIndexFinder
from .convert_excel_cell_location import ExcelCellConverter

class TableCellFinder:
    def __init__(self, data):
        self.data = data
        self.index_finder = TableIndexFinder(data)

    def get_cell_by_column_and_value(self, column_name, value_to_find=None):
        result = self.index_finder.get_index(column_name, value_to_find)
        if result is None:
            return None
        col_idx, row_idx = result
        col_idx = col_idx + 1
        row_idx = row_idx + 1

        return ExcelCellConverter.index_to_cell(col_idx, row_idx)

    def get_cell_by_indices(self, row_idx, col_idx):
        return ExcelCellConverter.index_to_cell(row_idx, col_idx)