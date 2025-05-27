from fastapi import Request, HTTPException, Query
from fastapi.responses import JSONResponse
import os
from dotenv import load_dotenv
from datetime import datetime
import re

load_dotenv('/.env')

class LogsController:
    def __init__(self):
        self.LOG_DIR = "/tmp/logs"
        os.makedirs(self.LOG_DIR, exist_ok=True)

    async def get_new_logs(self, trace_id: str = Query(..., description="Идентификатор трека (trace_id)")):
        log_file = os.path.join(self.LOG_DIR, f"{trace_id}.log")
        offset_file = os.path.join(self.LOG_DIR, f"{trace_id}.offset")

        if not os.path.exists(log_file):
            return JSONResponse(content={"status": "bad", "detail": f"Лог-файл {trace_id}.log не найден"}, status_code=200)

        try:
            offset = 0
            if os.path.exists(offset_file):
                with open(offset_file, "r") as f:
                    offset = int(f.read().strip() or 0)

            with open(log_file, "r", encoding="utf-8") as f:
                f.seek(offset)
                new_logs = f.read()
                offset = f.tell()

            with open(offset_file, "w") as f:
                f.write(str(offset))

            pattern = re.compile(r"\[(?P<date>[^\]]+)\] \[(?P<source>[^\]]+)\] \[indent=(?P<indent>\d+)\]\s+(?P<message>.+)")
            result = []
            for line in new_logs.strip().splitlines():
                m = pattern.match(line)
                if m:
                    result.append({
                        "date": m.group("date"),
                        "source": m.group("source"),
                        "indent": int(m.group("indent")),
                        "message": m.group("message")
                    })
                else:
                    result.append({"raw": line})

            return JSONResponse(content={"logs": result}, status_code=200)

        except Exception as e:
            raise HTTPException(status_code=500, detail=f"Ошибка при чтении логов: {str(e)}")

    async def log_data(self, request: Request):
        try:
            data = await request.json()

            trace_id = data.get("trace_id")
            source = data.get("source")
            message = data.get("message")
            indent = data.get("indent", 0)

            if not all([trace_id, source, message]):
                raise HTTPException(status_code=400, detail="trace_id, source, and message are required")

            timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
            indent_str = "  " * int(indent)
            log_line = f"[{timestamp}] [{source}] [indent={indent}] {indent_str}{message}\n"

            log_path = os.path.join(self.LOG_DIR, f"{trace_id}.log")
            with open(log_path, "a", encoding="utf-8") as f:
                f.write(log_line)

            return JSONResponse(content={"status": "success"}, status_code=200)

        except Exception as e:
            raise HTTPException(status_code=500, detail=f"Logging failed: {str(e)}")

logs_controller = LogsController()