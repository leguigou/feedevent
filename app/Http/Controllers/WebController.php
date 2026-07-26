<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;

class WebController extends Controller
{
    public function home()
    {
        $categories = Category::all();
        return view('home', compact('categories'));
    }

    public function calendar()
    {
        return view('calendar');
    }

    public function map()
    {
        return view('map');
    }
}
