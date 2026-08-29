<?php

return [
    'date_window_days' => (int) env('BANK_MATCH_DATE_WINDOW_DAYS', 14),
    'document_days_before' => (int) env('BANK_MATCH_DOCUMENT_DAYS_BEFORE', 30),
    'document_days_after' => (int) env('BANK_MATCH_DOCUMENT_DAYS_AFTER', 7),
    'amount_tolerance' => (float) env('BANK_MATCH_AMOUNT_TOLERANCE', 0.01),
    'confidence' => [
        'high' => (int) env('BANK_MATCH_HIGH_CONFIDENCE', 85),
        'medium' => (int) env('BANK_MATCH_MEDIUM_CONFIDENCE', 60),
    ],
];
