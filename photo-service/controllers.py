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
from PIL import Image, ImageFilter

load_dotenv('/.env')

class Controller:
    def __init__(self):
        repo_token = os.getenv("GITHUB_PHOTOS_TOKEN")
        repo_url = os.getenv("GITHUB_PHOTOS_REPOSITORY_LINK")

        self.repo_access_url = 'https://' + repo_token + repo_url
        self.repo_dir = Path("/tmp/photo-repo")

        self.stable_api_key = os.getenv('TECDOC_KEY_RM')
        self.stable_provider_id = os.getenv('TECDOC_PROVIDER_ID_RM')

    def delete_photo_dir(self):
        subprocess.run(["rm", "-rf", str(self.repo_dir)], check=True)

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
            return None, None
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
            return False

        original_dir = self.repo_dir / "original"
        original_dir.mkdir(parents=True, exist_ok=True)
        final_path = original_dir / downloaded_photo

        shutil.move(str(downloaded_path), str(final_path))

        return {
            'file_name': downloaded_photo,
            'file_path': str(final_path)
        }

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

            print('saved')
            result = True
        else:
            print(status_result)
            print('not saved')
            result = False

        return result

    def add_logo_to_image(self, image_path, image_name):
        try:
            logo_path = "logo/logo.png"

            logo = Image.open(logo_path)
            img = Image.open(image_path)

            img_width = img.width
            img_height = img.height

            logo_x_x = img_width - int(img_width/12)
            logo_x_y = int(img_height/12)

            size = img.width

            new_size = int(size/8)
            logo = logo.resize((new_size, new_size), Image.Resampling.LANCZOS)

            new_img = Image.new("RGB", (img.width, img.height))
            new_img.paste(img, (0, 0))
            new_img.paste(logo, (logo_x_y, logo_x_y), logo)

            branded_dir = self.repo_dir / 'branded'
            branded_dir.mkdir(parents=True, exist_ok=True)
            final_path = branded_dir / image_name

            new_img.save(final_path)

            return final_path.exists()
        except Exception as e:
            print(f"Ошибка при добавлении логотипа: {e}")
            return False

    def test(self):
#         self.delete_photo_dir()
        self.set_git_connection()

        url = 'http://webservice.tecalliance.services/pegasus-3-0/documents/20888/845520187112708/0?api_key=2BeBXg67uLkZ2w57dH3wKkXX2p2DJgygGuUPSN8htSo3dpM7qBAy'
        name = 'product5'

        photo = self.save_photo(url, name)
        print(photo)

        self.add_logo_to_image(photo['file_path'], photo['file_name'])

        result = self.git_commit_push()

        return result

    async def save_photo_from_request(self, request: Request):
        self.delete_photo_dir()
        self.set_git_connection()

        items = await request.json()

        for item in items:
            photo = self.save_photo(item['url'], item['name'])
            saved_with_logo = self.add_logo_to_image(photo['file_path'], photo['file_name'])

            item['original_photo_url'] = '/original/' + photo['file_name']
            item['cortexparts_photo_url'] = '/branded/' + photo['file_name']
            item['saved_with_logo'] = saved_with_logo

        result = self.git_commit_push()

        data = {}
        data['result'] = result
        data['items'] = items

        return data

controller = Controller()