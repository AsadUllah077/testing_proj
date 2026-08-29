<?php

namespace App\Http\Controllers\QMS\Sop;

use App\Http\Controllers\Controller;
use App\Models\QmsSop;
use App\Models\QmsSopStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SopController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search     = $request->input('q');
            $status     = $request->input('status');
            $department = $request->input('department');
            $perPage    = $request->input('per_page', 20);

            $query = QmsSop::with(['owner'])
                ->where('company_id', Auth::user()->company_id)
                ->latest();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('sop_number', 'LIKE', "%{$search}%")
                      ->orWhere('department', 'LIKE', "%{$search}%");
                });
            }

            if (!empty($status)) {
                $query->where('status', $status);
            }

            if (!empty($department)) {
                $query->where('department', $department);
            }

            $sops = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            $stats = [
                'total'    => QmsSop::where('company_id', Auth::user()->company_id)->count(),
                'approved' => QmsSop::where('company_id', Auth::user()->company_id)->where('status', 'approved')->count(),
                'review'   => QmsSop::where('company_id', Auth::user()->company_id)->where('status', 'review')->count(),
                'draft'    => QmsSop::where('company_id', Auth::user()->company_id)->where('status', 'draft')->count(),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.qms.sop.partials.table', compact('sops'))->render(),
                ]);
            }

            return view('pages.qms.sop.index', compact('sops', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error fetching SOPs: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching SOPs.');
        }
    }

    public function create()
    {
        return view('pages.qms.sop.create');
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title'         => 'required|string|max:255',
                'department'    => 'nullable|string|max:255',
                'linked_product'=> 'nullable|string|max:255',
                'description'   => 'nullable|string',
                'status'        => 'required|in:draft,review,approved,archived',
                'steps'         => 'nullable|array',
                'steps.*.title' => 'required_with:steps|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();

            $sop = QmsSop::create([
                'title'          => $request->title,
                'description'    => $request->description,
                'department'     => $request->department,
                'linked_product' => $request->linked_product,
                'status'         => $request->status,
                'company_id'     => Auth::user()->company_id,
                'created_by'     => Auth::id(),
                'approved_at'    => $request->status === 'approved' ? now() : null,
            ]);

            if ($request->has('steps') && is_array($request->steps)) {
                foreach ($request->steps as $i => $step) {
                    if (!empty($step['title'])) {
                        QmsSopStep::create([
                            'sop_id'       => $sop->id,
                            'order'        => $i + 1,
                            'title'        => $step['title'],
                            'description'  => $step['description'] ?? null,
                            'has_checklist'=> isset($step['has_checklist']) ? true : false,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'SOP created successfully!', 'data' => $sop], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error creating SOP: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $sop = QmsSop::with('steps')->where('company_id', Auth::user()->company_id)->findOrFail($id);
            return view('pages.qms.sop.show', compact('sop'));
        } catch (\Exception $e) {
            return redirect()->route('qms.sop.index')->with('error', 'SOP not found.');
        }
    }

    public function edit($id)
    {
        try {
            $sop = QmsSop::with('steps')->where('company_id', Auth::user()->company_id)->findOrFail($id);
            return view('pages.qms.sop.edit', compact('sop'));
        } catch (\Exception $e) {
            return redirect()->route('qms.sop.index')->with('error', 'SOP not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sop = QmsSop::where('company_id', Auth::user()->company_id)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title'  => 'required|string|max:255',
                'status' => 'required|in:draft,review,approved,archived',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();

            $sop->update([
                'title'          => $request->title,
                'description'    => $request->description,
                'department'     => $request->department,
                'linked_product' => $request->linked_product,
                'status'         => $request->status,
                'approved_at'    => $request->status === 'approved' ? ($sop->approved_at ?? now()) : $sop->approved_at,
            ]);

            // Re-sync steps
            if ($request->has('steps')) {
                $sop->steps()->delete();
                foreach ($request->steps as $i => $step) {
                    if (!empty($step['title'])) {
                        QmsSopStep::create([
                            'sop_id'       => $sop->id,
                            'order'        => $i + 1,
                            'title'        => $step['title'],
                            'description'  => $step['description'] ?? null,
                            'has_checklist'=> isset($step['has_checklist']) ? true : false,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'SOP updated successfully!', 'data' => $sop]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error updating SOP: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $sop = QmsSop::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $sop->delete();
            return response()->json(['success' => true, 'message' => 'SOP deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting SOP.'], 500);
        }
    }
}
