from fastapi import APIRouter
from controllers import test_controller
from google_sheets.controller import sheets_controller

router = APIRouter()

router.add_api_route(
    "/test",
    test_controller.get_test_data,
    methods=["GET"],
    tags=["Test"],
    summary="Тестовый маршрут"
)

router.add_api_route(
    "/sheets",
    sheets_controller.get_all_sheets,
    methods=["GET"],
    tags=["Google Sheets"],
    summary="Get all available Google Sheets"
)

router.add_api_route(
    "/sheet",
    sheets_controller.get_sheet,
    methods=["GET"],
    tags=["Google Sheet"],
    summary="Get Google Sheet"
)