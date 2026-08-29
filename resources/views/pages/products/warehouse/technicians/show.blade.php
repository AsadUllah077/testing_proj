{{-- resources/views/pages/products/warehouse/technicians/show.blade.php --}}
@extends('layouts.auth')

@section('title', 'Task Detail - ' . $assignment->product->unique_id)

@section('content')
<x-acccount.loader />
@php
    $status = $assignment->completed_at
        ? 'COMPLETE'
        : ($assignment->started_at ? 'IN PROGRESS' : 'PENDING');

    $statusClass = match ($status) {
        'COMPLETE' => 'bg-green-100 text-green-800',
        'IN PROGRESS' => 'bg-blue-100 text-blue-800',
        'PENDING' => 'bg-yellow-100 text-yellow-800',
        default => 'bg-gray-100 text-gray-800',
    };

    $taskType = strtolower(trim($assignment->task_type ?? ''));
    $stageName = strtolower(trim($assignment->product->workFlowStage->name ?? ''));
    if (str_contains($taskType, 'functional') || str_contains($stageName, 'functional')) {
        $urlType = 'functional';
    } elseif (str_contains($taskType, 'visual') || str_contains($stageName, 'visual')) {
        $urlType = 'visual';
    } elseif (str_contains($taskType, 'calibration')) {
        $urlType = 'calibration';
    } elseif (str_contains($taskType, 'repair')) {
        $urlType = 'repair';
    } elseif (str_contains($taskType, 'quality')) {
        $urlType = 'quality';
    } else {
        $urlType = 'general';
    }
@endphp

<div class="mt-4">
    <div class="mx-auto px-3">
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Task Detail</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $assignment->task_type }} - {{ $assignment->product->unique_id }}</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        @if($status === 'IN PROGRESS')
                            <a href="{{ route('warehouse.technicians.submit.result', ['id' => $assignment->id, 'type' => $urlType]) }}"
                                class="px-5 py-2 bg-[#0078C8] text-white rounded-md text-xs font-medium hover:bg-[#0063A6] text-center">
                                SUBMIT RESULT
                            </a>
                        @elseif($status === 'COMPLETE')
                            <a href="{{ route('warehouse.technicians.view.result', $assignment->id) }}"
                                class="px-5 py-2 bg-[#0078C8] text-white rounded-md text-xs font-medium hover:bg-[#0063A6] text-center">
                                VIEW RESULT
                            </a>
                            <a href="{{ route('warehouse.technicians.submit.result', ['id' => $assignment->id, 'type' => $urlType, 'edit' => 1]) }}"
                                class="px-5 py-2 border border-gray-300 text-gray-700 rounded-md text-xs font-medium hover:bg-gray-50 text-center">
                                EDIT RESULT
                            </a>
                        @endif
                        <a href="{{ route('warehouse.technicians.index') }}"
                            class="px-5 py-2 bg-gray-700 text-white rounded-md text-xs font-medium hover:bg-gray-800 text-center">
                            BACK
                        </a>
                    </div>
                </div>

                <hr class="border-gray-200 mb-6">

                <div class="bg-blue-50 p-4 rounded-lg mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <span class="text-xs text-blue-600 font-semibold uppercase">Product</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $assignment->product->title }}</p>
                            <a href="{{ route('warehouse.products.show', $assignment->product->id) }}"
                                class="text-xs text-[#0078C8] hover:underline">{{ $assignment->product->unique_id }}</a>
                        </div>
                        <div>
                            <span class="text-xs text-blue-600 font-semibold uppercase">Category</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $assignment->product->category->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-600">Location: {{ $assignment->product->location ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-blue-600 font-semibold uppercase">Technician</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $assignment->technician->full_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-600">{{ $assignment->technician->email ?? '' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-blue-600 font-semibold uppercase">Status</span>
                            <p class="mt-1">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">{{ $status }}</span>
                            </p>
                            <p class="text-xs text-gray-600 mt-1">Priority: {{ ucfirst($assignment->priority ?? 'N/A') }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Assignment Information</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Task Type</span>
                                <span class="font-medium text-gray-900 text-right">{{ $assignment->task_type ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Workflow Stage</span>
                                <span class="font-medium text-gray-900 text-right">{{ $assignment->workflowStage()->first()?->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Disposition</span>
                                <span class="font-medium text-gray-900 text-right">{{ $assignment->disposition()->first()?->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Assigned By</span>
                                <span class="font-medium text-gray-900 text-right">{{ $assignment->assigner->username ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Timeline</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Assigned At</span>
                                <span class="font-medium text-gray-900 text-right">{{ $assignment->assigned_at ? $assignment->assigned_at->format('M d, Y H:i') : 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Started At</span>
                                <span class="font-medium text-gray-900 text-right">{{ $assignment->started_at ? $assignment->started_at->format('M d, Y H:i') : 'Not Started' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Due Date</span>
                                <span class="font-medium text-gray-900 text-right">{{ $assignment->due_date ? $assignment->due_date->format('M d, Y') : 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">Completed At</span>
                                <span class="font-medium text-gray-900 text-right">{{ $assignment->completed_at ? $assignment->completed_at->format('M d, Y H:i') : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 mt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Instructions / Notes</h3>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $assignment->remarks ?: 'No instructions added.' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
