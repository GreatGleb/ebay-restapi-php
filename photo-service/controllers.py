import os
import shutil
import subprocess
from pathlib import Path

import requests
from dotenv import load_dotenv
from fastapi import HTTPException, Query, Request
from fastapi.responses import JSONResponse
from urllib.parse import urlparse, parse_qs, urlencode, urlunparse

import imghdr

load_dotenv('/.env')

class Controller:
    def __init__(self):
        repo_token = os.getenv("GITHUB_PHOTOS_TOKEN")
        repo_url = os.getenv("GITHUB_PHOTOS_REPOSITORY_LINK")

        self.repo_access_url = 'https://' + repo_token + repo_url
        self.repo_dir = Path("/tmp/photo-repo")

        self.stable_api_key = os.getenv('TECDOC_KEY_RM')
        self.stable_provider_id = os.getenv('TECDOC_PROVIDER_ID_RM')

    def update_api_key(self, url: str, provider_id: str, new_api_key: str) -> str:
        parsed_url = urlparse(url)

        path_parts = parsed_url.path.strip("/").split("/")
        if len(path_parts) > 2:
            path_parts[2] = str(provider_id)  # 20888 → новый ID
        new_path = "/" + "/".join(path_parts)

        query_params = parse_qs(parsed_url.query)
        query_params['api_key'] = [new_api_key]

        new_query = urlencode(query_params, doseq=True)
        new_url = urlunparse(parsed_url._replace(path=new_path, query=new_query))

        return new_url

    def download_photo(self, url: str, download_dir: Path, filename: str) -> Path:
        download_dir.mkdir(parents=True, exist_ok=True)
        temp_path = download_dir / (filename + ".tmp")

        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
            "AppleWebKit/537.36 (KHTML, like Gecko) "
            "Chrome/114.0.0.0 Safari/537.36"
        }

        try:
            response = requests.get(url, headers=headers, stream=True)
            response.raise_for_status()
        except requests.exceptions.HTTPError as e:
            if response.status_code == 403:
                print(f"[403 Forbidden] Доступ запрещён. Возможно, нужен другой ключ или заголовки.")

                return 403, 403
        except requests.exceptions.RequestException as e:
            print(f"[RequestException] Failed to download {url}: {e}")
            return None, None

        with open(temp_path, "wb") as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)

        img_type = imghdr.what(temp_path)
        if not img_type:
            raise ValueError("Невозможно определить тип изображения")

        final_name = f"{filename}.{img_type}"

        final_path = download_dir / final_name
        temp_path.rename(final_path)

        return final_name, final_path

    def set_git_connection(self):
        if not self.repo_dir.exists():
            subprocess.run(["git", "clone", "--depth", '1', self.repo_access_url, str(self.repo_dir)], check=True)

    def save_photo(self, url, filename):
        downloaded_photo, downloaded_path = self.download_photo(url, Path("/tmp/downloaded_photos"), filename)

        if downloaded_photo == 403:
            new_url = self.update_api_key(url, self.stable_provider_id, self.stable_api_key)
            downloaded_photo, downloaded_path = self.download_photo(new_url, Path("/tmp/downloaded_photos"), filename)

        if not downloaded_photo or downloaded_photo == 403:
            print(new_url)
            return False

        original_dir = self.repo_dir / "original"
        original_dir.mkdir(parents=True, exist_ok=True)
        final_path = original_dir / downloaded_photo

        shutil.move(str(downloaded_path), str(final_path))

        return downloaded_photo

    def git_commit_push(self):
        status_result = subprocess.run(
            ["git", "-C", str(self.repo_dir), "status", "--porcelain"],
            capture_output=True, text=True
        )

        if status_result.stdout.strip():
            subprocess.run(["git", "config", "--global", "user.name", os.getenv("GITHUB_PHOTOS_USERNAME")])
            subprocess.run(["git", "config", "--global", "user.email", os.getenv("GITHUB_PHOTOS_EMAIL")])
            subprocess.run(["git", "-C", str(self.repo_dir), "add", "."], check=True)
            subprocess.run(["git", "-C", str(self.repo_dir), "commit", "-m", "Auto upload photos"], check=True)

            subprocess.run(["git", "-C", str(self.repo_dir), "pull", "--rebase"], check=True)
            subprocess.run(["git", "-C", str(self.repo_dir), "push"], capture_output=True, text=True)

            result = True
        else:
            result = False

        return result

    def test(self):
        self.set_git_connection()

        url = 'http://webservice.tecalliance.services/pegasus-3-0/documents/20888/845520187112708/0?api_key=2BeBXg67uLkZ2w57dH3wKkXX2p2DJgygGuUPSN8htSo3dpM7qBAy'
        name = 'product4'

        photo = self.save_photo(url, name)

        result = self.git_commit_push()

        return photo

controller = Controller()