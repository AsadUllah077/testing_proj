<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('pages.projects.dashboard');
    }
}
