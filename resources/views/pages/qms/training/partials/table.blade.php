<div class="qms-table-wrap">
    <table class="qms-table">
        <thead>
            <tr>
                <th>Training Name</th>
                <th>Linked SOP / Policy</th>
                <th>Assigned</th>
                <th>Completion</th>
                <th>Certification</th>
                <th>Status</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trainings as $training)
            <tr id="row-{{ $training->id }}">

                {{-- Title --}}
                <td>
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--accent-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 14px;">🎓</span>
                        </div>
                        <div>
                            <p style="font-weight: 600; color: var(--text); line-height: 1.3;">{{ $training->title }}</p>
                            @if($training->description)
                                <p style="font-size: 11px; color: var(--text3); margin-top: 2px;">{{ Str::limit($training->description, 50) }}</p>
                            @endif
                        </div>
                    </div>
                </td>

                {{-- Linked --}}
                <td>
                    @if($training->sop)
                        <span class="inline-badge" style="background: rgba(99, 120, 255, 0.1); color: var(--accent);">
                            SOP: {{ Str::limit($training->sop->title, 15) }}
                        </span>
                    @endif
                    @if($training->policy)
                        <span class="inline-badge" style="background: rgba(167, 139, 250, 0.1); color: var(--accent2);">
                            Policy: {{ Str::limit($training->policy->title, 15) }}
                        </span>
                    @endif
                    @if(!$training->sop && !$training->policy)
                        <span style="color: var(--text3);">—</span>
                    @endif
                </td>

                {{-- Assigned Employees --}}
                <td>
                    <span style="font-weight: 600;">{{ $training->employees->count() }}</span>
                    <span style="font-size: 11px; color: var(--text3); margin-left: 2px;">employees</span>
                </td>

                {{-- Completion Progress --}}
                <td>
                    @php
                        $total     = $training->employees->count();
                        $completed = $training->employees->where('completion_status', 'completed')->count();
                        $pct       = $total > 0 ? round(($completed / $total) * 100) : 0;
                    @endphp
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="flex: 1; height: 6px; background: var(--surface2); border-radius: 10px; overflow: hidden; border: 1px solid var(--border);">
                            <div style="width: {{ $pct }}%; height: 100%; background: var(--accent); border-radius: 10px;"></div>
                        </div>
                        <span style="font-size: 11px; font-weight: 600; color: var(--text2);">{{ $pct }}%</span>
                    </div>
                </td>

                {{-- Certification --}}
                <td>
                    @if($training->certification_enabled)
                        <span class="qms-badge" style="color: var(--review); background: var(--review-bg);">
                            ⭐ Certificate
                        </span>
                    @else
                        <span style="color: var(--text3); font-size: 11px;">None</span>
                    @endif
                </td>

                {{-- Status --}}
                <td>
                    @php
                        $smap = [
                            'pending'     => ['Pending',     'qms-badge-draft'],
                            'in_progress' => ['In Progress', 'qms-badge-inprogress'],
                            'completed'   => ['Completed',   'qms-badge-approved']
                        ];
                        [$lbl,$cls] = $smap[$training->status] ?? [ucfirst($training->status),'qms-badge-draft'];
                    @endphp
                    <span class="qms-badge {{ $cls }}">{{ $lbl }}</span>
                </td>

                {{-- Actions --}}
                <td style="text-align: center; position: relative;">
                    <div class="row-actions" style="opacity: 1; display: flex; justify-content: center; gap: 6px;">
                        <button class="icon-btn dropdown-btn" title="Actions">⋮</button>
                        <div class="dropdown-menu hidden absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <a href="{{ route('qms.training.show', $training->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                👁️ View Details
                            </a>
                            <a href="{{ route('qms.training.edit', $training->id) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                ✏️ Edit Session
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <button type="button" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left"
                                data-url="{{ route('qms.training.destroy', $training->id) }}"
                                data-row="#row-{{ $training->id }}"
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <p class="text-gray-400 text-sm">No trainings found.</p>
                        <a href="{{ route('qms.training.create') }}" class="text-[#0078C8] text-sm hover:underline">Create first training</a>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<x-data-table-paginatior :paginator="$trainings" />
