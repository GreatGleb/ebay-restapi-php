from fastapi import Request, HTTPException, Query
from fastapi.responses import JSONResponse
import os
from dotenv import load_dotenv

load_dotenv('/.env')

class Controller:
    def __init__(self):
        self.supplier_website = os.getenv('AUTOPARTNER_WEBSITE')
        self.username = os.getenv('AUTOPARTNER_WEBSITE_LOGIN')
        self.password = os.getenv('AUTOPARTNER_WEBSITE_PASSWORD')

    async def test(self):
        return self.supplier_website

controller = Controller()