<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sale;

    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    public function handle(SaleService $saleService)
    {
        $saleService->sendInvoiceEmail($this->sale);
    }
}
