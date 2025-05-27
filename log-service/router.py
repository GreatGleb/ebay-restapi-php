from fastapi import APIRouter
from controllers import test_controller

router = APIRouter()

router.add_api_route(
    "/test",
    test_controller.get_test_data,
    methods=["GET"],
    tags=["Test"],
    summary="Тестовый маршрут"
)