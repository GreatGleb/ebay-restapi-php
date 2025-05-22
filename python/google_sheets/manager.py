from typing import Dict, List, Union, Any
import os
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
import json
from .connection import GoogleSheetsConnection

class GoogleSheetsManager:
    """Manager for working with Google Sheets and Drive APIs."""

    def __init__(self):
        connection = GoogleSheetsConnection()

        self.drive_service = connection.drive_service
        self.sheets_service = connection.sheets_service

    def list_all_spreadsheets(self) -> List[Dict[str, str]]:
        """
        Retrieve all Google Sheets files from user's Drive.

        Returns:
            List of dictionaries with:
            - 'id': The spreadsheet ID
            - 'name': The spreadsheet name
            - 'createdTime': Creation timestamp
            - 'modifiedTime': Last modified timestamp
        """
        try:
            results = self.drive_service.files().list(
                q="mimeType='application/vnd.google-apps.spreadsheet'",
                fields="files(id, name, createdTime, modifiedTime)"
            ).execute()

            return results.get('files', [])

        except Exception as e:
            print(f"Error listing spreadsheets: {e}")
            return []

    def get_spreadsheet_by_id(self, spreadsheet_id: str) -> Dict[str, Union[str, Dict]]:
        """
        Retrieve a specific Google Sheets file by its ID.

        Args:
            spreadsheet_id: The ID of the spreadsheet to retrieve

        Returns:
            Dictionary containing:
            - 'id': The spreadsheet ID
            - 'name': The spreadsheet name
            - 'createdTime': Creation timestamp
            - 'modifiedTime': Last modified timestamp
            - 'sheets': List of sheet properties (names, IDs, etc.)

        Raises:
            HttpError: If the spreadsheet is not found or access is denied
        """
        try:
            # Get basic file metadata from Drive API
            file_metadata = self.drive_service.files().get(
                fileId=spreadsheet_id,
                fields="id, name, createdTime, modifiedTime"
            ).execute()

            # Get sheet details from Sheets API
            sheet_metadata = self.sheets_service.spreadsheets().get(
                spreadsheetId=spreadsheet_id,
                fields="sheets(properties(sheetId,title,index))"
            ).execute()

            sheets_with_data = []
            for sheet in sheet_metadata.get('sheets', []):
                sheet_title = sheet['properties']['title']

                data = self.sheets_service.spreadsheets().values().get(
                    spreadsheetId=spreadsheet_id,
                    range=sheet_title
                ).execute()

                sheets_with_data.append({
                    "properties": sheet['properties'],
                    "data": data.get('values', [])
                })

            return {
                **file_metadata,
                "sheets": sheets_with_data
            }
        except Exception as e:
            print(f"Unexpected error: {e}")
            raise

    def write_to_cell(self, spreadsheet_id: str, sheet_name: str, cell: str, value: Any) -> bool:
        try:
            range_notation = f"{sheet_name}!{cell}"
            body = {
                'values': [[value]]
            }

            self.sheets_service.spreadsheets().values().update(
                spreadsheetId=spreadsheet_id,
                range=range_notation,
                valueInputOption='USER_ENTERED',
                body=body
            ).execute()

            return True

        except Exception as e:
            print(f"Error while Google Sheets recording to cell  {cell}: {e}")
            return False

    def write_to_cells(self, spreadsheet_id: str, body: Dict) -> bool:
        try:
            body['valueInputOption'] = 'USER_ENTERED'

            self.sheets_service.spreadsheets().values().batchUpdate(
                spreadsheetId=spreadsheet_id,
                body=body
            ).execute()

            return True

        except Exception as e:
            print(f"Error while Google Sheets recording data: {e}")
            return False