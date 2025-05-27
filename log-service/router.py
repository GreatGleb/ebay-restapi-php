from fastapi import APIRouter
from controllers import logs_controller

router = APIRouter()

router.add_api_route(
    "/get",
    logs_controller.get_new_logs,
    methods=["GET"],
    tags=["Google Sheets"],
    summary="Get all available Google Sheets"
)

router.add_api_route(
    "/add",
    logs_controller.log_data,
    methods=["POST"],
    tags=["Logging"],
    summary="Log custom data from JSON POST"
)