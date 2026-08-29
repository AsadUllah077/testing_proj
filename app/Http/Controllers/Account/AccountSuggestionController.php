<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\AccountSuggestionService;
use Illuminate\Http\Request;

class AccountSuggestionController extends Controller
{
    /**
     * Return top-3 account suggestions based on description and/or vendor/customer history.
     *
     * GET /account/suggest-account?description=...&vendor_id=...&customer_id=...
     */
    public function suggest(Request $request, AccountSuggestionService $service)
    {
        $result = $service->suggest(
            description: $request->input('description'),
            vendorId: $request->integer('vendor_id') ?: null,
            customerId: $request->integer('customer_id') ?: null,
        );

        return response()->json($result);
    }
}
