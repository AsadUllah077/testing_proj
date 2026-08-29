<?php

namespace App\Jobs;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportUsersPdf implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    protected $userIds;
    /**
     * Create a new job instance.
     */
    public function __construct(array $userIds = null)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        return $this->userIds ? User::whereIn('id', $this->userIds)->get() : User::all();
    }
}
