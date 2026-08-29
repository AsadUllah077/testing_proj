<?php
/*
 * (c) Hilal Ahmad
 */

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $format;
    protected $userId;
    protected $jobId;
    protected $companyId;
    protected $filters;

    public function __construct($userId, $jobId, $format, $companyId, $filters = [])
    {
        $this->userId    = $userId;
        $this->jobId     = $jobId;
        $this->format    = $format;
        $this->companyId = $companyId;
        $this->filters   = $filters;
    }

    public function handle(): void
    {
        try {
            $this->updateProgress(10, 'Starting export...');

            $fileName = 'tickets_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Fetching ticket data...');

            $query = Ticket::where('company_id', $this->companyId)
                ->with(['assignee', 'reporter'])
                ->latest();

            if (!empty($this->filters['q'])) {
                $search = $this->filters['q'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('ticket_number', 'like', "%{$search}%");
                });
            }

            if (!empty($this->filters['ids'])) {
                $ids = is_array($this->filters['ids']) ? $this->filters['ids'] : explode(',', $this->filters['ids']);
                $query->whereIn('id', $ids);
            }

            if (!empty($this->filters['status'])) {
                $query->where('status', $this->filters['status']);
            }

            if (!empty($this->filters['severity'])) {
                $query->where('severity', $this->filters['severity']);
            }

            if (!empty($this->filters['category'])) {
                $query->where('category', $this->filters['category']);
            }

            $tickets = $query->get();

            $this->updateProgress(60, 'Generating file...');

            if ($this->format === 'xlsx') {
                Excel::store(
                    new class($tickets) implements FromCollection, WithHeadings, WithStyles {
                        protected $tickets;
                        public function __construct($tickets) { $this->tickets = $tickets; }
                        public function collection() {
                            return $this->tickets->map(fn($t) => [
                                'Ticket #'     => $t->ticket_number,
                                'Title'        => $t->title,
                                'Category'     => $t->category ?? 'N/A',
                                'Severity'     => ucfirst($t->severity),
                                'Status'       => ucfirst(str_replace('-', ' ', $t->status)),
                                'Assigned To'  => $t->assignee?->full_name ?? '—',
                                'Reported By'  => $t->reporter?->full_name ?? ($t->reporter?->name ?? '—'),
                                'Date'         => $t->created_at->format('Y-m-d H:i'),
                            ]);
                        }
                        public function headings(): array {
                            return ['Ticket #', 'Title', 'Category', 'Severity', 'Status', 'Assigned To', 'Reported By', 'Date'];
                        }
                        public function styles(Worksheet $sheet) {
                            return [1 => ['font' => ['bold' => true]]];
                        }
                    },
                    $filePath,
                    'public'
                );
            } elseif ($this->format === 'pdf') {
                $this->updateProgress(70, 'Rendering PDF...');
                $pdf = Pdf::loadView('pdf.tickets-export', ['tickets' => $tickets])
                    ->setPaper('a4', 'landscape');
                Storage::disk('public')->put($filePath, $pdf->output());
            }

            $this->updateProgress(90, 'Finalizing...');

            Cache::put("export_result_{$this->jobId}", [
                'url'      => Storage::url($filePath),
                'filename' => $fileName,
            ], now()->addHours(24));

            $this->updateProgress(100, 'Export completed!');
        } catch (\Throwable $th) {
            $this->updateProgress(-1, 'Export failed: ' . $th->getMessage());
            throw $th;
        }
    }

    protected function updateProgress($percentage, $message)
    {
        Cache::put("export_progress_{$this->jobId}", [
            'percentage' => $percentage,
            'message'    => $message,
        ], now()->addHours(1));
    }

    public function failed(\Throwable $exception)
    {
        $this->updateProgress(-1, 'Export failed: ' . $exception->getMessage());
    }
}
