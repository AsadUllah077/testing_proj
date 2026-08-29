@extends('layouts.auth')

@section('title', 'Invoice Terms')

@push('styles')
    <style>
        .invoice-term-editor:empty::before {
            content: attr(data-placeholder);
            color: #9ca3af;
            pointer-events: none;
        }

        .invoice-term-editor ol,
        .invoice-term-editor ul {
            margin: 0.5rem 0 0.5rem 1.25rem;
            padding-left: 1rem;
        }

        .invoice-term-editor ol {
            list-style: decimal;
        }

        .invoice-term-editor ul {
            list-style: disc;
        }

        .invoice-term-editor p,
        .invoice-term-editor div {
            margin: 0 0 0.35rem;
        }
    </style>
@endpush

@section('content')
    <div class="mb-8">
        <div class="p-3">
            <div class="mx-auto">
                <div class="p-5 bg-white rounded-lg border border-black/10 mb-4">
                    <nav class="flex mb-4" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-[#0078C8]">
                                <a href="{{ route('account.organization-settings.index') }}" class="flex items-center">
                                    <span class="material-symbols-outlined text-base mr-1">settings</span>
                                    Settings
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center text-gray-400">
                                    <span class="material-symbols-outlined text-base">chevron_right</span>
                                    <span class="ml-1 text-sm font-semibold text-gray-900">Invoice Terms</span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Invoice Terms</h1>
                            <p class="text-sm text-gray-500 font-medium">
                                Manage fallback terms and conditions for invoice PDFs.
                            </p>
                        </div>
                        <a href="{{ route('account.organization-settings.invoice-templates.index') }}"
                            class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded hover:bg-[#0078C8] hover:text-white transition-all">
                            Invoice Templates
                        </a>
                    </div>
                </div>

                @if ($globalDefault)
                    <div class="p-4 bg-cyan-50 border border-cyan-100 rounded-lg mb-4">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-cyan-700">info</span>
                            <div>
                                <h3 class="text-sm font-bold text-cyan-900">System fallback</h3>
                                <p class="text-sm text-cyan-800 mt-1">
                                    If no company terms are active and no invoice template terms are set, the PDF uses the system default:
                                    <span class="font-semibold">{{ $globalDefault->title }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg border border-black/10 p-5 h-fit">
                        <h2 class="text-lg font-bold text-gray-900 mb-1">Create Terms</h2>
                        <p class="text-sm text-gray-500 mb-4">Add a reusable fallback terms template.</p>

                        <form method="POST" action="{{ route('account.organization-settings.invoice-term-conditions.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                <input type="text" name="title" value="{{ old('title', 'Terms and Conditions') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2"
                                    required>
                                @error('title')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Terms Content</label>
                                <div data-rich-editor-wrapper>
                                    <div class="flex flex-wrap items-center gap-1 rounded-t-md border border-gray-300 bg-gray-50 px-2 py-1.5">
                                        <button type="button" data-command="bold" title="Bold"
                                            class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                            <span class="material-symbols-outlined text-[20px]">format_bold</span>
                                        </button>
                                        <button type="button" data-command="italic" title="Italic"
                                            class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                            <span class="material-symbols-outlined text-[20px]">format_italic</span>
                                        </button>
                                        <button type="button" data-command="underline" title="Underline"
                                            class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                            <span class="material-symbols-outlined text-[20px]">format_underlined</span>
                                        </button>
                                        <span class="mx-1 h-6 border-l border-gray-300"></span>
                                        <button type="button" data-command="insertOrderedList" title="Numbered list"
                                            class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                            <span class="material-symbols-outlined text-[20px]">format_list_numbered</span>
                                        </button>
                                        <button type="button" data-command="insertUnorderedList" title="Bullet list"
                                            class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                            <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>
                                        </button>
                                        <span class="mx-1 h-6 border-l border-gray-300"></span>
                                        <button type="button" data-command="removeFormat" title="Clear formatting"
                                            class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                            <span class="material-symbols-outlined text-[20px]">format_clear</span>
                                        </button>
                                    </div>
                                    <div contenteditable="true" data-rich-editor
                                        data-placeholder="Write invoice terms here."
                                        class="invoice-term-editor min-h-[220px] w-full rounded-b-md border border-t-0 border-gray-300 p-3 text-sm text-gray-800 focus:border-[#0078C8] focus:outline-none focus:ring-2 focus:ring-cyan-100">{!! old('content') ? \App\Models\InvoiceTermCondition::sanitizeContent(old('content')) : '' !!}</div>
                                    <input type="hidden" name="content" data-rich-editor-input value="{{ old('content') }}" required>
                                </div>
                                @error('content')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Sort Order</label>
                                <input type="number" min="0" name="sort_order" value="{{ old('sort_order', 0) }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                            </div>

                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="is_active" checked
                                        class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                                    Active
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="is_default"
                                        class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                                    Default
                                </label>
                            </div>

                            <button type="submit"
                                class="w-full px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded hover:bg-[#006bb0] transition-all">
                                Save Terms
                            </button>
                        </form>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        @forelse ($terms as $term)
                            <div class="bg-white rounded-lg border border-black/10 p-5">
                                <form method="POST" action="{{ route('account.organization-settings.invoice-term-conditions.update', $term) }}" class="space-y-4">
                                    @csrf
                                    @method('PUT')

                                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-3">
                                                @if ($term->is_default)
                                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">Default</span>
                                                @endif
                                                @if ($term->is_active)
                                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">Active</span>
                                                @else
                                                    <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2 py-1 rounded-full">Inactive</span>
                                                @endif
                                            </div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                            <input type="text" name="title" value="{{ old('title', $term->title) }}"
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2"
                                                required>
                                        </div>
                                        <div class="w-full md:w-32">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Sort Order</label>
                                            <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $term->sort_order) }}"
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#0078C8] focus:border-[#0078C8] sm:text-sm border p-2">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Terms Content</label>
                                        <div data-rich-editor-wrapper>
                                            <div class="flex flex-wrap items-center gap-1 rounded-t-md border border-gray-300 bg-gray-50 px-2 py-1.5">
                                                <button type="button" data-command="bold" title="Bold"
                                                    class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                                    <span class="material-symbols-outlined text-[20px]">format_bold</span>
                                                </button>
                                                <button type="button" data-command="italic" title="Italic"
                                                    class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                                    <span class="material-symbols-outlined text-[20px]">format_italic</span>
                                                </button>
                                                <button type="button" data-command="underline" title="Underline"
                                                    class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                                    <span class="material-symbols-outlined text-[20px]">format_underlined</span>
                                                </button>
                                                <span class="mx-1 h-6 border-l border-gray-300"></span>
                                                <button type="button" data-command="insertOrderedList" title="Numbered list"
                                                    class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                                    <span class="material-symbols-outlined text-[20px]">format_list_numbered</span>
                                                </button>
                                                <button type="button" data-command="insertUnorderedList" title="Bullet list"
                                                    class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                                    <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>
                                                </button>
                                                <span class="mx-1 h-6 border-l border-gray-300"></span>
                                                <button type="button" data-command="removeFormat" title="Clear formatting"
                                                    class="h-8 w-8 rounded text-gray-600 hover:bg-white hover:text-[#0078C8]">
                                                    <span class="material-symbols-outlined text-[20px]">format_clear</span>
                                                </button>
                                            </div>
                                            <div contenteditable="true" data-rich-editor
                                                data-placeholder="Write invoice terms here."
                                                class="invoice-term-editor min-h-[180px] w-full rounded-b-md border border-t-0 border-gray-300 p-3 text-sm text-gray-800 focus:border-[#0078C8] focus:outline-none focus:ring-2 focus:ring-cyan-100">{!! \App\Models\InvoiceTermCondition::sanitizeContent(old('content', $term->content)) !!}</div>
                                            <input type="hidden" name="content" data-rich-editor-input value="{{ old('content', $term->content) }}" required>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-4">
                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="is_active" {{ $term->is_active ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                                                Active
                                            </label>
                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="is_default" {{ $term->is_default ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                                                Default
                                            </label>
                                        </div>

                                        <button type="submit"
                                            class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded hover:bg-[#006bb0] transition-all">
                                            Update
                                        </button>
                                    </div>
                                </form>

                                <form method="POST"
                                    action="{{ route('account.organization-settings.invoice-term-conditions.destroy', $term) }}"
                                    class="flex justify-end mt-3"
                                    onsubmit="return confirm('Delete this invoice terms template?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-50 text-red-700 text-sm font-medium rounded hover:bg-red-100 transition-colors">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="bg-white rounded-lg border border-black/10 p-10 text-center">
                                <span class="material-symbols-outlined text-5xl text-gray-300">contract</span>
                                <h3 class="text-lg font-semibold text-gray-900 mt-3">No company invoice terms yet</h3>
                                <p class="text-sm text-gray-500 mt-1">Create a fallback terms template for your company.</p>
                            </div>
                        @endforelse

                        <x-data-table-paginatior :paginator="$terms" />
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-rich-editor-wrapper]').forEach((wrapper) => {
            const editor = wrapper.querySelector('[data-rich-editor]');
            const input = wrapper.querySelector('[data-rich-editor-input]');
            const form = wrapper.closest('form');

            const syncContent = () => {
                input.value = editor.innerHTML.trim();
            };

            wrapper.querySelectorAll('[data-command]').forEach((button) => {
                button.addEventListener('click', () => {
                    editor.focus();
                    document.execCommand(button.dataset.command, false, null);
                    syncContent();
                });
            });

            editor.addEventListener('input', syncContent);
            editor.addEventListener('blur', syncContent);

            if (form) {
                form.addEventListener('submit', syncContent);
            }
        });
    </script>
@endpush
