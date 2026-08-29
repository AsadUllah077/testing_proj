<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Imports\Logistics\CarriersImport;
use App\Exports\Logistics\CarriersTemplateExport;
use App\Models\LogisticsV2Carriers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class CarrierV2Controller extends Controller
{
    public function index(Request $request): View
    {
        $query = LogisticsV2Carriers::with('createdBy')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('carrier_company_name', 'like', "%{$search}%")
                    ->orWhere('trading_brand_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('ntn_scac_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('transport_mode')) {
            $query->where('transport_modes', $request->transport_mode);
        }

        $carriers = $query->paginate(15)->withQueryString();

        return view('pages.products.logistics.carriers.v2.index', compact('carriers'));
    }

    // ─── Create ───────────────────────────────────────────────────

    public function create(): View
    {
        return view('pages.products.logistics.carriers.v2.create');
    }

    // ─── Store ────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $status = $request->input('action') === 'draft' ? 'draft' : 'pending_approval';

        $data = array_merge($validated, [
            'status' => $status,
            'created_by' => Auth::id(),
        ]);

        if ($request->hasFile('liability_insurance')) {
            $data['liability_insurance_path'] = $request->file('liability_insurance')
                ->store('carriers/insurance', 'public');
        }

        $carrier = LogisticsV2Carriers::create($data);

        $message = $status === 'draft'
            ? 'Carrier saved as draft.'
            : 'Carrier submitted for approval.';

        return redirect()
            ->route('logistics.carriers.v2.show', $carrier)
            ->with('success', $message);
    }

    // ─── Show ─────────────────────────────────────────────────────

    public function show(LogisticsV2Carriers $carrier): View
    {
        $carrier->load('createdBy', 'approvedBy');

        return view('pages.products.logistics.carriers.v2.show', compact('carrier'));
    }

    // ─── Edit ─────────────────────────────────────────────────────

    public function edit(LogisticsV2Carriers $carrier): View
    {
        return view('pages.products.logistics.carriers.v2.edit', compact('carrier'));
    }

    // ─── Update ───────────────────────────────────────────────────

    public function update(Request $request, LogisticsV2Carriers $carrier): RedirectResponse
    {
        $validated = $request->validate($this->rules($carrier->id));

        // $status = $request->input('action') === 'draft' ? 'draft' : 'pending_approval';

        $data = array_merge($validated);

        if ($request->hasFile('liability_insurance')) {
            // Remove old file
            if ($carrier->liability_insurance_path) {
                Storage::disk('public')->delete($carrier->liability_insurance_path);
            }
            $data['liability_insurance_path'] = $request->file('liability_insurance')
                ->store('carriers/insurance', 'public');
        }

        $carrier->update($data);

        $message = 'Carrier updated and submitted for approval.';

        return redirect()
            ->route('logistics.carriers.v2.show', $carrier)
            ->with('success', $message);
    }

    // ─── Destroy ──────────────────────────────────────────────────

    public function destroy(LogisticsV2Carriers $carrier): RedirectResponse
    {
        if ($carrier->liability_insurance_path) {
            Storage::disk('public')->delete($carrier->liability_insurance_path);
        }

        $carrier->delete();

        return redirect()
            ->route('logistics.carriers.v2.index')
            ->with('success', 'Carrier deleted successfully.');
    }

    // ─── Approve ──────────────────────────────────────────────────

    public function approve(LogisticsV2Carriers $carrier): RedirectResponse
    {
        $carrier->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('logistics.carriers.v2.show', $carrier)
            ->with('success', 'Carrier approved and added to the vendor list.');
    }

    // ─── Reject ───────────────────────────────────────────────────

    public function reject(LogisticsV2Carriers $carrier): RedirectResponse
    {
        $carrier->update(['status' => 'rejected']);

        return redirect()
            ->route('logistics.carriers.v2.show', $carrier)
            ->with('success', 'Carrier has been rejected.');
    }

    // ─── Import ────────────────────────────────────────────

    public function downloadTemplate()
    {
        return Excel::download(new CarriersTemplateExport, 'carriers_import_template.xlsx');
    }

    public function importCarriers(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:5120',
        ]);

        try {
            $import = new CarriersImport;
            Excel::import($import, $request->file('file'));

            $errors = $import->errors();

            if (count($errors) > 0) {
                $msgs = collect($errors)->map(fn ($e) => $e->getMessage())->unique()->implode(' | ');
                return redirect()->back()->with('warning', 'Import completed with some errors: ' . $msgs);
            }

            return redirect()->back()->with('success', 'Carriers imported successfully!');

        } catch (ValidationException $e) {
            $failures = collect($e->failures())
                ->map(fn ($f) => 'Row ' . $f->row() . ': ' . implode(', ', $f->errors()))
                ->implode(' | ');
            return redirect()->back()->with('error', 'Validation failed — ' . $failures);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import error: ' . $e->getMessage());
        }
    }

    // ─── Validation Rules ───────────────────────────────────

    private function rules(?int $ignoreId = null): array
    {
        return [
            // Company Information
            'carrier_company_name' => ['required', 'string', 'max:255'],
            'trading_brand_name' => ['nullable', 'string', 'max:255'],
            'transport_modes' => ['required', 'string', Rule::in(array_keys(LogisticsV2Carriers::TRANSPORT_MODES))],
            'country_of_incorporation' => ['required', 'string', 'max:255'],
            'pakistan_liaison_office' => ['nullable', 'string', 'max:255'],
            'ntn_scac_code' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'phone_pakistan_office' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],

            // Service Coverage
            'key_origin_ports_airports' => ['nullable', 'string', 'max:255'],
            'key_destination_markets' => ['nullable', 'string', 'max:255'],
            'services_offered' => ['nullable', 'string', Rule::in(array_keys(LogisticsV2Carriers::SERVICES_OFFERED))],
            'tracking_portal_api' => ['nullable', 'string', Rule::in(array_keys(LogisticsV2Carriers::TRACKING_PORTAL_OPTIONS))],

            // Compliance & Documents
            'fiata_iata_membership' => ['nullable', 'string', Rule::in(array_keys(LogisticsV2Carriers::FIATA_IATA_OPTIONS))],
            'insurance_marine_liability' => ['nullable', 'string', Rule::in(array_keys(LogisticsV2Carriers::INSURANCE_OPTIONS))],
            'aeo_ctpat_iso_certification' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['required', 'string', Rule::in(array_keys(LogisticsV2Carriers::PAYMENT_TERMS))],
            'credit_limit_usd' => ['nullable', 'numeric', 'min:0'],
            'account_manager_internal' => ['nullable', 'string', 'max:255'],
            'notes_onboarding_remarks' => ['nullable', 'string'],
            'liability_insurance' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'carrier_company_name' => ['required', 'string', 'max:255'],
            'transport_modes' => ['required', 'string', Rule::in(array_keys(LogisticsV2Carriers::TRANSPORT_MODES))],
            'country_of_incorporation' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'payment_terms' => ['required', 'string', Rule::in(array_keys(LogisticsV2Carriers::PAYMENT_TERMS))],
        ]);

        $carrier = LogisticsV2Carriers::create(array_merge($validated, [
            'status' => 'approved',
            'created_by' => Auth::id(),
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'company_id' => auth()->check() ? auth()->user()->company_id : null,
        ]));

        return response()->json([
            'success' => true,
            'carrier' => $carrier,
        ]);
    }
}
