<?php

namespace App\Services;

use App\Models\MaerskSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MaerskApiService
{
    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private ?string $accessToken;
    private MaerskSetting $setting;

    public function __construct(MaerskSetting $setting)
    {
        $this->setting      = $setting;
        $this->baseUrl      = rtrim($setting->base_url ?? 'https://api.maersk.com', '/');
        $this->consumerKey  = $setting->consumer_key ?? '';
        $this->consumerSecret = $setting->consumer_secret ?? '';
        $this->accessToken  = $this->resolveAccessToken();
    }

    // ─── Token Management ──────────────────────────────────────────────────────

    private function resolveAccessToken(): ?string
    {
        if ($this->setting->access_token && $this->setting->token_expires_at
            && now()->lt($this->setting->token_expires_at->subMinutes(5))) {
            return $this->setting->access_token;
        }
        return $this->fetchAccessToken();
    }

    private function fetchAccessToken(): ?string
    {
        try {
            $response = Http::withOptions(['verify' => false])
                ->withHeaders(['Consumer-Key' => $this->consumerKey])
                ->asForm()
                ->post("{$this->baseUrl}/oauth2/access_token", [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->consumerKey,
                    'client_secret' => $this->consumerSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $expiresIn = $data['expires_in'] ?? 3600;
                $this->setting->update([
                    'access_token'    => $data['access_token'],
                    'token_expires_at' => now()->addSeconds($expiresIn),
                ]);
                return $data['access_token'];
            }

            Log::error('Maersk OAuth token request failed', ['body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Maersk OAuth exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    // ─── HTTP Helpers ──────────────────────────────────────────────────────────

    private function headers(bool $withAuth = true): array
    {
        $h = ['Consumer-Key' => $this->consumerKey];
        if ($withAuth && $this->accessToken) {
            $h['Authorization'] = "Bearer {$this->accessToken}";
        }
        return $h;
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withOptions(['verify' => false]);
    }

    private function get(string $path, array $query = [], bool $withAuth = true): array
    {
        try {
            $response = $this->http()
                ->withHeaders($this->headers($withAuth))
                ->get("{$this->baseUrl}{$path}", $query);

            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
                'data'    => $response->json(),
                'raw'     => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error("Maersk GET {$path} failed", ['message' => $e->getMessage()]);
            return ['success' => false, 'status' => 0, 'data' => null, 'raw' => $e->getMessage()];
        }
    }

    private function put(string $path, array $query = [], array $body = []): array
    {
        try {
            $response = $this->http()
                ->withHeaders($this->headers())
                ->put("{$this->baseUrl}{$path}" . ($query ? '?' . http_build_query($query) : ''), $body);

            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
                'data'    => $response->json(),
                'raw'     => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error("Maersk PUT {$path} failed", ['message' => $e->getMessage()]);
            return ['success' => false, 'status' => 0, 'data' => null, 'raw' => $e->getMessage()];
        }
    }

    // ─── 01 Commodities Reference Service ─────────────────────────────────────

    public function getCommodities(array $params = []): array
    {
        return $this->get('/commodity-classifications', $params, withAuth: false);
    }

    // ─── 02 VGM ───────────────────────────────────────────────────────────────

    public function getVgm(string $shipmentNumber, array $query): array
    {
        return $this->get("/vgm/shipments/{$shipmentNumber}/containers", $query);
    }

    public function submitVgm(string $shipmentNumber, string $containerNumber, array $query, array $body): array
    {
        return $this->put("/vgm/shipments/{$shipmentNumber}/containers/{$containerNumber}", $query, $body);
    }

    // ─── 03 Ocean Invoice Summary ─────────────────────────────────────────────

    public function getInvoices(array $params): array
    {
        return $this->get('/invoices', $params);
    }

    // ─── 04 Ocean Bill of Lading ──────────────────────────────────────────────

    public function getBillOfLading(string $transportDocRef): array
    {
        return $this->get("/ocean/document/v1/transport-documents/{$transportDocRef}");
    }

    // ─── 05 Import Demurrage & Detention ──────────────────────────────────────

    public function getDemurrageOrDetention(string $chargeType, array $params): array
    {
        // chargeType: DMR or DET
        return $this->get("/shipping-charges/import/{$chargeType}", $params);
    }

    // ─── 06 Track & Trace ─────────────────────────────────────────────────────

    public function trackEvents(array $params): array
    {
        return $this->get('/track-and-trace-private/events', $params);
    }

    // ─── 07 Ocean Credit Summary ──────────────────────────────────────────────

    public function getCredits(array $params): array
    {
        try {
            $response = $this->http()
                ->withHeaders(array_merge($this->headers(), ['API-Version' => 'v2']))
                ->get("{$this->baseUrl}/invoices", $params);

            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
                'data'    => $response->json(),
                'raw'     => $response->body(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'status' => 0, 'data' => null, 'raw' => $e->getMessage()];
        }
    }

    // ─── 08 Deadlines ─────────────────────────────────────────────────────────

    public function getDeadlines(array $params): array
    {
        return $this->get('/shipment-deadlines', $params, withAuth: false);
    }

    // ─── Utilities ────────────────────────────────────────────────────────────

    public function isConfigured(): bool
    {
        return !empty($this->consumerKey) && !empty($this->consumerSecret);
    }

    public function testConnection(): array
    {
        $result = $this->getCommodities(['commodityName' => 'test']);
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Connection successful' : 'Connection failed: ' . ($result['raw'] ?? 'Unknown error'),
        ];
    }
}
