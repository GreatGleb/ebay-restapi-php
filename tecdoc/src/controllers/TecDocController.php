<?php

namespace Great\Tecdoc\Controllers;

class TecDocController
{
    public function index()
    {
        var_dump($_ENV);
        var_dump(getenv('GOOGLE_SHEETS_ID_PRODUCTS'));
        echo "Привет из Tecdoc!";
    }

    public function show($name)
    {
        echo "Привет, $name!";
    }
}