<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ImportController extends Controller
{
    public function xlsx(): View
    {
        return view('crm.imports.xlsx', [
            'steps' => [
                'Upload XLSX file',
                'Validate required columns and row values',
                'Preview detected companies / duplicates',
                'Confirm import',
                'Store import log and row-level errors',
            ],
            'requiredColumns' => [
                'company_name',
                'ico',
                'website',
                'contact_name',
                'phone',
                'email',
                'note',
            ],
        ]);
    }
}
