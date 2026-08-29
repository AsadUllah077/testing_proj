<div class="qms-table-wrap">
    <table class="qms-table">
        <thead>
            <tr>
                <th>SOP Title</th>
                <th>Department</th>
                <th>Linked Product</th>
                <th>Version</th>
                <th>Status</th>
                <th>Last Updated</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sops as $sop)
            <tr id="row-{{ $sop->id }}">

                {{-- Title --}}
                <td>
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--accent-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 14px;">📂</span>
                        </div>
                        <div>
                            <p style="font-weight: 600; color: var(--text); line-height: 1.3;">{{ $sop->title }}</p>
                            <p style="font-size: 11px; color: var(--text3); margin-top: 2px;">{{ $sop->sop_number }} · {{ $sop->steps_count ?? $sop->steps->count() }} steps</p>
                        </div>
                    </div>
                </td>

                <td>{{ $sop->department ?? '—' }}</td>
                <td>{{ $sop->linked_product ?? '—' }}</td>

                {{-- Version --}}
                <td>
                    <span class="inline-badge">v{{ $sop->version }}</span>
                </td>

                {{-- Status --}}
                <td>
                    @php
                        $map = [
                            'draft'    => ['Draft',    'qms-badge-draft'],
                            'review'   => ['Review',   'qms-badge-review'],
                            'approved' => ['Approved', 'qms-badge-approved'],
                            'archived' => ['Archived', 'qms-badge-archived'],
                        ];
                        [$lbl, $cls] = $map[$sop->status] ?? [ucfirst($sop->status), 'qms-badge-draft'];
                    @endphp
                    <span class="qms-badge {{ $cls }}">
                        {{ $lbl }}
                    </span>
                </td>

                <td style="color: var(--text3);">{{ $sop->updated_at->format('M d, Y') }}</td>

                {{-- Actions --}}
                <td style="text-align: center; position: relative;">
                    <div class="row-actions" style="opacity: 1; display: flex; justify-content: center; gap: 6px;">
                        <button class="icon-btn dropdown-btn" title="Actions">⋮</button>
                        <div class="dropdown-menu hidden absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <a href="{{ route('qms.sop.show', $sop->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                👁️ View SOP
                            </a>
                            <a href="{{ route('qms.sop.edit', $sop->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                ✏️ Edit
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <button type="button" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left"
                                data-url="{{ route('qms.sop.destroy', $sop->id) }}"
                                data-row="#row-{{ $sop->id }}"
                                onclick="confirmDelete(this)">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-12 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-gray-400 text-sm">No SOPs found.</p>
                        <a href="{{ route('qms.sop.create') }}" class="text-[#0078C8] text-sm hover:underline">Create your first SOP</a>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<x-data-table-paginatior :paginator="$sops" />
