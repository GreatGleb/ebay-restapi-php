from fastapi import APIRouter
from controllers import controller

router = APIRouter()

router.add_api_route(
    "/get_api_key",
    controller.get_api_key,
    methods=["GET"],
    tags=["Google Sheets"],
    summary="Get all available Google Sheets"
)