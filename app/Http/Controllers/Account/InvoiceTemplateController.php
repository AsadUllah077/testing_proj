<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\InvoiceTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceTemplateController extends Controller
{
    public function index()
    {
        try {
            $templates = InvoiceTemplate::all();
            return view('pages.account.organization-settings.invoice-setting.index', compact('templates'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function create()
    {
        try {
            return view('pages.account.organization-settings.invoice-setting.create');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $template = InvoiceTemplate::findOrFail($id);
            return view('pages.account.organization-settings.invoice-setting.show', compact('template'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // Handle checkbox inputs
            $booleanFields = [
                'show_tax_numbers',
                'show_column_heading',
                'show_item_code',
                'show_invoice_qty',
                'show_payment_advance_cutaway',
                'show_tax_column',
                'show_registered_columns',
                'show_logo',
                'show_hs',
                'hide_discount',
                'show_contact_account_number',
                'is_default',
            ];

            foreach ($booleanFields as $field) {
                $data[$field] = $request->has($field);
            }

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoName = time() . '_' . $logo->getClientOriginalName();
                $logo->storeAs('logos', $logoName, 'public');
                $data['logo'] = 'logos/' . $logoName;
            }

            if ($data['is_default']) {
                InvoiceTemplate::where('company_id', auth('web')->user()->company_id)->update(['is_default' => false]);
            }

            InvoiceTemplate::create($data);

            return to_route('account.organization-settings.invoice-templates.index')->with('success', 'Invoice template created successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Show the edit form for the invoice template.
     */
    public function edit($id)
    {
        try {
            $template = InvoiceTemplate::findOrFail($id);
            return view('pages.account.organization-settings.invoice-setting.edit', compact('template'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $template = InvoiceTemplate::findOrFail($id);

            $data = $request->all();

            // Handle checkbox inputs (ensure unchecked become false)
            $booleanFields = [
                'show_tax_numbers',
                'show_column_heading',
                'show_item_code',
                'show_invoice_qty',
                'show_payment_advance_cutaway',
                'show_tax_column',
                'show_registered_columns',
                'show_logo',
                'show_hs',
                'hide_discount',
                'show_contact_account_number',
                'is_default',
            ];

            foreach ($booleanFields as $field) {
                $data[$field] = $request->has($field);
            }

            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($template->logo && Storage::disk('public')->exists($template->logo)) {
                    Storage::disk('public')->delete($template->logo);
                }

                $logo = $request->file('logo');
                $logoName = time() . '_' . $logo->getClientOriginalName();
                $logo->storeAs('logos', $logoName, 'public');
                $data['logo'] = 'logos/' . $logoName;
            }

            if ($data['is_default']) {
                InvoiceTemplate::where('company_id', auth('web')->user()->company_id)->where('id', '!=', $id)->update(['is_default' => false]);
            }

            $template->update($data);

            return to_route('account.organization-settings.invoice-templates.index')->with('success', 'Invoice template updated successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $template = InvoiceTemplate::findOrFail($id);

            // Delete logo file if exists
            if ($template->logo && Storage::disk('public')->exists($template->logo)) {
                Storage::disk('public')->delete($template->logo);
            }

            $template->delete();
            return to_route('account.organization-settings.invoice-templates.index')->with('success', 'Invoice template deleted successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function duplicate($id)
    {
        try {
            $template = InvoiceTemplate::findOrFail($id);
            $newTemplate = $template->replicate();
            $newTemplate->name = $template->name . ' (Copy)';
            $newTemplate->is_default = false;
            $newTemplate->save();
            return to_route('account.organization-settings.invoice-templates.index')->with('success', 'Invoice template duplicated successfully');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
