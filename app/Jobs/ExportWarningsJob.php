<?php

namespace App\Jobs;

use App\Models\Warning;
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

class ExportWarningsJob implements ShouldQueue
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

            $fileName = 'warnings_export_' . now()->format('Y_m_d_His') . '.' . $this->format;
            $filePath = 'exports/' . $fileName;

            $this->updateProgress(30, 'Fetching warning data...');

            $query = Warning::where('company_id', $this->companyId)
                ->with('employee')
                ->latest();

            if (!empty($this->filters['q'])) {
                $search = $this->filters['q'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhereHas('employee', fn($e) => $e->where('full_name', 'LIKE', "%{$search}%"));
                });
            }

            if (!empty($this->filters['from_date'])) {
                $query->whereDate('warning_date', '>=', $this->filters['from_date']);
            }

            if (!empty($this->filters['to_date'])) {
                $query->whereDate('warning_date', '<=', $this->filters['to_date']);
            }

            $warnings = $query->get();

            $this->updateProgress(60, 'Generating file...');

            if ($this->format === 'xlsx') {
                Excel::store(
                    new class($warnings) implements FromCollection, WithHeadings, WithStyles {
                        protected $warnings;
                        public function __construct($warnings) { $this->warnings = $warnings; }
                        public function collection() {
                            return $this->warnings->map(fn($w) => [
                                'Employee'     => $w->employee->full_name ?? 'N/A',
                                'Email'        => $w->employee->email ?? '',
                                'Warning Date' => $w->warning_date ? $w->warning_date->format('Y-m-d') : '',
                                'Title'        => $w->title,
                                'Description'  => $w->description ?? '',
                            ]);
                        }
                        public function headings(): array {
                            return ['Employee', 'Email', 'Warning Date', 'Title', 'Description'];
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
                $pdf = \PDF::loadView('pdf.warnings-export', ['warnings' => $warnings]);
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
