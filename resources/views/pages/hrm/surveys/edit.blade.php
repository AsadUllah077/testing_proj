@extends('layouts.auth')

@section('title', 'Edit Survey')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Edit Survey</h2>
                        <p class="text-gray-600 text-sm mt-1">Modify the details of the selected survey</p>
                    </div>
                    <div>
                        <a href="{{ route('hrm.surveys.index') }}"
                           class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-xs font-normal">Back</a>
                    </div>
                </div>

                <form id="formSubmit" method="POST" action_url="{{ route('hrm.surveys.update', $survey->id) }}" back_url="{{ route('hrm.surveys.index') }}"
                      class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-600">*</span></label>
                            <input type="text" name="title" value="{{ old('title', $survey->title) }}" required
                                   class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-600">*</span></label>
                            <select name="status" required class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none select2">
                                <option value="Draft" {{ $survey->status == 'Draft' ? 'selected' : '' }}>Draft</option>
                                <option value="Published" {{ $survey->status == 'Published' ? 'selected' : '' }}>Published</option>
                                <option value="Closed" {{ $survey->status == 'Closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-600">*</span></label>
                            <input type="date" name="start_date" value="{{ old('start_date', $survey->start_date?->format('Y-m-d')) }}" required
                                   class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none date" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-red-600">*</span></label>
                            <input type="date" name="end_date" value="{{ old('end_date', $survey->end_date?->format('Y-m-d')) }}" required
                                   class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none date" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">{{ old('description', $survey->description) }}</textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_anonymous" value="1" {{ $survey->is_anonymous ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-[#0078C8] focus:ring-2 focus:ring-[#0078C8] focus:outline-none" />
                                <span class="ml-2 text-sm text-gray-700">Anonymous Survey</span>
                            </label>
                        </div>
                    </div>

                    <hr class="my-6" />

                    <h2 class="text-lg font-semibold text-gray-800 mb-3">Assign To</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assignment Type <span class="text-red-600">*</span></label>
                            <select id="assign_type" name="assign_type" required class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none select2">
                                <option value="All" {{ $survey->assignment_type == 'All' ? 'selected' : '' }}>All Employees</option>
                                <option value="Department" {{ $survey->assignment_type == 'Department' ? 'selected' : '' }}>Department</option>
                                <option value="Team" {{ $survey->assignment_type == 'Team' ? 'selected' : '' }}>Team</option>
                                <option value="Employee" {{ $survey->assignment_type == 'Employee' ? 'selected' : '' }}>Specific Employee</option>
                            </select>
                        </div>
                        <div id="assign_ids_container" class="col-span-2 hidden">
                            <label class="block text-sm font-medium text-gray-700" id="assign_ids_label">Select</label>
                            <select name="assign_ids[]" id="assign_ids" multiple class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none select2"></select>
                        </div>
                    </div>

                    <hr class="my-6" />

                    <h2 class="text-lg font-semibold text-gray-800 mb-3">Questions</h2>
                    <div id="questions_container">
            @foreach($survey->questions as $qIndex => $question)
                <div class="border rounded-lg p-4 mb-4 bg-gray-50">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-medium text-gray-800">Question #{{ $qIndex + 1 }}</h3>
                        <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-gray-500 hover:text-red-600">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700">Question Text <span class="text-red-600">*</span></label>
                        <input type="text" name="questions[{{ $qIndex }}][question_text]" required class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" value="{{ old('questions.' . $qIndex . '.question_text', $question->question_text) }}" />
                    </div>
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700">Type <span class="text-red-600">*</span></label>
                        <select class="question-type" data-index="{{ $qIndex }}" name="questions[{{ $qIndex }}][type]" required class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">
                            <option value="text" {{ $question->type == 'text' ? 'selected' : '' }}>Text Input</option>
                            <option value="textarea" {{ $question->type == 'textarea' ? 'selected' : '' }}>Textarea</option>
                            <option value="radio" {{ $question->type == 'radio' ? 'selected' : '' }}>Radio Button</option>
                            <option value="checkbox" {{ $question->type == 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                            <option value="dropdown" {{ $question->type == 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                            <option value="rating" {{ $question->type == 'rating' ? 'selected' : '' }}>Rating (1-5)</option>
                        </select>
                    </div>
                    @if(in_array($question->type, ['radio','checkbox','dropdown']))
                    <div class="options-container" id="options_{{ $qIndex }}">
                        <label class="block text-sm font-medium text-gray-700">Options (one per line)</label>
                        <textarea name="questions[{{ $qIndex }}][options]" rows="3" class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">{{ old('questions.' . $qIndex . '.options', $question->options) }}</textarea>
                    </div>
                    @endif
                    <div class="flex items-center">
                        <input type="checkbox" name="questions[{{ $qIndex }}][is_required]" value="1" class="rounded border-gray-300 text-[#0078C8] focus:ring-2 focus:ring-[#0078C8] focus:outline-none" {{ $question->is_required ? 'checked' : '' }} />
                        <span class="ml-2 text-sm text-gray-700">Required</span>
                    </div>
                </div>
            @endforeach
        </div>
                    <button type="button" id="add_question_btn" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-colors">
                        <i class="fa-solid fa-plus"></i> Add Question
                    </button>

                    <div class="mt-8 flex items-center">
                        <button type="submit" class="w-full px-4 py-2 bg-[#0078C8] text-white font-semibold rounded-lg hover:bg-[#006bb0] transition-colors">
                            Update Survey
                        </button>
                        <a href="{{ route('hrm.surveys.index') }}" class="ml-3 px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium text-sm">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const employees = @json($employees);
        const departments = @json($departments ?? []);
        const teams = @json($teams ?? []);
        let questionIndex = {{ $survey->questions->count() ?? 0 }};

        function renderAssignOptions() {
            const type = document.getElementById('assign_type').value;
            const container = document.getElementById('assign_ids_container');
            const label = document.getElementById('assign_ids_label');
            const select = document.getElementById('assign_ids');
            select.innerHTML = '';
            if (type === 'All') {
                container.classList.add('hidden');
                return;
            }
            let data = [];
            if (type === 'Department') data = departments;
            if (type === 'Team') data = teams;
            if (type === 'Employee') data = employees;
            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name || item.full_name || item.title;
                select.appendChild(opt);
            });
            label.textContent = type === 'Employee' ? 'Select Employees' : type + 's';
            container.classList.remove('hidden');
        }
        document.getElementById('assign_type').addEventListener('change', renderAssignOptions);
        renderAssignOptions();

        function addQuestion() {
            const container = document.getElementById('questions_container');
            const qId = questionIndex++;
            const qDiv = document.createElement('div');
            qDiv.className = 'border rounded-lg p-4 mb-4 bg-gray-50';
            qDiv.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-medium text-gray-800">Question #${qId + 1}</h3>
                    <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-gray-500 hover:text-red-600">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium text-gray-700">Question Text <span class="text-red-600">*</span></label>
                    <input type="text" name="questions[${qId}][question_text]" required class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" />
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium text-gray-700">Type <span class="text-red-600">*</span></label>
                    <select class="question-type" data-index="${qId}" name="questions[${qId}][type]" required class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">
                        <option value="text">Text Input</option>
                        <option value="textarea">Textarea</option>
                        <option value="radio">Radio Button</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="dropdown">Dropdown</option>
                        <option value="rating">Rating (1-5)</option>
                    </select>
                </div>
                <div class="options-container hidden" id="options_${qId}">
                    <label class="block text-sm font-medium text-gray-700">Options (one per line)</label>
                    <textarea name="questions[${qId}][options]" rows="3" class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none"></textarea>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="questions[${qId}][is_required]" value="1" class="rounded border-gray-300 text-[#0078C8] focus:ring-2 focus:ring-[#0078C8] focus:outline-none" />
                    <span class="ml-2 text-sm text-gray-700">Required</span>
                </div>
            `;
            container.appendChild(qDiv);
            qDiv.querySelector('.question-type').addEventListener('change', function(){
                const idx = this.dataset.index;
                const optDiv = document.getElementById('options_' + idx);
                if(['radio','checkbox','dropdown'].includes(this.value)) {
                    optDiv.classList.remove('hidden');
                } else {
                    optDiv.classList.add('hidden');
                }
            });
        }
        document.getElementById('add_question_btn').addEventListener('click', addQuestion);
    </script>
@endpush
