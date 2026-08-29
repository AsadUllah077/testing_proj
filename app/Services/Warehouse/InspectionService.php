<?php

namespace App\Services\Warehouse;

use App\Models\Disposition;
use App\Models\FunctionalTestingResult;
use App\Models\GeneralTaskResult;
use App\Models\ProductAssignment;
use App\Models\VisualInspectionResult;
use Illuminate\Support\Facades\DB;

class InspectionService
{
    public function start(ProductAssignment $assignment, int $actorId): ProductAssignment
    {
        if ($assignment->completed_at) {
            throw new \DomainException('Cannot start a completed task');
        }

        return DB::transaction(function () use ($assignment, $actorId) {
            $assignment->loadMissing('product');
            $assignment->update([
                'started_at' => now(),
                'status' => 'in_progress',
            ]);

            $assignment->product->histories()->create([
                'action' => 'task_started',
                'field' => 'task_status',
                'old_value' => 'pending',
                'new_value' => 'in_progress',
                'notes' => 'Task started by technician',
                'changed_by' => $actorId,
                'company_id' => $assignment->company_id,
            ]);

            return $assignment->refresh();
        });
    }

    public function retry(ProductAssignment $assignment, int $actorId): ProductAssignment
    {
        return DB::transaction(function () use ($assignment, $actorId) {
            $assignment->loadMissing('product');
            $assignment->update([
                'started_at' => null,
                'completed_at' => null,
                'result' => null,
                'status' => 'pending',
            ]);

            $assignment->product->histories()->create([
                'action' => 'task_retried',
                'field' => 'task_status',
                'old_value' => 'failed',
                'new_value' => 'pending',
                'notes' => 'Task marked for retry',
                'changed_by' => $actorId,
                'company_id' => $assignment->company_id,
            ]);

            return $assignment->refresh();
        });
    }

    public function submitVisual(ProductAssignment $assignment, array $data, int $actorId): array
    {
        return DB::transaction(function () use ($assignment, $data, $actorId) {
            $assignment->loadMissing('product');
            $wasCompleted = (bool) $assignment->completed_at;

            $result = VisualInspectionResult::updateOrCreate(
                ['assignment_id' => $assignment->id],
                [
                    'product_id' => $assignment->product_id,
                    'technician_id' => $actorId,
                    'checklist_items' => $data['checklist_items'],
                    'inspection_notes' => $data['inspection_notes'] ?? null,
                    'grade' => $data['grade'],
                    'photos' => $data['photos'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'submitted_by' => $actorId,
                    'submitted_at' => now(),
                    'company_id' => $assignment->company_id,
                ]
            );

            $checklistKeys = array_keys($data['checklist_items'] ?? []);
            $assignment->product->visualInspections()->updateOrCreate(
                ['product_id' => $assignment->product_id],
                [
                    'product_id' => $assignment->product_id,
                    'grade' => $data['grade'],
                    'inspection_notes' => $data['inspection_notes'] ?? null,
                    'checklist_items' => $checklistKeys,
                    'custom_checklist' => $checklistKeys,
                    'inspected_by' => $actorId,
                    'inspected_at' => now(),
                    'company_id' => $assignment->company_id,
                ]
            );

            if (! empty($data['functional_grade'])) {
                $functionalTesting = $assignment->product->functionalTestings()->firstOrNew([
                    'product_id' => $assignment->product_id,
                ]);
                $functionalTesting->fill([
                    'grade' => $data['functional_grade'],
                    'custom_tests' => $functionalTesting->custom_tests ?? [],
                    'tested_by' => $functionalTesting->tested_by ?? $actorId,
                    'tested_at' => $functionalTesting->tested_at ?? now(),
                    'company_id' => $assignment->company_id,
                ])->save();
            }

            $this->markSubmitted($assignment);
            $assignment->product->histories()->create([
                'action' => $wasCompleted ? 'visual_inspection_updated' : 'visual_inspection_completed',
                'field' => 'visual_inspection',
                'old_value' => null,
                'new_value' => json_encode([
                    'grade' => $data['grade'],
                    'notes' => $data['inspection_notes'] ?? null,
                ]),
                'notes' => ($wasCompleted ? 'Visual inspection updated' : 'Visual inspection completed')." with grade {$data['grade']}",
                'changed_by' => $actorId,
                'company_id' => $assignment->company_id,
            ]);

            return compact('assignment', 'result', 'wasCompleted');
        });
    }

    public function submitFunctional(ProductAssignment $assignment, array $data, int $actorId): array
    {
        return DB::transaction(function () use ($assignment, $data, $actorId) {
            $assignment->loadMissing('product');
            $wasCompleted = (bool) $assignment->completed_at;

            $result = FunctionalTestingResult::updateOrCreate(
                ['assignment_id' => $assignment->id],
                [
                    'product_id' => $assignment->product_id,
                    'technician_id' => $actorId,
                    'test_results' => $data['test_results'],
                    'overall_summary' => $data['overall_summary'] ?? null,
                    'grade' => $data['grade'],
                    'remarks' => $data['remarks'] ?? null,
                    'submitted_by' => $actorId,
                    'submitted_at' => now(),
                    'company_id' => $assignment->company_id,
                ]
            );

            $assignment->product->functionalTestings()->updateOrCreate(
                ['product_id' => $assignment->product_id],
                [
                    'product_id' => $assignment->product_id,
                    'grade' => $data['grade'],
                    'overall_results_summary' => $data['overall_summary'] ?? null,
                    'custom_tests' => array_column($data['test_results'] ?? [], 'name'),
                    'tested_by' => $actorId,
                    'tested_at' => now(),
                    'company_id' => $assignment->company_id,
                ]
            );

            if (! empty($data['cosmetic_grade'])) {
                $visualInspection = $assignment->product->visualInspections()->firstOrNew([
                    'product_id' => $assignment->product_id,
                ]);
                $visualInspection->fill([
                    'grade' => $data['cosmetic_grade'],
                    'checklist_items' => $visualInspection->checklist_items ?? [],
                    'inspected_by' => $visualInspection->inspected_by ?? $actorId,
                    'inspected_at' => $visualInspection->inspected_at ?? now(),
                    'company_id' => $assignment->company_id,
                ])->save();
            }

            $this->markSubmitted($assignment);
            $assignment->product->histories()->create([
                'action' => $wasCompleted ? 'functional_testing_updated' : 'functional_testing_completed',
                'field' => 'functional_testing',
                'old_value' => null,
                'new_value' => json_encode([
                    'grade' => $data['grade'],
                    'summary' => $data['overall_summary'] ?? null,
                ]),
                'notes' => ($wasCompleted ? 'Functional testing updated' : 'Functional testing completed')." with grade {$data['grade']}",
                'changed_by' => $actorId,
                'company_id' => $assignment->company_id,
            ]);

            return compact('assignment', 'result', 'wasCompleted');
        });
    }

    public function submitGeneral(ProductAssignment $assignment, array $data, int $actorId): array
    {
        return DB::transaction(function () use ($assignment, $data, $actorId) {
            $assignment->loadMissing('product');
            $wasCompleted = (bool) $assignment->completed_at;

            $additionalData = [
                'checklist_items' => $data['checklist_items'] ?? [],
                'grade' => $data['grade'] ?? null,
                'cosmetic_grade' => $data['cosmetic_grade'] ?? null,
                'functional_grade' => $data['functional_grade'] ?? null,
            ];

            $result = GeneralTaskResult::updateOrCreate(
                ['assignment_id' => $assignment->id],
                [
                    'product_id' => $assignment->product_id,
                    'technician_id' => $actorId,
                    'status' => 'completed',
                    'remarks' => $data['remarks'] ?? null,
                    'technician_notes' => $data['technician_notes'] ?? null,
                    'additional_data' => $additionalData,
                    'started_at' => $assignment->started_at,
                    'completed_at' => now(),
                    'submitted_by' => $actorId,
                    'submitted_at' => now(),
                    'company_id' => $assignment->company_id,
                ]
            );

            $this->applyOptionalGrades($assignment, $data, $actorId);
            $this->markSubmitted($assignment);

            $gradeSuffix = ! empty($data['grade']) ? " with grade {$data['grade']}" : '';
            $assignment->product->histories()->create([
                'action' => $wasCompleted ? 'task_updated' : 'task_completed',
                'field' => 'general_task',
                'old_value' => null,
                'new_value' => json_encode([
                    'grade' => $data['grade'] ?? null,
                    'cosmetic_grade' => $data['cosmetic_grade'] ?? null,
                    'functional_grade' => $data['functional_grade'] ?? null,
                    'checklist_items' => array_keys($data['checklist_items'] ?? []),
                    'notes' => $data['technician_notes'] ?? null,
                ]),
                'notes' => ($wasCompleted ? 'Task updated' : 'Task completed').$gradeSuffix,
                'changed_by' => $actorId,
                'company_id' => $assignment->company_id,
            ]);

            return compact('assignment', 'result', 'wasCompleted');
        });
    }

    public function complete(ProductAssignment $assignment, array $data, int $actorId): ProductAssignment
    {
        return DB::transaction(function () use ($assignment, $data, $actorId) {
            $assignment->loadMissing('product');
            $assignment->update([
                'completed_at' => now(),
                'result' => $data['result'],
                'completion_notes' => $data['notes'] ?? null,
                'test_data' => $data['test_data'] ?? null,
                'status' => 'completed',
            ]);

            $disposition = Disposition::where('name', $data['result'] === 'pass' ? 'Pass' : 'Fail')->first();
            if ($disposition) {
                $assignment->product->update(['disposition_id' => $disposition->id]);
            }

            $assignment->product->histories()->create([
                'action' => 'task_completed',
                'field' => 'task_status',
                'old_value' => 'in_progress',
                'new_value' => 'completed',
                'notes' => $data['notes'] ?? 'Task completed with result: '.$data['result'],
                'changed_by' => $actorId,
                'company_id' => $assignment->company_id,
            ]);

            return $assignment->refresh();
        });
    }

    private function markSubmitted(ProductAssignment $assignment): void
    {
        $assignment->update([
            'completed_at' => $assignment->completed_at ?? now(),
            'result' => null,
            'status' => 'completed',
        ]);
    }

    private function applyOptionalGrades(ProductAssignment $assignment, array $data, int $actorId): void
    {
        if (! empty($data['cosmetic_grade'])) {
            $visualInspection = $assignment->product->visualInspections()->firstOrNew([
                'product_id' => $assignment->product_id,
            ]);
            $visualInspection->fill([
                'grade' => $data['cosmetic_grade'],
                'checklist_items' => $visualInspection->checklist_items ?? [],
                'inspected_by' => $visualInspection->inspected_by ?? $actorId,
                'inspected_at' => $visualInspection->inspected_at ?? now(),
                'company_id' => $assignment->company_id,
            ])->save();
        }

        if (! empty($data['functional_grade'])) {
            $functionalTesting = $assignment->product->functionalTestings()->firstOrNew([
                'product_id' => $assignment->product_id,
            ]);
            $functionalTesting->fill([
                'grade' => $data['functional_grade'],
                'custom_tests' => $functionalTesting->custom_tests ?? [],
                'tested_by' => $functionalTesting->tested_by ?? $actorId,
                'tested_at' => $functionalTesting->tested_at ?? now(),
                'company_id' => $assignment->company_id,
            ])->save();
        }
    }
}
