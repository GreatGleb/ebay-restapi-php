from fastapi import Request, HTTPException, Query
from fastapi.responses import JSONResponse
import os
from dotenv import load_dotenv
from datetime import datetime
import re
from selenium import webdriver
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.chrome.options import Options
import asyncio
from urllib.parse import urlparse, parse_qs
import time

load_dotenv('/.env')

class Controller:
    def __init__(self):
        self.supplier_website = os.getenv('AUTOPARTNER_WEBSITE')
        self.username = os.getenv('AUTOPARTNER_WEBSITE_LOGIN')
        self.password = os.getenv('AUTOPARTNER_WEBSITE_PASSWORD')

    async def set_driver(self):
        options = Options()
        options.add_argument("--headless")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")

        self.driver = webdriver.Remote(
            command_executor='http://selenium:4444/wd/hub',
            options=options
        )

        return self.driver

    async def login(self):
        successLogin = False

        try:
            self.driver.get(self.supplier_website)

            wait = WebDriverWait(self.driver, 10)

            login_input = wait.until(lambda d: d.find_element(By.NAME, "LoginName"))

            if login_input:
                login_input.clear()
                login_input.send_keys(self.username)
            else:
                print("Error: Could not find the login input field.")

            password_input = wait.until(lambda d: d.find_element(By.NAME, "Password"))
            password_input.clear()
            password_input.send_keys(self.password)

            login_url = self.driver.current_url

            submit_btn = wait.until(lambda d: d.find_element(By.CSS_SELECTOR, 'input[type="submit"]'))
            submit_btn.click()

            wait.until(lambda d: d.current_url != login_url)

            successLogin = True
        except Exception as e:
            print(f"Произошла ошибка login: {e}")

        return successLogin

    async def get_photo_link(self):
        img_src = False

        try:
            pageUrl = self.supplier_website + 'pl/wyszukiwanie/1/1/72-5275/wahacz-db-p-vito-viano-03-le-hd/7536591'
            self.driver.get(pageUrl)

            wait = WebDriverWait(self.driver, 10)

            img_element = wait.until(lambda d: d.find_element(By.XPATH, "//img[contains(@src, 'webservice.tecalliance.services')]"))
            img_src = img_element.get_attribute("src")

            successLogin = True
        except Exception as e:
            print(f"Произошла ошибка: {e}")

        return img_src

    async def parse_key(self, photo_link):
        parsed_url = urlparse(photo_link)

        path_parts = parsed_url.path.split('/')
        providerId = path_parts[3]

        query_params = parse_qs(parsed_url.query)
        key = query_params.get('api_key', [None])[0]

        return providerId, key

    def parseProductPageLayout(self, productReference):
        wait = WebDriverWait(self.driver, 7)

        productReference = productReference.strip().replace(" ", "")

        # 3. Парсинг страницы товара
        try:
            container = wait.until(EC.presence_of_element_located((By.CLASS_NAME, "flex-general")))

            # --- Проверка артикула (без очистки символов) ---
            site_code_elem = container.find_elements(By.CSS_SELECTOR, ".flex-tecdoc-number .flex-value")
            if site_code_elem:
                site_code = site_code_elem[0].text.strip().replace(" ", "")

                # Прямое сравнение без удаления тире
                if site_code != productReference.strip():
                    print(f"Артикул не совпал: ожидалось '{productReference}', на сайте '{site_code}'")
                    return {}
            else:
                return {}

            result = {
                'link': self.driver.current_url,
                'name': container.find_element(By.CLASS_NAME, "flex-title").text.strip(),
                'brand_id': None
            }

            # --- Парсинг brand_id из аргументов onclick ---
            # Ищем вкладку 'Parametry', в которой вшит нужный ID
            params_tab = container.find_elements(By.CSS_SELECTOR, ".flex-tab.flex-params")
            if params_tab:
                onclick_val = params_tab[0].get_attribute("onclick")
                # Регулярка достает значения в одинарных кавычках: '7652921', '260019', '403'...
                matches = re.findall(r"'(.*?)'", onclick_val)
                if len(matches) >= 4:
                    result['brand_id'] = matches[3] # Четвертый аргумент - наш tecdoc brand id

            # --- Цена (Закупка без VAT) ---
            price_elem = container.find_elements(By.CLASS_NAME, "flex-price")
            if price_elem:
                price_raw = price_elem[0].get_attribute("data-flex-purchase-price")
                result['price'] = float(price_raw.strip().replace(",", "."))

            # --- Склады ---
            main_stock = container.find_elements(By.CSS_SELECTOR, ".flex-main-stock .Amount strong")
            result['main_stock'] = int(''.join(filter(str.isdigit, main_stock[0].text)) or 0) if main_stock else 0

            dist_stock = container.find_elements(By.CSS_SELECTOR, ".flex-distribution-stock .Amount strong")
            result['second_stock'] = int(''.join(filter(str.isdigit, dist_stock[0].text)) or 0) if dist_stock else 0

            return result

        except Exception as e:
            print(f"Ошибка парсинга {productReference}: {e}")
            return {}

    def parseSearchListPage(self, productReference):
        product_items = self.driver.find_elements(By.CSS_SELECTOR, '.flex-products .flex-item')

        result = {}

        product_variants = []

        for item in product_items:
            try:
                code_elem = item.find_elements(By.CSS_SELECTOR, ".flex-col2 .flex-tecdoc-numbers .flex-tecdoc-number")
                if code_elem:
                    code_elem = code_elem[0]
                    html_elem = code_elem.get_attribute("innerHTML")
                    code = html_elem.split("</span>")[-1].strip().replace(" ", "")
                    productReference = productReference.strip().replace(" ", "")

                    if code == productReference:
                        product_variant = {}
                        links = item.find_elements(By.CSS_SELECTOR, 'a')
                        if links:
                            product_variant['link'] = links[0].get_attribute("href")

                        name = item.find_elements(By.CSS_SELECTOR, '.flex-description')
                        if name:
                            product_variant['name'] = name[0].text.strip()

                        product_variant['brand_id'] = item.get_attribute('data-flex-tecdoc-brand-id')

                        main_stock = item.find_elements(By.CSS_SELECTOR, '.flex-stocks .flex-main-stock .flex-items-count')
                        if main_stock:
                            stock = main_stock[0].text.strip()
                            stock = ''.join(filter(str.isdigit, stock))
                            product_variant['main_stock'] = int(stock)

                        second_stock = item.find_elements(By.CSS_SELECTOR, '.flex-stocks .flex-distribution-stock .flex-items-count')
                        if second_stock:
                            stock = second_stock[0].text.strip()
                            stock = ''.join(filter(str.isdigit, stock))
                            product_variant['second_stock'] = int(stock)

                        price = item.find_elements(By.CSS_SELECTOR, '.flex-price')
                        if price:
                            price = price[0]
                            price = price.get_attribute('data-flex-purchase-price')
                            price = float(price.strip().replace(",", "."))

                            additional_price = item.find_elements(By.CSS_SELECTOR, '.flex-surcharges .flex-surcharge-price')
                            if additional_price:
                                additional_price = additional_price[0]
                                additional_price = additional_price.text.strip()
                                match = re.search(r"\d+,\d+", additional_price)
                                if match:
                                    additional_price = match.group()
                                    additional_price = float(additional_price.strip().replace(",", "."))

                                    price = price + additional_price

                            product_variant['price'] = price
                        product_variants.append(product_variant)
            except:
                print('continue')
                continue

        if product_variants:
            result = product_variants[0]

        for variant in product_variants:
            if variant['brand_id'] == '403':
                result = variant

        return result

    def searchProductAndGetInfo(self, productReference):
        wait = WebDriverWait(self.driver, 5)

        # 1. Вводим текст
        search_input = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "#SmartSearchInput")))
        search_input.clear()
        search_input.send_keys(productReference)

        try:
            # 2. Пробуем найти подсказку (даем ей 2 секунды)
            hint_wait = WebDriverWait(self.driver, 2)
            suggestion = hint_wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, ".flex-smart-search-whisperer .flex-product-by-number")))

            old_url = self.driver.current_url
            suggestion.click()
            wait.until(lambda d: d.current_url != old_url)

            # Если кликнули по подсказке — парсим страницу ТОВАРА (твой новый код)
            return self.parseProductPageLayout(productReference)
        except:
            # 3. Если подсказка не вышла — жмем кнопку поиска (твой старый код)
            print("Подсказка не найдена, идем через общий поиск...")

        try:
            search_btn = self.driver.find_element(By.CSS_SELECTOR, '#BodyContentPlaceHolder_SmartSearchBar_SmartSearchButton')
            old_url = self.driver.current_url
            search_btn.click()
            wait.until(lambda d: d.current_url != old_url)

            return self.parseSearchListPage(productReference)
        except Exception as e:
            print(f"Ошибка при поиске: {e}")
            return {}

    async def products(self, request: Request):
        await self.set_driver()

        successLogin = await self.login()

        if not successLogin:
            return False

        items = await request.json()

        for item in items:
            item['parsed'] = self.searchProductAndGetInfo(item['reference'])

        data = {}
        data['result'] = True
        data['items'] = items

        self.driver.quit()

        return data

    async def get_api_key(self):
        await self.set_driver()

        successLogin = await self.login()

        if not successLogin:
            return False

        photo_link = await self.get_photo_link()

        if not photo_link:
            return False

        providerId, key = await self.parse_key(photo_link)

        self.driver.quit()

        return {'providerId': providerId , 'key': key}

controller = Controller()