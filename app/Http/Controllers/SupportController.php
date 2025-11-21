<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function help()
    {
        return view('support.help');
    }

    public function contact()
    {
        return view('support.contact');
    }

    public function privacy()
    {
        return view('support.privacy');
    }

    public function terms()
    {
        return view('support.terms');
    }
}