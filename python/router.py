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

router.add_api_route(
    "/categories/save_to_db_from_google_sheets",
    sheets_controller.categories_save_to_db_from_google_sheets,
    methods=["GET"],
    tags=["Google Sheet"],
    summary="Get Categories Google Sheet"
)

router.add_api_route(
    "/products/update_categories_in_google_sheets",
    sheets_controller.products_update_categories_in_google_sheets,
    methods=["GET"],
    tags=["Google Sheet"],
    summary="Get Categories Google Sheet"
)

router.add_api_route(
    "/products/save_to_db_from_google_sheets",
    sheets_controller.products_save_to_db_from_google_sheets,
    methods=["GET"],
    tags=["Google Sheet"],
    summary="Update DB products from Google Sheet"
)

router.add_api_route(
    "/products/update_from_db_to_google_sheets",
    sheets_controller.products_update_from_db_to_google_sheets,
    methods=["GET"],
    tags=["Google Sheet"],
    summary="Get Categories Google Sheet"
)