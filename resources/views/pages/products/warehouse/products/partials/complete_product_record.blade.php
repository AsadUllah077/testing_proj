@php
    $fieldLabels = [
        'id' => 'Database ID',
        'sold_status' => 'Sold',
        'scrap_status' => 'Scrapped',
        'warehouse_location_id' => 'Warehouse Location',
        'unique_id' => 'Unique ID',
        'dpn_sn_number' => 'DPN/SN Number',
        'title' => 'Title',
        'count' => 'Count',
        'manual_status' => 'Product Type',
        'product_details' => 'Product Details',
        'remarks' => 'Remarks',
        'problem' => 'Problem',
        'category_id' => 'Category',
        'assigned_to' => 'Assigned Employee',
        'disposition_id' => 'Disposition',
        'work_flow_stage_id' => 'Workflow Stage',
        'last_action' => 'Last Action',
        'specifications' => 'Specifications',
        'product_id_value' => 'Product ID Value',
        'status' => 'Status',
        'company_id' => 'Company',
        'created_by' => 'Created By',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'deleted_at' => 'Deleted At',
    ];

    $relationValue = static function ($relation, $id, string $nameField = 'name'): string {
        if ($relation) {
            return ($relation->{$nameField} ?? 'N/A').' (ID: '.$id.')';
        }

        return $id === null ? 'N/A' : 'ID: '.$id;
    };

    $fieldValues = [
        'sold_status' => (bool) $product->sold_status ? 'Yes (1)' : 'No (0)',
        'scrap_status' => (bool) $product->scrap_status ? 'Yes (1)' : 'No (0)',
        'manual_status' => (int) $product->manual_status === 1 ? 'Manual / Bulk (1)' : 'Individual / Barcode (0)',
        'warehouse_location_id' => $relationValue($product->warehouseLocation, $product->warehouse_location_id),
        'category_id' => $relationValue($product->category, $product->category_id),
        'assigned_to' => $relationValue($product->assignedTo, $product->assigned_to, 'full_name'),
        'disposition_id' => $relationValue($product->disposition, $product->disposition_id),
        'work_flow_stage_id' => $relationValue($product->workflowStage, $product->work_flow_stage_id),
        'company_id' => $relationValue($product->company, $product->company_id),
        'created_by' => $relationValue($product->createdBy, $product->created_by, 'username'),
        'specifications' => count($product->specifications ?? []).' specification(s)',
        'created_at' => $product->created_at?->format('M d, Y g:i A'),
        'updated_at' => $product->updated_at?->format('M d, Y g:i A'),
        'deleted_at' => $product->deleted_at?->format('M d, Y g:i A'),
    ];

    $wideFields = ['title', 'product_details', 'remarks', 'problem', 'last_action'];
@endphp

<section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
        <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
            <svg class="h-5 w-5 text-[#0078C8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Complete Product Record
        </h2>
        <p class="mt-1 text-xs text-gray-500">Every field stored on this product, including internal identifiers and audit timestamps.</p>
    </div>

    <dl class="grid grid-cols-1 gap-px bg-gray-200 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($product->getAttributes() as $field => $rawValue)
            @php
                $value = array_key_exists($field, $fieldValues) ? $fieldValues[$field] : $rawValue;
                $value = $value === null || $value === '' ? 'N/A' : $value;
                $isWide = in_array($field, $wideFields, true);
            @endphp
            <div class="bg-white px-5 py-4 {{ $isWide ? 'sm:col-span-2 xl:col-span-3' : '' }}">
                <dt class="text-[11px] font-bold uppercase tracking-wider text-gray-500">
                    {{ $fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field)) }}
                </dt>
                <dd class="mt-1 break-words text-sm font-medium text-gray-900">{{ $value }}</dd>
                {{-- @if (isset($fieldLabels[$field]) && $fieldLabels[$field] !== ucwords(str_replace('_', ' ', $field)))
                    <div class="mt-1 font-mono text-[10px] text-gray-400">{{ $field }}</div>
                @endif --}}
            </div>
        @endforeach
    </dl>
</section>
