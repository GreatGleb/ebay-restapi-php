<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ebay API Request</title>
</head>
<body>
<h1>Отправка запроса к Ebay API</h1>
<button id="sendRequest">Отправить запрос</button>
<div id="response"></div>

<script>
document.getElementById('sendRequest').addEventListener('click', async function() {
    const url = "http://localhost/api/update/products";
    const data = [{
        "id": "31",
        "reference": null,
        "tecdoc_number": "72-3731",
        "retail_price_net": 0,
        "retail_price_gross": 0,
        "supplier_price_net": 0,
        "supplier_price_gross": 0,
        "stock_quantity_pl": 0,
        "stock_quantity_pruszkow": 0,
        "name_original_pl": null,
        "internal_description": null,
        "ru_category_from_ebay_de": "Подвеска и рулевое управление → Рычаги управления и шаровые шарниры",
        "category_id_ebay_de": "33580",
        "installation_position_ru": "передний мост слева",
        "installation_position_de": "Vorderachse links",
        "specifics_ru": "парные номера артикулов - 72-3732,\nМатериал - алюминий,\nТип рычага - Поперечный рычаг,\nСторона установки - передний мост слева,\nДополнительный артикул / дополнительная информация 2 - с подшипником",
        "specifics_en": "paired article number - 72-3732,\nMaterial - Aluminium,\nControl/Trailing Arm Type - Control Arm,\nFitting Position - Front Axle Left,\nSupplementary Article/Info 2 - with bearing",
        "specifics_de": "paarige Artikelnummer - 72-3732,\nMaterial - Aluminium,\nLenkerart - Querlenker,\nEinbauposition - Vorderachse links,\nErgänzungsartikel/Ergänzende Info 2 - mit Lager",
        "product_type_ru": "Рычаг независимой подвески колеса, подвеска колеса",
        "product_type_en": "Control/Trailing Arm, wheel suspension",
        "product_type_de": "Lenker, Radaufhängung",
        "part_of_ebay_de_name_product_type": "Querlenker vorderachse links passend für",
        "part_of_ebay_name_for_cars": null,
        "ebay_name_ru": null,
        "ebay_name_de": "Querlenker vorderachse links passend für",
        "description_to_ebay_de": null,
        "photos": [
            {
                "product_id": "31",
                "original_photo_url": "saved to github with logo"
            }
        ],
        "photo": "=IMAGE(\"None\")",
        "ebay_similar_products_name": "2x Maxgear Lenker Radaufhängung Vorne Links Rechts für Hyundai Santa Fé II\nQUERLENKER VORNE LINKS MAXGEAR 72-3731 FÜR HYUNDAI SANTA F 2 CM\nOriginal MAXGEAR Lenker Radaufhängung 72-3731 für Hyundai\nMaxgear Lenker, Radaufhängung 72-3731 für HYUNDAI\nQuerlenker Dreieckslenker MAXGEAR 72-3731 Aluminium für HYUNDAI SANTA FÉ 2 CM\nQuerlenker Vorderachse links für HYUNDAI SANTA FÉ II Kasten/SUV\n2X MAXGEAR QUERLENKER SATZ VORNE PASSEND FÜR HYUNDAI SANTA LINKS+RECHTS\nMAXGEAR QUERLENKER VORDERACHSE LINKS PASSEND FÜR HYUNDAI SANTA | 72-3731\nMAXGEAR 72-3731 Querlenker für HYUNDAI\nLenker, Radaufhängung MAXGEAR 72-3731 für Hyundai",
        "ebay_similar_products_photo": "=IMAGE(\"None\")",
        "has_hologram": false,
        "no_photo": false,
        "supplier": null,
        "producer_brand": "MAXGEAR",
        "ean": "5903364323288",
        "weight_gram": 0,
        "box_length_cm": 0,
        "box_width_cm": 0,
        "box_height_cm": 0,
        "oe_codes": "saved to db from tecdoc",
        "car_compatibilities": "saved to db from tecdoc",
        "link": null,
        "comment": null,
        "published_to_ebay_de": false,
        "sold_in_general": 0
    }];

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            document.getElementById('response').innerHTML = `<p>Успешный ответ: ${JSON.stringify(result)}</p>`;
        } catch (error) {
            document.getElementById('response').innerHTML = `<p>Ошибка: ${error.message}</p>`;
        }
    });
</script>
</body>
</html>
