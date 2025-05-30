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