from fastapi import FastAPI
from router import router
from dotenv import load_dotenv
import uvicorn

app = FastAPI(title="API Example")
app.include_router(router)

if __name__ == "__main__":
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)