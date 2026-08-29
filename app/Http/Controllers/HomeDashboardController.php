<?php

namespace App\Http\Controllers;

use App\Models\Employee;

class HomeDashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $employees = Employee::where('company_id', $companyId);
        $employeeStats = [
            'total' => (clone $employees)->count(),
            'active' => (clone $employees)->where('status', 'active')->count(),
            'inactive' => (clone $employees)->where('status', 'inactive')->count(),
            'resigned' => (clone $employees)->where('status', 'resigned')->count(),
            'terminated' => (clone $employees)->where('status', 'terminated')->count(),
        ];

        return view('pages.home.dashboard', compact('employeeStats'));
    }
}
