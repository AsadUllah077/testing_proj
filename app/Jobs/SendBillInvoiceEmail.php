<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Services\BillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBillInvoiceEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $billId) {}

    public function handle(BillService $billService): void
    {
        $bill = Bill::withoutGlobalScope('company')->with(['vendor', 'billItems', 'currency'])->findOrFail($this->billId);
        if ($bill->approval_status !== 'approved') {
            return;
        }

        if (! $billService->sendBillEmail($bill)) {
            $this->fail(new \RuntimeException('Bill email delivery failed.'));
        }
    }
}
