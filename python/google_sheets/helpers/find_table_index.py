class TableIndexFinder:
    def __init__(self, data):
        self.data = data
        self.headers = data[0]

    def get_index(self, column_name, value_to_find=None):
        if column_name not in self.headers:
            raise ValueError(f"Column '{column_name}' not found")

        col_index = self.headers.index(column_name)

        for row_index, row in enumerate(self.data[1:], start=1):
            if not value_to_find or (len(row) > col_index and row[col_index] == str(value_to_find)):
                return [col_index, row_index]

        return None

