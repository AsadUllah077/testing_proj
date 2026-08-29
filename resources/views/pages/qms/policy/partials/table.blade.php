<div class="qms-table-wrap">
    <table class="qms-table">
        <thead>
            <tr>
                <th style="width: 40px;">
                    <input type="checkbox" id="checkAll" class="rounded border-gray-300 text-[#0078C8]">
                </th>
                <th>Policy Name</th>
                <th>Owner</th>
                <th>Version</th>
                <th>Status</th>
                <th>Department</th>
                <th>Last Updated</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($policies as $policy)
            <tr id="row-{{ $policy->id }}">
                <td>
                    <input type="checkbox" class="row-check rounded border-gray-300 text-[#0078C8]" data-id="{{ $policy->id }}">
                </td>

                {{-- Policy Name --}}
                <td>
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--accent-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 14px;">📋</span>
                        </div>
                        <div>
                            <p style="font-weight: 600; color: var(--text); line-height: 1.3;">{{ $policy->title }}</p>
                            <p style="font-size: 11px; color: var(--text3); margin-top: 2px;">{{ $policy->policy_number }}</p>
                        </div>
                    </div>
                </td>

                {{-- Owner (Previously Category) --}}
                <td style="color: var(--text2); font-size: 13px;">
                    {{ $policy->creator->full_name ?: $policy->creator->username ?: 'System' }}
                </td>

                {{-- Version --}}
                <td>
                    <span class="inline-badge">v{{ $policy->version ?: '1.0' }}</span>
                </td>

                {{-- Status --}}
                <td>
                    @php
                        $statusMap = [
                            'draft'        => ['Draft',        'qms-badge-draft'],
                            'active'       => ['Approved',     'qms-badge-approved'],
                            'under_review' => ['Review',       'qms-badge-review'],
                            'archived'     => ['Archived',     'qms-badge-archived'],
                        ];
                        [$label, $cls] = $statusMap[$policy->status] ?? [ucfirst($policy->status), 'qms-badge-draft'];
                    @endphp
                    <span class="qms-badge {{ $cls }}">
                        {{ $label }}
                    </span>
                </td>

                {{-- Department --}}
                <td>{{ $policy->department ?? '—' }}</td>

                {{-- Last Updated --}}
                <td style="color: var(--text3);">{{ $policy->updated_at->format('M d, Y') }}</td>

                {{-- Actions --}}
                <td style="text-align: center; position: relative;">
                    <div class="row-actions" style="opacity: 1; display: flex; justify-content: center; gap: 6px;">
                         <button class="icon-btn dropdown-btn" title="Actions">⋮</button>
                         <div class="dropdown-menu hidden absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <a href="{{ route('qms.policy.show', $policy->id) }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                👁️ View Details
                            </a>
                            <a href="{{ route('qms.policy.edit', $policy->id) }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                ✏️ Edit Policy
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <button type="button" class="flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left"
                                data-url="{{ route('qms.policy.destroy', $policy->id) }}"
                                data-row="#row-{{ $policy->id }}"
                                onclick="confirmDelete(this)">
                                🗑️ Delete
                            </button>
                         </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-5 py-12 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-gray-400 text-sm">No policies found.</p>
                        <a href="{{ route('qms.policy.create') }}" class="text-[#0078C8] text-sm hover:underline">Create your first policy</a>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<x-data-table-paginatior :paginator="$policies" />
