@extends('layouts.auth')

@section('title', 'Take Survey')

@section('content')
<div class="mt-8">
    <div class="mx-auto max-w-3xl px-4">
        <div class="bg-white p-6 rounded-lg  border border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $survey->title }}</h2>
            @if($survey->description)
                <p class="text-gray-600 mb-6">{{ $survey->description }}</p>
            @endif
            <form id="surveyForm" method="POST" action="{{ route('hrm.surveys.submit', $survey->id) }}">
                @csrf
                @foreach($survey->questions as $question)
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $question->question_text }}
                            @if($question->is_required)
                                <span class="text-red-600">*</span>
                            @endif
                        </label>
                        @php $fieldName = 'answers['.$question->id.']'; @endphp
                        @switch($question->type)
                            @case('text')
                                <input type="text" name="{{ $fieldName }}" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-[#0078C8]" {{ $question->is_required ? 'required' : '' }} />
                                @break
                            @case('textarea')
                                <textarea name="{{ $fieldName }}" rows="3" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-[#0078C8]" {{ $question->is_required ? 'required' : '' }}></textarea>
                                @break
                            @case('radio')
                                @foreach($question->options as $opt)
                                    <div class="flex items-center mb-1">
                                        <input type="radio" name="{{ $fieldName }}" value="{{ $opt->option_text }}" class="text-[#0078C8] focus:ring-2 focus:ring-[#0078C8]" {{ $question->is_required ? 'required' : '' }} />
                                        <span class="ml-2 text-sm text-gray-700">{{ $opt->option_text }}</span>
                                    </div>
                                @endforeach
                                @break
                            @case('checkbox')
                                @foreach($question->options as $opt)
                                    <div class="flex items-center mb-1">
                                        <input type="checkbox" name="{{ $fieldName }}[]" value="{{ $opt->option_text }}" class="text-[#0078C8] focus:ring-2 focus:ring-[#0078C8]" />
                                        <span class="ml-2 text-sm text-gray-700">{{ $opt->option_text }}</span>
                                    </div>
                                @endforeach
                                @break
                            @case('dropdown')
                                <select name="{{ $fieldName }}" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-[#0078C8] select2" {{ $question->is_required ? 'required' : '' }}>
                                    <option value="" disabled selected>Select an option</option>
                                    @foreach($question->options as $opt)
                                        <option value="{{ $opt->option_text }}">{{ $opt->option_text }}</option>
                                    @endforeach
                                </select>
                                @break
                            @case('rating')
                                <select name="{{ $fieldName }}" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-[#0078C8] select2" {{ $question->is_required ? 'required' : '' }}>
                                    <option value="" disabled selected>Rate (1-5)</option>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                                @break
                            @case('date')
                                <input type="date" name="{{ $fieldName }}" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-[#0078C8] date" {{ $question->is_required ? 'required' : '' }} />
                                @break
                        @endswitch
                        @error('answers.' . $question->id)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
                <div class="flex items-center justify-between">
                    <button type="submit" class="px-4 py-2 bg-[#0078C8] text-white font-semibold rounded hover:bg-[#006bb0] transition-colors">
                        Submit Survey
                    </button>
                    <a href="{{ route('hrm.surveys.active') }}" class="text-sm text-gray-600 hover:underline">Back to Active Surveys</a>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.getElementById('surveyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const data = new FormData(form);
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: data
            });
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                window.location.href = result.redirect_to;
            } else {
                alert(result.message);
            }
        } catch (err) {
            console.error(err);
            alert('An unexpected error occurred.');
        }
    });
</script>
@endpush
@endsection
