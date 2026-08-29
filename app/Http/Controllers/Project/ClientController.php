<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::where('company_id', Auth::user()->company_id);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $clients = $query->latest()->paginate($request->get('per_page', 15))->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html'       => view('pages.projects.clients.partials.table', compact('clients'))->render(),
                'pagination' => $clients->appends($request->query())->links()->toHtml(),
            ]);
        }

        return view('pages.projects.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('pages.projects.clients.create');
    }

    public function show(Client $client)
    {
        $client->load('projects.manager');
        return view('pages.projects.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('pages.projects.clients.edit', compact('client'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'type'           => 'nullable|in:individual,company',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string|max:500',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'website'        => 'nullable|string|max:255',
            'industry'       => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_email'  => 'nullable|email|max:255',
            'contact_phone'  => 'nullable|string|max:50',
            'notes'          => 'nullable|string',
            'status'         => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $client = Client::create(array_merge($validator->validated(), [
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
                'status'     => $request->input('status', 'active'),
                'type'       => $request->input('type', 'company'),
            ]));

            return response()->json(['success' => true, 'message' => 'Client created successfully!', 'data' => $client]);
        } catch (\Exception $e) {
            Log::error('ClientController@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create client.'], 500);
        }
    }

    public function update(Request $request, Client $client)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'type'           => 'nullable|in:individual,company',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string|max:500',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'website'        => 'nullable|string|max:255',
            'industry'       => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_email'  => 'nullable|email|max:255',
            'contact_phone'  => 'nullable|string|max:50',
            'notes'          => 'nullable|string',
            'status'         => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $client->update($validator->validated());
            return response()->json(['success' => true, 'message' => 'Client updated successfully!', 'data' => $client]);
        } catch (\Exception $e) {
            Log::error('ClientController@update: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update client.'], 500);
        }
    }

    public function destroy(Client $client)
    {
        try {
            $client->delete();
            return response()->json(['success' => true, 'message' => 'Client deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('ClientController@destroy: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $clients = Client::where('company_id', Auth::user()->company_id)
                ->when($request->get('q'), fn($q, $s) => $q->where(function ($query) use ($s) {
                    $query->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                }))
                ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
                ->when($request->get('type'), fn($q, $t) => $q->where('type', $t))
                ->latest()->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.projects.clients.exports.pdf', compact('clients'));
            return $pdf->download('clients-' . now()->format('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('ClientController@exportPdf: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'PDF export failed: ' . $e->getMessage()], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $clients = Client::where('company_id', Auth::user()->company_id)
                ->when($request->get('q'), fn($q, $s) => $q->where(function ($query) use ($s) {
                    $query->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                }))
                ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
                ->when($request->get('type'), fn($q, $t) => $q->where('type', $t))
                ->latest()->get();

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="clients-' . now()->format('Y-m-d') . '.csv"',
            ];
            $callback = function () use ($clients) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Name', 'Email', 'Type', 'Contact Person', 'Phone', 'Industry', 'City', 'Country', 'Status']);
                foreach ($clients as $client) {
                    fputcsv($handle, [
                        $client->name,
                        $client->email ?? '',
                        $client->type ?? '',
                        $client->contact_person ?? '',
                        $client->contact_phone ?? $client->phone ?? '',
                        $client->industry ?? '',
                        $client->city ?? '',
                        $client->country ?? '',
                        $client->status,
                    ]);
                }
                fclose($handle);
            };
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('ClientController@exportExcel: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }
}
