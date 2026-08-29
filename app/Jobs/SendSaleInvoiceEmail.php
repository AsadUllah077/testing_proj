<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSaleInvoiceEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $saleId)
    {
    }

    public function handle(SaleService $saleService): void
    {
        $sale = Sale::withoutGlobalScope('company')->with(['customer', 'saleItems', 'currency'])->findOrFail($this->saleId);
        if (! in_array($sale->approval_status, ['approved'], true)) {
            return;
        }

        if (! $saleService->sendInvoiceEmail($sale)) {
            $this->fail(new \RuntimeException('Invoice email delivery failed.'));
        }
    }
}
