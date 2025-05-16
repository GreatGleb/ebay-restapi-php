import math

class RenameProductFromSheetToDbStyle:
    @staticmethod
    def run(list_of_dicts):
        data = [RenameProductFromSheetToDbStyle.rename_properties_in_dict(d) for d in list_of_dicts]

        return data

    @staticmethod
    def rename_properties_in_dict(old_dict):
        mapping = {
            "#": "id",
            "Comment": "comment",
            "Link": "link",
            "Reference": "reference",
            "TecDoc number": "tecdoc_number",
            "Specifics": "specifics",
            "Category": "category",
            "Category id eBay.de": "category_ebay_id",
            "Internal description": "internal_description",
            "Name original pl": "name_original_pl",
            "Retail price without VAT": "retail_price_net",
            "Retail price with VAT": "retail_price_gross",
            "Наличие PL": "stock_quantity_pl",
            "Наличие Mag. ODDZIAŁ PRUSZKÓW": "stock_quantity_pruszkow",
            "Installation position": "installation_position",
            "Product type": "product_type",
            "Part of eBay name - for cars": "part_of_ebay_name",
            "eBay name Russian": "ebay_name_ru",
            "eBay name English": "ebay_name_en",
            "eBay name German": "ebay_name_de",
            "Photos": "photos",
            "С голограммой": "has_hologram",
            "Без фото": "no_photo",
            "Supplier": "supplier",
            "Producer brand": "producer_brand",
            "EAN": "ean",
            "weight": "weight",
            "box length cm": "box_length_cm",
            "box width cm": "box_width_cm",
            "box height cm": "box_height_cm",
            "Oe codes": "oe_codes",
            "Cars compatibilities": "cars_compatibilities",
            "Published to ebay.de?": "published_ebay_de",
            "Last update in ebay.de": "last_update_ebay",
            "Sold in general": "sold_in_general",
        }

        new_dict = {}
        for old_key, new_key in mapping.items():
            value = old_dict.get(old_key)

            if new_key in ("stock_quantity_pl", "stock_quantity_pruszkow"):
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
            elif new_key in ("retail_price_net", "retail_price_gross", "weight"):
                if isinstance(value, str):
                    value = value.replace(",", ".").strip()
                    try:
                        value = float(value)
                    except ValueError:
                        value = None
            elif new_key in ("sold_in_general"):
                if isinstance(value, str):
                    try:
                        value = int(math.ceil(float(value)))
                    except ValueError:
                        continue
                elif value is None:
                    value = 0
            elif new_key in ("has_hologram", "no_photo", "published_ebay_de"):
                if isinstance(value, str):
                    val_low = value.lower()
                    if val_low in ("1", "yes", "true", '+'):
                        value = True
                    else:
                        value = False
                else:
                    value = bool(value)
            if new_key in ("photos"):
                product_id = old_dict.get('#')
                if isinstance(value, str):
                    try:
                        value = [{"product_id": product_id, "original_photo_url": url.strip()} for url in value.split(",") if url.strip()]
                    except ValueError:
                        value = []

            new_dict[new_key] = value

        return new_dict
