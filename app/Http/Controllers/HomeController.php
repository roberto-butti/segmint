<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('public.home', [
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    }
}
