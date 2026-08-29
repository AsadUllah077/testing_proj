<?php

namespace App\Http\Controllers\QMS\DocumentManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QMSDocCategoryController extends Controller
{
    public function index()
    {
        return view('pages.qms.documentmanagement.qmsdoccategory.index');
    }
}
