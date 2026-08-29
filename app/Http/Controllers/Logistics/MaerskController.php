<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\MaerskSetting;
use App\Services\MaerskApiService;
use Illuminate\Http\Request;

class MaerskController extends Controller
{
    private function getSetting(): MaerskSetting
    {
        $companyId = session('company_id') ?? 1;

        return MaerskSetting::firstOrCreate(
            ['company_id' => $companyId],
            ['base_url' => 'https://api.maersk.com']
        );
    }

    private function api(): MaerskApiService
    {
        return new MaerskApiService($this->getSetting());
    }

    // ─── Hub ──────────────────────────────────────────────────────────────────

    public function index()
    {
        $setting = $this->getSetting();
        $configured = ! empty($setting->consumer_key) && ! empty($setting->consumer_secret);

        return view('pages.products.logistics.maersk.index', compact('setting', 'configured'));
    }

    // ─── Settings ─────────────────────────────────────────────────────────────

    public function settings()
    {
        $setting = $this->getSetting();

        return view('pages.products.logistics.maersk.settings', compact('setting'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'consumer_key' => 'required|string',
            'consumer_secret' => 'required|string',
            'base_url' => 'required|url',
        ]);

        $setting = $this->getSetting();
        $setting->update([
            'consumer_key' => $request->consumer_key,
            'consumer_secret' => $request->consumer_secret,
            'base_url' => $request->base_url,
            'access_token' => null,
            'token_expires_at' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Maersk API settings saved successfully.']);
    }

    public function testConnection()
    {
        $setting = $this->getSetting();
        if (empty($setting->consumer_key)) {
            return response()->json(['success' => false, 'message' => 'API credentials not configured.']);
        }
        $result = (new MaerskApiService($setting))->testConnection();

        return response()->json($result);
    }

    // ─── Track & Trace ────────────────────────────────────────────────────────

    public function trackTrace(Request $request)
    {
        $result = null;
        $error = null;
        $events = [];
        $searched = false;

        if ($request->isMethod('post') || $request->has('carrierBookingReference')
            || $request->has('transportDocumentReference') || $request->has('equipmentReference')) {

            $params = array_filter([
                'carrierBookingReference' => $request->carrierBookingReference,
                'transportDocumentReference' => $request->transportDocumentReference,
                'equipmentReference' => $request->equipmentReference,
                'eventType' => $request->eventType,
                'limit' => $request->limit ?? 100,
            ]);

            if (! empty($params)) {
                $searched = true;
                $result = $this->api()->trackEvents($params);
                if ($result['success']) {
                    $data = $result['data'] ?? [];
                    // API wraps events in {"events": [...]}
                    $events = $data['events'] ?? (array_is_list($data) ? $data : []);
                } else {
                    $error = $this->extractError($result);
                }
            }
        }

        return view('pages.products.logistics.maersk.track-trace', compact('events', 'error', 'searched'));
    }

    // ─── Commodities ──────────────────────────────────────────────────────────

    public function commodities(Request $request)
    {
        $commodities = [];
        $error = null;
        $searched = false;

        if ($request->has('commodityName') || $request->has('commodityCode')
            || $request->has('hsCommodityCode') || $request->has('cargoType')) {

            $params = array_filter([
                'commodityName' => $request->commodityName,
                'commodityCode' => $request->commodityCode,
                'hsCommodityCode' => $request->hsCommodityCode,
                'cargoType' => $request->cargoType,
            ]);

            $searched = true;
            $result = $this->api()->getCommodities($params);
            if ($result['success']) {
                $commodities = $result['data'] ?? [];
            } else {
                $error = $this->extractError($result);
            }
        }

        return view('pages.products.logistics.maersk.commodities', compact('commodities', 'error', 'searched'));
    }

    // ─── Deadlines ────────────────────────────────────────────────────────────

    public function deadlines(Request $request)
    {
        $deadlines = null;
        $error = null;
        $searched = false;

        if ($request->has('vesselIMONumber')) {
            $params = array_filter([
                'vesselIMONumber' => $request->vesselIMONumber,
                'voyage' => $request->voyage,
                'UNLocationCode' => $request->UNLocationCode,
                'ISOCountryCode' => $request->ISOCountryCode,
                'portOfLoad' => $request->portOfLoad,
            ]);

            $searched = true;
            $result = $this->api()->getDeadlines($params);
            if ($result['success']) {
                $deadlines = $result['data'];
            } else {
                $error = $this->extractError($result);
            }
        }

        return view('pages.products.logistics.maersk.deadlines', compact('deadlines', 'error', 'searched'));
    }

    // ─── VGM ──────────────────────────────────────────────────────────────────

    public function vgm(Request $request)
    {
        $vgmData = null;
        $error = null;
        $searched = false;

        if ($request->has('shipmentNumber')) {
            $searched = true;
            $query = array_filter([
                'customerCode' => $request->customerCode,
                'brandCode' => $request->brandCode ?? 'MAEU',
            ]);
            $result = $this->api()->getVgm($request->shipmentNumber, $query);
            if ($result['success']) {
                $vgmData = $result['data'];
            } else {
                $error = $this->extractError($result);
            }
        }

        return view('pages.products.logistics.maersk.vgm', compact('vgmData', 'error', 'searched'));
    }

    public function submitVgm(Request $request)
    {
        $request->validate([
            'shipmentNumber' => 'required|string',
            'containerNumber' => 'required|string',
            'vgm' => 'required|numeric',
            'vgmUnit' => 'required|in:KGS,LBS',
            'vgmMethod' => 'required|in:CALCULATED,SCALED',
            'brandCode' => 'required|string',
        ]);

        $query = array_filter([
            'brandCode' => $request->brandCode,
            'customerCode' => $request->carrierCustomerCode,
        ]);

        $body = array_filter([
            'vgmSource' => $request->vgmSource ?? 'Shipper',
            'vgm' => (float) $request->vgm,
            'vgmUnit' => $request->vgmUnit,
            'vgmMethod' => $request->vgmMethod,
            'authorizedPersonName' => $request->authorizedPersonName,
            'authorizedPersonEmail' => $request->authorizedPersonEmail,
        ]);

        $result = $this->api()->submitVgm($request->shipmentNumber, $request->containerNumber, $query, $body);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'VGM submitted successfully (processing may take up to 60 seconds).'
                : $this->extractError($result),
            'data' => $result['data'],
        ]);
    }

    // ─── Ocean Invoices ───────────────────────────────────────────────────────

    public function invoiceDetail(Request $request, string $invoiceNo)
    {
        $params = array_filter([
            'carrierCode' => $request->carrierCode ?? 'MAEU',
            'customerCodeCMD' => $request->customerCodeCMD,
            'searchType' => 'BY_INVOICE_NUMBER',
            'ids' => $invoiceNo,
        ]);
        // mandatory parameters — invoiceType must match ^(OPEN|PAID|OVERDUE|CREDITS)$
        $rawType = $request->invoiceType ?? '';
        $params['invoiceType'] = in_array($rawType, ['OPEN', 'PAID', 'OVERDUE', 'CREDITS']) ? $rawType : 'OPEN';
        $params['isSelected'] = $request->isSelected ?? 'false';
        $params['isCreditCountry'] = $request->isCreditCountry ?? 'false';
        $params['startDate'] = '01JAN2024';
        $params['endDate'] = '31DEC2026';

        $result = $this->api()->getInvoices($params);

        if ($result['success']) {
            return response()->json(['success' => true, 'data' => $result['data']]);
        }

        return response()->json(['success' => false, 'error' => $this->extractError($result)], 422);
    }

    public function invoices(Request $request)
    {
        $invoices = [];
        $error = null;
        $searched = false;

        if ($request->has('carrierCode') && $request->has('customerCodeCMD')) {
            $searched = true;
            $params = array_filter([
                'carrierCode' => $request->carrierCode,
                'customerCodeCMD' => $request->customerCodeCMD,
                'searchType' => $request->searchType,
                'ids' => $request->ids,
            ]);
            // invoiceType must match ^(OPEN|PAID|OVERDUE|CREDITS)$
            $rawInvoiceType = $request->invoiceType ?? '';
            $params['invoiceType'] = in_array($rawInvoiceType, ['OPEN', 'PAID', 'OVERDUE', 'CREDITS'])
                ? $rawInvoiceType : 'OPEN';
            $params['isCreditCountry'] = $request->isCreditCountry ?? 'false';
            $params['isSelected'] = $request->isSelected ?? 'false';
            // startDate/endDate are mandatory for PAID; default to current year range when not supplied
            if ($params['invoiceType'] === 'PAID') {
                $params['startDate'] = $this->toMaerskDate($request->startDate) ?: strtoupper(now()->startOfYear()->format('dMY'));
                $params['endDate'] = $this->toMaerskDate($request->endDate) ?: strtoupper(now()->format('dMY'));
            } elseif ($request->startDate) {
                $params['startDate'] = $this->toMaerskDate($request->startDate);
                $params['endDate'] = $this->toMaerskDate($request->endDate);
            }

            $result = $this->api()->getInvoices($params);
            if ($result['success']) {
                $invoices = $result['data'] ?? [];
            } else {
                $error = $this->extractError($result);
            }
        }

        return view('pages.products.logistics.maersk.invoices', compact('invoices', 'error', 'searched'));
    }

    // ─── Demurrage & Detention ────────────────────────────────────────────────

    public function demurrage(Request $request)
    {
        $charges = null;
        $error = null;
        $searched = false;

        if ($request->has('billOfLadingNumber')) {
            $searched = true;
            $chargeType = $request->chargeType === 'DET' ? 'DET' : 'DMR';
            $params = array_filter([
                'billOfLadingNumber' => $request->billOfLadingNumber,
                'carrierCustomerCode' => $request->carrierCustomerCode,
                'carrierCode' => $request->carrierCode,
                'chargesEndDate' => $request->chargesEndDate,
            ]);

            $result = $this->api()->getDemurrageOrDetention($chargeType, $params);
            if ($result['success']) {
                $charges = $result['data'];
            } else {
                $error = $this->extractError($result);
            }
        }

        return view('pages.products.logistics.maersk.demurrage', compact('charges', 'error', 'searched'));
    }

    // ─── Ocean Credit Summary ─────────────────────────────────────────────────

    public function credits(Request $request)
    {
        $credits = [];
        $error = null;
        $searched = false;

        if ($request->has('customerCode')) {
            $searched = true;
            $params = array_filter([
                'carrierCode' => 'MAEU',
                'customerCode' => $request->customerCode,
                'invoiceType' => 'CREDITS',
                'searchType' => $request->searchType,
                'ids' => $request->ids,
            ]);

            $result = $this->api()->getCredits($params);
            if ($result['success']) {
                $credits = $result['data'] ?? [];
            } else {
                $error = $this->extractError($result);
            }
        }

        return view('pages.products.logistics.maersk.credits', compact('credits', 'error', 'searched'));
    }

    // ─── Bill of Lading ───────────────────────────────────────────────────────

    public function billOfLading(Request $request)
    {
        $bol = null;
        $error = null;
        $searched = false;

        if ($request->has('transportDocumentReference')) {
            $searched = true;
            $result = $this->api()->getBillOfLading($request->transportDocumentReference);
            if ($result['success']) {
                $bol = $result['data'];
            } else {
                $error = $this->extractError($result);
            }
        }

        return view('pages.products.logistics.maersk.bill-of-lading', compact('bol', 'error', 'searched'));
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    /**
     * Normalize any common date string to the DDMMMYYYY format Maersk requires (e.g. 12MAR2026).
     */
    private function toMaerskDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }
        if (preg_match('/^\d{2}[A-Z]{3}\d{4}$/', $date)) {
            return $date;
        } // already correct
        try {
            return strtoupper(\Carbon\Carbon::parse($date)->format('dMY'));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractError(array $result): string
    {
        $data = $result['data'];
        if (is_array($data)) {
            return $data['message'] ?? $data['error'] ?? $data['title'] ?? json_encode($data);
        }

        return $result['raw'] ?? 'An unknown error occurred.';
    }
}
