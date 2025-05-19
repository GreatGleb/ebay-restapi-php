from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
from googleapiclient.discovery import build
from pathlib import Path
import os
import pickle
import json

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
        # Get the directory where this class is located
        self.base_dir = Path(__file__).parent

        # Set default credential file paths
        self.token_file = str(self.base_dir /  'tokens' /'token.json')
        self.credentials_file = str(self.base_dir /  'tokens' /'credentials.json')
        self.sheets_service = self._authenticate_sheets()
        self.drive_service = self._authenticate_drive()
        self.creds = self._get_credentials()

    def _authenticate_sheets(self):
        """Authenticate for Google Sheets API."""
        creds = self._get_credentials()
        return build('sheets', 'v4', credentials=creds)

    def _authenticate_drive(self):
        """Authenticate for Google Drive API."""
        creds = self._get_credentials()
        return build('drive', 'v3', credentials=creds)

    def _get_credentials(self):
        with open(self.credentials_file) as f:
            client_config = json.load(f).get('installed', {})

        if os.path.exists(self.token_file):
            try:
                with open(self.token_file) as f:
                    token_data = json.load(f)
                    return Credentials(
                        token=token_data.get('token'),
                        refresh_token=token_data.get('refresh_token'),
                        token_uri='https://oauth2.googleapis.com/token',
                        client_id=client_config.get('client_id'),
                        client_secret=client_config.get('client_secret'),
                        scopes=self.SCOPES
                    )
            except Exception as e:
                print(f"Error loading Google Sheets token: {e}")

        flow = InstalledAppFlow.from_client_secrets_file(
            self.credentials_file,
            self.SCOPES,
            redirect_uri="urn:ietf:wg:oauth:2.0:oob"
        )

        auth_url, _ = flow.authorization_url()
        print("Please visit:", auth_url)
        code = os.getenv('GOOGLE_TOKEN_CODE')
        if not code:
            raise ValueError("GOOGLE_TOKEN_CODE environment variable not set")

        token_data = flow.fetch_token(code=code)
        creds = Credentials(
            token=token_data['access_token'],
            refresh_token=token_data.get('refresh_token'),
            token_uri=token_data['token_uri'],
            client_id=client_config.get('client_id'),
            client_secret=client_config.get('client_secret'),
            scopes=token_data.get('scope', '').split()
        )

        with open(self.token_file, 'w') as f:
            json.dump({
                'token': creds.token,
                'refresh_token': creds.refresh_token,
                'client_id': creds.client_id,
                'client_secret': creds.client_secret,
                'scopes': creds.scopes,
                'expiry': creds.expiry.isoformat() if creds.expiry else None
            }, f, indent=2)

        return creds