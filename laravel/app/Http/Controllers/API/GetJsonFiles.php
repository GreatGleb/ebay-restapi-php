<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

class GetJsonFiles extends Controller
{
    public function getTableSchema() {
        $filePath = database_path('schema/tables.json');

        if (!file_exists($filePath)) {
            return response()->json([
                'error' => 'Schema file not found'
            ], 404);
        }

        $jsonContent = file_get_contents($filePath);
        $jsonData = json_decode($jsonContent);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'error' => 'Invalid JSON format in schema file'
            ], 500);
        }

        return response()->json($jsonData);
    }
}
