<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UpdateProducts extends Controller
{
    public function run(Request $request) {
        var_dump($request->all());
        return 0;
    }
}
