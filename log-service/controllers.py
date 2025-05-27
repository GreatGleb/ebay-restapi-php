from fastapi import HTTPException
import os
from dotenv import load_dotenv

class TestController:
    @staticmethod
    async def get_test_data():
        """
        Тестовый эндпоинт, возвращающий приветственное сообщение
        """
        try:
            return {"message": "Привет, это тестовый маршрут FastAPI!"}
        except Exception as e:
            raise HTTPException(status_code=500, detail=str(e))

test_controller = TestController()