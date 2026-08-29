<?php

namespace App\Jobs;

use App\Exports\MassMailerContactsExport;
use App\Models\MassMailerContact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportMassMailerContactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $search;
    protected $tagId;
    protected $format;
    protected $userId;
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($search, $tagId, $format, $userId, $jobId)
    {
        $this->search = $search;
        $this->tagId = $tagId;
        $this->format = $format;
        $this->userId = $userId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'contacts_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Processing contact data...');

            if ($this->format === 'xlsx') {
                Excel::store(
                    new MassMailerContactsExport($this->search, $this->tagId),
                    $filePath,
                    'public'
                );
            } elseif ($this->format === 'pdf') {
                $this->exportToPDF($filePath);
            }

            $this->updateProgress(90, 'Finalizing export...');

            Cache::put(
                "export_result_{$this->jobId}",
                [
                    'url' => Storage::url($filePath),
                    'filename' => $fileName,
                ],
                now()->addHours(24)
            );

            $this->updateProgress(100, 'Export completed!');
        } catch (\Exception $e) {
            $this->updateProgress(-1, 'Export failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function exportToPDF($filePath)
    {
        $query = MassMailerContact::with('tags');

        if ($this->search) {
            $search = strtolower($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(firstname) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(lastname) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($this->tagId) {
            $query->whereHas('tags', function ($q) {
                $q->where('mass_mailer_tags.id', $this->tagId);
            });
        }

        $contacts = $query->latest()->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.mass_mailer.contacts.pdf.export', compact('contacts'));

        Storage::disk('public')->put($filePath, $pdf->output());
    }

    protected function updateProgress($percentage, $message)
    {
        Cache::put(
            "export_progress_{$this->jobId}",
            [
                'percentage' => $percentage,
                'message' => $message,
            ],
            now()->addHours(1)
        );
    }
}
