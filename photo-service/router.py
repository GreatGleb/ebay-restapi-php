from fastapi import APIRouter
from controllers import controller

router = APIRouter()

router.add_api_route(
    "/test",
    controller.test,
    methods=["GET"],
    tags=["Test"],
    summary="Test"
)

router.add_api_route(
    "/add",
    controller.save_photo_from_request,
    methods=["POST"],
    tags=["Upload photos"],
    summary="Upload photos"
)