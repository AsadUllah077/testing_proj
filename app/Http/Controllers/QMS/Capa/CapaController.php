<?php

namespace App\Http\Controllers\QMS\Capa;

use App\Http\Controllers\Controller;
use App\Models\QmsCapa;
use App\Models\QmsSop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CapaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search  = $request->input('q');
            $status  = $request->input('status');
            $perPage = $request->input('per_page', 20);

            $query = QmsCapa::with(['assignee', 'sop'])
                ->where('company_id', Auth::user()->company_id)
                ->latest();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('capa_number', 'LIKE', "%{$search}%");
                });
            }

            if (!empty($status)) {
                $query->where('status', $status);
            }

            $capas = $query->paginate($perPage)->withQueryString()->onEachSide(1);

            $stats = [
                'open'        => QmsCapa::where('company_id', Auth::user()->company_id)->where('status', 'open')->count(),
                'in_progress' => QmsCapa::where('company_id', Auth::user()->company_id)->where('status', 'in_progress')->count(),
                'closed'      => QmsCapa::where('company_id', Auth::user()->company_id)->where('status', 'closed')->count(),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.qms.capa.partials.table', compact('capas'))->render(),
                ]);
            }

            return view('pages.qms.capa.index', compact('capas', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error fetching CAPAs: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching CAPAs.');
        }
    }

    public function create()
    {
        $sops = QmsSop::where('company_id', Auth::user()->company_id)->where('status', 'approved')->get();
        return view('pages.qms.capa.create', compact('sops'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title'          => 'required|string|max:255',
                'description'    => 'nullable|string',
                'root_cause'     => 'nullable|string',
                'linked_product' => 'nullable|string|max:255',
                'linked_sop_id'  => 'nullable|exists:qms_sops,id',
                'assigned_to'    => 'nullable|exists:users,id',
                'status'         => 'required|in:open,in_progress,closed',
                'deadline'       => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $capa = QmsCapa::create([
                'title'           => $request->title,
                'description'     => $request->description,
                'root_cause'      => $request->root_cause,
                'linked_product'  => $request->linked_product,
                'linked_sop_id'   => $request->linked_sop_id,
                'assigned_to'     => $request->assigned_to,
                'status'          => $request->status,
                'deadline'        => $request->deadline,
                'resolution_notes'=> $request->resolution_notes,
                'company_id'      => Auth::user()->company_id,
                'created_by'      => Auth::id(),
                'closed_at'       => $request->status === 'closed' ? now() : null,
            ]);

            return response()->json(['success' => true, 'message' => 'CAPA created successfully!', 'data' => $capa], 201);

        } catch (\Exception $e) {
        }
    }

    public function show($id)
    {
        try {
            $capa = QmsCapa::with(['assignee', 'sop', 'creator'])
                ->where('company_id', Auth::user()->company_id)
                ->findOrFail($id);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data'     => $capa,
                ]);
            }

            return view('pages.qms.capa.show', compact('capa'));

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'CAPA not found.'], 404);
            }
            return redirect()->route('qms.capa.index')->with('error', 'CAPA not found.');
        }
    }

    public function edit($id)
    {
        try {
            $capa = QmsCapa::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $sops = QmsSop::where('company_id', Auth::user()->company_id)->where('status', 'approved')->get();
            return view('pages.qms.capa.edit', compact('capa', 'sops'));
        } catch (\Exception $e) {
            return redirect()->route('qms.capa.index')->with('error', 'CAPA not found.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $capa = QmsCapa::where('company_id', Auth::user()->company_id)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title'  => 'required|string|max:255',
                'status' => 'required|in:open,in_progress,closed',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $capa->update([
                'title'           => $request->title,
                'description'     => $request->description,
                'root_cause'      => $request->root_cause,
                'linked_product'  => $request->linked_product,
                'linked_sop_id'   => $request->linked_sop_id,
                'assigned_to'     => $request->assigned_to,
                'status'          => $request->status,
                'deadline'        => $request->deadline,
                'resolution_notes'=> $request->resolution_notes,
                'closed_at'       => $request->status === 'closed' ? ($capa->closed_at ?? now()) : $capa->closed_at,
            ]);

            return response()->json(['success' => true, 'message' => 'CAPA updated successfully!', 'data' => $capa]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating CAPA: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $capa = QmsCapa::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $capa->delete();
            return response()->json(['success' => true, 'message' => 'CAPA deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting CAPA.'], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $capa = QmsCapa::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $capa->update([
                'status'    => $request->status,
                'closed_at' => $request->status === 'closed' ? now() : $capa->closed_at,
            ]);
            return response()->json(['success' => true, 'message' => 'Status updated!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating status.'], 500);
        }
    }
}
