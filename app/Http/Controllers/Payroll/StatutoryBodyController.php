<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\StatutoryBody;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StatutoryBodyController extends Controller
{
    public function index(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $query = StatutoryBody::where('company_id', $companyId);

        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'ILIKE', '%' . $request->search . '%')
                    ->orWhere('code', 'ILIKE', '%' . $request->search . '%');
            });
        }

        $bodies = $query->orderBy('name')->paginate($request->input('per_page', 20))->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $bodies
            ]);
        }

        return view('pages.payroll.statutory-body.index', compact('bodies'));
    }

    public function store(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('statutory_bodies', 'code')->where('company_id', $companyId),
            ],
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:3',
            'default_grace_days' => 'required|integer|min:0',
            'penalty_rate_per_day' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        StatutoryBody::create(array_merge($validator->validated(), [
            'company_id' => $companyId,
        ]));

        return response()->json(['success' => true, 'message' => 'Statutory Body created successfully']);
    }

    public function update(Request $request, $id)
    {
        $companyId = loggedCompanyId()['company_id'];
        $body = StatutoryBody::where('company_id', $companyId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('statutory_bodies', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($body->id),
            ],
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:3',
            'default_grace_days' => 'required|integer|min:0',
            'penalty_rate_per_day' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        $body->update($validator->validated());

        return response()->json(['success' => true, 'message' => 'Statutory Body updated successfully']);
    }

    public function destroy($id)
    {
        $body = StatutoryBody::where('company_id', loggedCompanyId()['company_id'])
            ->findOrFail($id);
        $body->delete();

        return response()->json(['success' => true, 'message' => 'Statutory Body deleted successfully']);
    }
}
