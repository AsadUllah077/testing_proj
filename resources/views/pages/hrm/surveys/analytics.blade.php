@extends('layouts.auth')

@section('title', 'Survey Analytics')

@section('content')
<div class="mt-4">
    <div class="mx-auto px-4 max-w-5xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Survey Analytics – {{ $survey->title }}</h1>
            <a href="{{ route('hrm.surveys.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">Back to Surveys</a>
        </div>

        <div class="bg-white  rounded-lg p-6 mb-6">
            <p class="text-sm text-gray-600">{{ $survey->description ?? 'No description provided.' }}</p>
            <div class="mt-4 flex space-x-4 text-sm text-gray-500">
                <span>Status: <strong class="text-gray-800">{{ $survey->status }}</strong></span>
                <span>Period: <strong class="text-gray-800">{{ $survey->start_date?->format('M d, Y') }} – {{ $survey->end_date?->format('M d, Y') }}</strong></span>
                <span>Responses: <strong class="text-gray-800">{{ $totalResponses }}</strong> / {{ $totalAssignees }} ({{ $responsePercent }}%)</span>
            </div>
        </div>

        @foreach($questionAnalytics as $qa)
            <div class="bg-white  rounded-lg p-4 mb-4">
                <h3 class="font-medium text-gray-800 mb-2">{{ $qa['question']->question_text }}</h3>
                @php $type = $qa['question']->type; @endphp
                @if($type === 'rating')
                    <p class="text-sm mb-2">Average rating: <strong>{{ $qa['average'] }}</strong></p>
                    <div class="grid grid-cols-5 gap-2">
                        @foreach($qa['distribution'] as $score => $count)
                            <div class="bg-gray-100 p-2 rounded text-center">
                                  <span class="block text-xs text-gray-600">{{ $score }}★</span>
                                <span class="font-semibold">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif(in_array($type, ['radio', 'dropdown']))
                    <ul class="list-disc list-inside">
                        @foreach($qa['distribution'] as $option => $cnt)
                            <li>{{ $option }}: {{ $cnt }}</li>
                        @endforeach
                    </ul>
                @elseif($type === 'checkbox')
                    <ul class="list-disc list-inside">
                        @foreach($qa['distribution'] as $option => $cnt)
                            <li>{{ $option }}: {{ $cnt }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-600">Sample responses:</p>
                    <ul class="list-disc list-inside">
                        @foreach($qa['responses'] as $resp)
                            <li class="text-xs">{{ $resp }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
