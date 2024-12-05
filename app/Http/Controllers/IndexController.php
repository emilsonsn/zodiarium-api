<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function success(){
        return view('success');
    }
    public function cancel(){
        return view('cancel');
    }
}
