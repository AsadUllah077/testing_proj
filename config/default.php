<?php
$today = date('Y-m-d');
return [
    'default_tax' => [
        'default_tax' => 'Default Tax',
        'default_sale_tax' => 'Default Sale Tax',
        'default_purchase_tax' => 'Default purchase Tax',
        'sales_tax' => 'Sales Tax',
        'purchase_tax' => 'Purchase Tax',
    ],

    'tax_period' => [
        'annually' => 'Annually',
        'quarter' => 'Quarter',
        '1month' => '1 Month',
        '2month' => '2 Month',
        '3month' => '3 Month',
        '4month' => '4 Month',
        '5month' => '5 Month',
        '6month' => '6 Month',
    ],

    'per_page' => [
        10,
        20,
        50,
        75,
        100,
        125,
        150,
        200,
        300,
        400,
        500,
        750,
        1000,
    ],

    'per_date' => [

        'TODAY' => $today . '|' . $today,

        'THIS WEEK' => date('Y-m-d', strtotime('monday this week')) . '|' . date('Y-m-d', strtotime('sunday this week')),

        'THIS MONTH' => date('Y-m-01') . '|' . date('Y-m-t'),

        'PREVIOUS MONTH' => date('Y-m-01', strtotime('first day of previous month')) . '|' . date('Y-m-t',  strtotime('last day of previous month')),

        'LAST 3 MONTHS' => date('Y-m-d', strtotime('-3 months')) . '|' . $today,

        'LAST 6 MONTHS' => date('Y-m-d', strtotime('-6 months')) . '|' . $today,

        'LAST 9 MONTHS' => date('Y-m-d', strtotime('-9 months')) . '|' . $today,

        'YEAR TO DATE' => date('Y-01-01') . '|' . $today,

        'LAST 1 YEAR'  => date('Y-m-d', strtotime('-1 year')) . '|' . $today,

        'LAST 3 YEARS' => date('Y-m-d', strtotime('-3 years')) . '|' . $today,

        'LAST 6 YEARS' => date('Y-m-d', strtotime('-6 years')) . '|' . $today,

        'LAST 9 YEARS' => date('Y-m-d', strtotime('-9 years')) . '|' . $today,

        'ALL TIME' => '1900-01-01|' . $today,
        'CUSTOM' => 'CUSTOM',
    ],

    'date_ranges' => [
        'today'             => 'Today',
        'week'              => 'This Week',
        'month'             => 'This Month',
        'year'              => 'This Year',

        'last_7_days'       => 'Last 7 Days',
        'last_30_days'      => 'Last 30 Days',
        'last_90_days'      => 'Last 90 Days',

        'unlimited_before'  => 'Unlimited Before (All History)',
        'unlimited_after'   => 'Unlimited After (All Future)',

        'years_range_10'    => '± 10 Years',
        'years_range_25'    => '± 25 Years',
        'years_range_50'    => '± 50 Years',
    ],
];
