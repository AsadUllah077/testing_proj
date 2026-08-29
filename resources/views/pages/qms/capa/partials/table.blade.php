<div class="qms-table-wrap">
    <table class="qms-table">
        <thead>
            <tr>
                <th>Issue Details</th>
                <th>Linked Product</th>
                <th>Root Cause</th>
                <th>Assigned To</th>
                <th>Status</th>
                <th>Deadline</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($capas as $capa)
            <tr id="row-{{ $capa->id }}">

                {{-- Issue --}}
                <td>
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 14px;">⚠️</span>
                        </div>
                        <div>
                            <p style="font-weight: 600; color: var(--text); line-height: 1.3;">{{ $capa->title }}</p>
                            <p style="font-size: 11px; color: var(--text3); margin-top: 2px;">{{ $capa->capa_number }}</p>
                        </div>
                    </div>
                </td>

                <td>{{ $capa->linked_product ?? '—' }}</td>

                {{-- Root Cause --}}
                <td style="max-width: 180px;">
                    <span style="font-size: 13px; color: var(--text2); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $capa->root_cause ? Str::limit($capa->root_cause, 60) : '—' }}
                    </span>
                </td>

                {{-- Assigned To --}}
                <td>
                    @if($capa->assignee)
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; border: 1px solid var(--accent);">
                                {{ strtoupper(substr($capa->assignee->name ?? 'U', 0, 1)) }}
                            </div>
                            <span style="font-size: 13px; font-weight: 500;">{{ $capa->assignee->name }}</span>
                        </div>
                    @else
                        <span style="color: var(--text3); font-size: 13px;">—</span>
                    @endif
                </td>

                {{-- Status --}}
                <td>
                    @php
                        $smap = [
                            'open'        => ['Open',        'qms-badge-open'],
                            'in_progress' => ['In Progress', 'qms-badge-inprogress'],
                            'closed'      => ['Closed',      'qms-badge-closed'],
                        ];
                        [$lbl,$cls] = $smap[$capa->status] ?? [ucfirst($capa->status),'qms-badge-draft'];
                    @endphp
                    <span class="qms-badge {{ $cls }}">
                        {{ $lbl }}
                    </span>
                </td>

                {{-- Deadline --}}
                <td>
                    @if($capa->deadline)
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 13px; {{ $capa->deadline->isPast() && $capa->status !== 'closed' ? 'color: #dc2626; font-weight: 600;' : 'color: var(--text2);' }}">
                                {{ $capa->deadline->format('M d, Y') }}
                            </span>
                            @if($capa->deadline->isPast() && $capa->status !== 'closed')
                                <span style="font-size: 10px; color: #ef4444; font-weight: 700; text-transform: uppercase;">Overdue</span>
                            @endif
                        </div>
                    @else
                        <span style="color: var(--text3);">—</span>
                    @endif
                </td>

                {{-- Actions --}}
                <td style="text-align: center; position: relative;">
                    <div class="row-actions" style="opacity: 1; display: flex; justify-content: center; gap: 6px;">
                        <button class="icon-btn dropdown-btn" title="Actions">⋮</button>
                        <div class="dropdown-menu hidden absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <a href="{{ route('qms.capa.show', $capa->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                👁️ View Details
                            </a>
                            <a href="{{ route('qms.capa.edit', $capa->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                ✏️ Edit Action
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <button type="button" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left"
                                data-url="{{ route('qms.capa.destroy', $capa->id) }}"
                                data-row="#row-{{ $capa->id }}"
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-gray-400 text-sm">No CAPAs found.</p>
                        <a href="{{ route('qms.capa.create') }}" class="text-[#0078C8] text-sm hover:underline">Create first CAPA</a>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<x-data-table-paginatior :paginator="$capas" />
