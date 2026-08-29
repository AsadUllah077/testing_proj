{{-- @foreach ($node->children as $child)
    <li class="{{ $child->id == $current->id ? 'text-blue-600 font-semibold' : 'text-gray-700' }}">
        ↳ {{ $child->name }}

        @if ($child->childrenRecursive->count())
            <ul class="ml-6 mt-2 border-l pl-4">
                @include('components.acccount.coa.hierarchy-node', [
                    'node' => $child,
                    'current' => $current,
                ])
            </ul>
        @endif
    </li>
@endforeach --}}


@foreach ($node->children as $child)
    <li class="relative group">
        <div
            class="flex items-center gap-2 py-1.5 px-3 rounded-lg transition-all
            {{ $child->id == $current->id
                ? 'bg-blue-100 border-l-4 border-[#0078C8] text-blue-700 font-bold shadow-sm'
                : 'text-gray-700 hover:bg-gray-100 hover:pl-4' }}">

            <!-- Arrow Icon -->
            <svg class="w-4 h-4 {{ $child->id == $current->id ? 'text-[#0078C8]' : 'text-gray-400' }}" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>

            <!-- Account Name -->
            <span class="flex-1">{{ $child->name }}</span>

            <!-- Badge for current account -->
            @if ($child->id == $current->id)
                <span class="bg-[#0078C8] text-white px-2 py-0.5 rounded-full text-xs font-semibold">
                    Current
                </span>
            @endif
        </div>

        @if ($child->childrenRecursive->count())
            <ul class="ml-8 mt-2 border-l-2 border-gray-200 pl-4 space-y-1">
                @include('components.acccount.coa.hierarchy-node', [
                    'node' => $child,
                    'current' => $current,
                ])
            </ul>
        @endif
    </li>
@endforeach
