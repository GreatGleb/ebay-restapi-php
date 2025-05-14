class ExcelCellConverter:
    @staticmethod
    def index_to_cell(col: int, row: int) -> str:
        if row < 1 or col < 1:
            raise ValueError("Индексы строки и столбца должны начинаться с 1")

        column_label = ''
        while col > 0:
            col, remainder = divmod(col - 1, 26)
            column_label = chr(65 + remainder) + column_label

        return [column_label, row]

ExcelCellConverter = ExcelCellConverter()