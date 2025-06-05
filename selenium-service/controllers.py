from fastapi import Request, HTTPException, Query
from fastapi.responses import JSONResponse
import os
from dotenv import load_dotenv
from datetime import datetime
import re
from selenium import webdriver
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.common.by import By
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
            login_input.clear()
            login_input.send_keys(self.username)

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

    def searchProductAndGetInfo(self, productReference):
        wait = WebDriverWait(self.driver, 2)

        login_input = wait.until(lambda d: d.find_element(By.CSS_SELECTOR, "#SmartSearchInput"))
        login_input.clear()
        login_input.send_keys(productReference)

        search_btn = wait.until(lambda d: d.find_element(By.CSS_SELECTOR, '#BodyContentPlaceHolder_SmartSearchBar_SmartSearchButton'))

        old_url = self.driver.current_url
        search_btn.click()
        wait.until(lambda d: d.current_url != old_url)

        print(productReference)

        product_items = self.driver.find_elements(By.CSS_SELECTOR, '.flex-products .flex-item')

        result = {}

        product_variants = []

        for item in product_items:
            try:
                code_elem = item.find_elements(By.CSS_SELECTOR, ".flex-col2 .flex-tecdoc-numbers .flex-tecdoc-number")
                if code_elem:
                    code_elem = code_elem[0]
                    html_elem = code_elem.get_attribute("innerHTML")
                    code = html_elem.split("</span>")[-1].strip()

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
                            stock = main_stock[0].text.strip()
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