from google.oauth2.service_account import Credentials
from googleapiclient.discovery import build
from pathlib import Path
import os

class GoogleSheetsConnection:
    SCOPES = [
         'https://www.googleapis.com/auth/spreadsheets',
         'https://www.googleapis.com/auth/drive.metadata.readonly'
     ]

    def __init__(self):
        """
        Initialize with authentication credentials.
        Automatically looks for credentials in the same directory as this class.
        """
        self.base_dir = Path(__file__).parent

        self.service_account_file = str(self.base_dir /  'tokens' /'service-account.json')
        self.creds = self._get_credentials()
        self.sheets_service = self._authenticate_sheets()
        self.drive_service = self._authenticate_drive()

    def _authenticate_sheets(self):
        """Authenticate for Google Sheets API."""
        return build('sheets', 'v4', credentials=self.creds)

    def _authenticate_drive(self):
        """Authenticate for Google Drive API."""
        return build('drive', 'v3', credentials=self.creds)

    def _get_credentials(self):
        if os.path.exists(self.service_account_file):
            try:
                creds = Credentials.from_service_account_file(
                    self.service_account_file,
                    scopes=self.SCOPES
                )
            except Exception as e:
                print(f"Error loading Google Sheets token: {e}")

        return creds