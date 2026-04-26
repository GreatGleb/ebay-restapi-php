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
    const data = [
        {
            "id": "1",
            "reference": "82-1127",
            "tecdoc_number": "82-1127",
            "retail_price_net": 49.15,
            "retail_price_gross": 60.46,
            "supplier_price_net": 37.81,
            "supplier_price_gross": 46.51,
            "stock_quantity_pl": 10,
            "stock_quantity_pruszkow": 3,
            "name_original_pl": "ZACISK HAM. MAZDA T. 3 1,5-2,2 D 13- LE",
            "ru_category_from_ebay_de": null,
            "category_id_ebay_de": "33563",
            "installation_position_ru": "Задний мост слева",
            "installation_position_en": "Rear Axle Left",
            "installation_position_de": "Hinterachse links",
            "specifics_ru": "парные номера артикулов - 82-1128,\nСторона установки - Задний мост слева,\nСторона установки - за мостом,\nограничение производителя - ATE,\nТип тормозного суппорта - Тормозной суппорт со встр. стояночной системой,\nТип тормозного суппорта - Тормозной суппорт (1-поршневый),\nдля тормозного диска толщиной [мм] - 9,\nТип тормозного диска - цельный,\nдля тормозного диска диаметром [мм] - 265,\nМатериал - алюминий,\nДиаметр поршня [мм] - 36",
            "specifics_en": "paired article number - 82-1128,\nFitting Position - Rear Axle Left,\nFitting Position - behind the axle,\nManufacturer Restriction - ATE,\nBrake Caliper Type - Brake Caliper with integrated parking brake,\nBrake Caliper Type - Brake Caliper (1 piston),\nfor brake disc thickness [mm] - 9,\nBrake Disc Type - solid,\nfor brake disc diameter [mm] - 265,\nMaterial - Aluminium,\nPiston Diameter [mm] - 36",
            "specifics_de": "paarige Artikelnummer - 82-1128,\nEinbauposition - Hinterachse links,\nEinbauposition - hinter der Achse,\nHerstellereinschränkung - ATE,\nBremssattelausführung - Bremssattel m. integr. Feststellbremse,\nBremssattelausführung - Bremssattel (1-Kolben),\nfür Bremsscheibendicke [mm] - 9,\nBremsscheibenart - voll,\nfür Bremsscheibendurchmesser [mm] - 265,\nMaterial - Aluminium,\nKolbendurchmesser [mm] - 36",
            "product_type_ru": "Тормозной суппорт",
            "product_type_en": "Brake Caliper",
            "product_type_de": "Bremssattel",
            "part_of_ebay_de_name_product_type": "Bremssattel Hinten Links für",
            "part_of_ebay_name_for_cars": "MAZDA 3 2013-",
            "ebay_name_de": "Bremssattel Hinten Links für MAZDA 3 2013-",
            "photos": [
                {
                    "product_id": "1",
                    "original_photo_url": "saved to db from tecdoc"
                }
            ],
            "photo": "=IMAGE(\"None\")",
            "ebay_similar_products_name": "2X MAXGEAR BREMSSATTEL HINTER DER ACHSE HINTEN PASSEND FÜR MAZDA 3\nMAXGEAR 82-1127 Bremssattel Hinten Links für MAZDA 3 BN BM\nMAXGEAR Bremssattel 82-1127 Aluminium für MAZDA BM BN BMLFP BMLFS BM4 BN4 BM6FJ\nMAXGEAR BREMSSATTEL HINTEN LINKS PASSEND FÜR MAZDA 3 | 82-1127\nMaxgear 82-1127 Bremssattel Hinten Links Hinter Der Achse für Mazda 3 BM BN\nBremssattel Hinterachse links für MAZDA 3 Stufenheck\nBremssattel hinten links für MAZDA\nMaxgear Bremssattel hinten links für MAZDA\nBremssattel MAXGEAR 82-1127 Aluminium für MAZDA BM BN BMLFP BMLFS BM4 BN4 BM6FJ\nBremssattel hinten links Bremssystem für Mazda 3 2.0 1.5 BM BN",
            "ebay_similar_products_photo": "=IMAGE(\"None\")",
            "has_hologram": false,
            "no_photo": false,
            "supplier": "AutoPartner",
            "producer_brand": "MAXGEAR",
            "ean": "5903766337531",
            "oe_codes": "56044672AA / 053013554AF / 02140-1110 / 535 0174 10 / EVR58621 / 056044672AA TC1544",
            "car_compatibilities": "saved to db from tecdoc",
            "link": "https://www.apnext.eu/pl/wyszukiwanie/1/1/82-1127/zacisk-ham-mazda-t-3-15-22-d-13-le/5864423",
            "published_to_ebay_de": false
        },
        {
            "id": "2",
            "reference": "MSE0215",
            "tecdoc_number": "1148000169",
            "retail_price_net": 34.94,
            "retail_price_gross": 42.98,
            "supplier_price_net": 26.88,
            "supplier_price_gross": 33.06,
            "stock_quantity_pl": 0,
            "stock_quantity_pruszkow": 0,
            "name_original_pl": "CZUJNIK TEMP. SPALIN VW POLO 1,2-1,6 TDI 09-",
            "ru_category_from_ebay_de": null,
            "category_id_ebay_de": "33578",
            "installation_position_ru": null,
            "installation_position_en": null,
            "installation_position_de": null,
            "specifics_ru": "Количество втычных контактов - 2,\nДлина кабеля [мм] - 520,\nТип датчика - PTC-Датчик",
            "specifics_en": "Number of pins - 2,\nCable Length [mm] - 520,\nSensor Type - PTC",
            "specifics_de": "Anzahl der Steckkontakte - 2,\nKabellänge [mm] - 520,\nSensorart - PTC-Sensor",
            "product_type_ru": "Датчик, температура выхлопных газов",
            "product_type_en": "Sensor, exhaust gas temperature",
            "product_type_de": "Sensor, Abgastemperatur",
            "part_of_ebay_de_name_product_type": "Sensor Abgastemperatur für",
            "part_of_ebay_name_for_cars": "VW POLO 2009-",
            "ebay_name_de": "Sensor Abgastemperatur für VW POLO 2009-",
            "photos": [
                {
                    "product_id": "2",
                    "original_photo_url": "saved to github with logo"
                }
            ],
            "photo": "=IMAGE(\"None\")",
            "ebay_similar_products_name": "MEYLE Abgastemperatursensor für VW POLO V AUDI A1 8X SEAT IBIZA IV 1.2/1.6 TDI\nMEYLE Sensor Abgastemperatur 114 800 0169 für VW SEAT AUDI SKODA POLO 5 6R1 6C1\nSensor Abgastemperatur MEYLE 114 800 0169 für SKODA AUDI SEAT VW POLO 5 6R1 6C1\nMEYLE Sensor, Abgastemperatur  u.a. für AUDI, SEAT, SKODA, VW\n1x Sensor, Abgastemperatur MEYLE 114 800 0169 passend für AUDI SEAT SKODA VW\n114 800 0169 MEYLE Sensor, Abgastemperatur MEYLE-ORIGINAL: True to OE.",
            "ebay_similar_products_photo": "=IMAGE(\"None\")",
            "has_hologram": false,
            "no_photo": false,
            "supplier": "AutoPartner",
            "producer_brand": "MEYLE",
            "ean": "4040074266861",
            "oe_codes": "saved to db from tecdoc",
            "car_compatibilities": "saved to db from tecdoc",
            "link": "https://www.apnext.eu/pl/wyszukiwanie/1/1/1148000169/czujnik-temp-spalin%C2%A0vw-polo-12-16-tdi-09/8912777",
            "published_to_ebay_de": false
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
