<?php

namespace App\Jobs;

use App\Models\Survey;
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

class ExportSurveysJob implements ShouldQueue
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

            $fileName = 'surveys_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Fetching survey data...');

            $query = Survey::where('company_id', $this->companyId)->withCount('responses')->latest();

            if (!empty($this->filters['q'])) {
                $search = $this->filters['q'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            if (!empty($this->filters['status'])) {
                $query->where('status', $this->filters['status']);
            }

            if (!empty($this->filters['from_date'])) {
                $query->whereDate('start_date', '>=', $this->filters['from_date']);
            }

            if (!empty($this->filters['to_date'])) {
                $query->whereDate('end_date', '<=', $this->filters['to_date']);
            }

            $surveys = $query->get();

            $this->updateProgress(60, 'Generating file...');

            if ($this->format === 'xlsx') {
                Excel::store(
                    new class($surveys) implements FromCollection, WithHeadings, WithStyles {
                        protected $surveys;
                        public function __construct($surveys) { $this->surveys = $surveys; }
                        public function collection() {
                            return $this->surveys->map(fn($s) => [
                                'Title'      => $s->title,
                                'Status'     => $s->status,
                                'Start Date' => $s->start_date ? $s->start_date->format('Y-m-d') : '',
                                'End Date'   => $s->end_date ? $s->end_date->format('Y-m-d') : '',
                                'Responses'  => $s->responses_count,
                                'Anonymous'  => $s->is_anonymous ? 'Yes' : 'No',
                            ]);
                        }
                        public function headings(): array {
                            return ['Title', 'Status', 'Start Date', 'End Date', 'Responses', 'Anonymous'];
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
                $pdf = \PDF::loadView('pdf.surveys-export', ['surveys' => $surveys]);
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
