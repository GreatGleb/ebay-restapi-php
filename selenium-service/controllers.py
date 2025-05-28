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

            print(self.driver.title)

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

            title = self.driver.title
            print(title)

            img_element = wait.until(lambda d: d.find_element(By.XPATH, "//img[contains(@src, 'webservice.tecalliance.services')]"))
            img_src = img_element.get_attribute("src")

            print(img_src)

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