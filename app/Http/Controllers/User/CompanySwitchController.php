<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CompanySwitchController extends Controller
{
    /**
     * Switch the current user's active company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function switch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Verify user has access to this company
        if (!$user->companies()->where('companies.id', $request->company_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this company.'
            ], 403);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The password you entered is incorrect.'
            ], 401);
        }

        // Update company_id
        $user->company_id = $request->company_id;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Company switched successfully!'
        ]);
    }
}
