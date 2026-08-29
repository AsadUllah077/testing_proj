<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EntityController extends Controller
{
    public function index(Request $request)
    {
        $entities = LogisticsEntity::query();
        $search = strtolower($request->search);

        if ($request->has('search') && $request->search != '') {
            $entities->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(business_legal_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(entity_type) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(contact_full_name) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(contact_email) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(tax_id_vat_number) LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($request->has('entity_type') && $request->entity_type != '') {
            $entities->where('entity_type', $request->entity_type);
        }

        if ($request->has('from_date') && $request->from_date != '') {
            $entities->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date != '') {
            $entities->whereDate('created_at', '<=', $request->to_date);
        }

        $entities = $entities->latest()->paginate(20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.logistics.entities.partials.table', compact('entities'))->render(),
                'pagination' => $entities->links()->toHtml(),
            ]);
        }

        return view('pages.products.logistics.entities.index', compact('entities'));
    }

    public function create()
    {
        return view('pages.products.logistics.entities.create');
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'business_legal_name' => 'required|string|max:255',
                'entity_type' => 'required|string|in:Importer,Exporter,Both',
                'tax_id_vat_number' => 'required|string|max:50',
                'registration_country' => 'required|string|max:100',

                'contact_full_name' => 'required|string|max:255',
                'contact_email' => 'required|email|max:255',
                'contact_phone_number' => 'required|string|max:20',

                'registered_street_address' => 'required|string|max:255',
                'registered_city' => 'required|string|max:100',
                'registered_state_province' => 'required|string|max:100',
                'registered_postal_code' => 'required|string|max:20',

                'mailing_street_address' => 'nullable|string|max:255',
                'mailing_city' => 'nullable|string|max:100',
                'mailing_state_province' => 'nullable|string|max:100',
                'mailing_postal_code' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all()
                ]);
            }

            LogisticsEntity::create($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entity registered successfully!',
                'redirect_url' => route('logistics.entities.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Entity registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        $entity = LogisticsEntity::findOrFail($id);
        return view('pages.products.logistics.entities.show', compact('entity'));
    }

    public function edit($id)
    {
        $entity = LogisticsEntity::findOrFail($id);
        return view('pages.products.logistics.entities.edit', compact('entity'));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'business_legal_name' => 'required|string|max:255',
                'entity_type' => 'required|string|in:Importer,Exporter,Both',
                'tax_id_vat_number' => 'required|string|max:50',
                'registration_country' => 'required|string|max:100',

                'contact_full_name' => 'required|string|max:255',
                'contact_email' => 'required|email|max:255',
                'contact_phone_number' => 'required|string|max:20',

                'registered_street_address' => 'required|string|max:255',
                'registered_city' => 'required|string|max:100',
                'registered_state_province' => 'required|string|max:100',
                'registered_postal_code' => 'required|string|max:20',

                'mailing_street_address' => 'nullable|string|max:255',
                'mailing_city' => 'nullable|string|max:100',
                'mailing_state_province' => 'nullable|string|max:100',
                'mailing_postal_code' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all()
                ]);
            }

            $entity = LogisticsEntity::findOrFail($id);
            $entity->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entity updated successfully!',
                'redirect_url' => route('logistics.entities.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Entity update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $entity = LogisticsEntity::findOrFail($id);
            $entity->delete();

            return response()->json([
                'success' => true,
                'message' => 'Entity deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting entity: ' . $e->getMessage()
            ], 500);
        }
    }
}
