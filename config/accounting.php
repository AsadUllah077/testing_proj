<?php

/**
 * Accounting / compliance configuration.
 *
 * Centralizes statutory deadlines used by Tax and Payroll liability reports.
 * Override per jurisdiction as needed.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Tax filing grace period
    |--------------------------------------------------------------------------
    | Days after a tax period ends before filing/payment becomes overdue.
    | Sensible defaults by jurisdiction:
    |   Malaysia GST/SST: 30 | UK VAT: 37 | India GST (GSTR-3B): 20 | EU avg: 30
    */
    'tax_filing_grace_days' => env('ACC_TAX_GRACE_DAYS', 30),

    /*
    | Days before the due date when a period should be flagged "due soon".
    */
    'tax_filing_warning_days' => env('ACC_TAX_WARNING_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Payroll statutory remittance deadline
    |--------------------------------------------------------------------------
    | Days after pay-period end by which deductions (PAYE / EPF / SOCSO etc.)
    | must be remitted to the statutory body.
    | Sensible defaults:
    |   Malaysia EPF/SOCSO: 15 | UK PAYE: 22 | Singapore CPF: 14 | India PF: 15
    */
    'payroll_statutory_grace_days' => env('ACC_PAYROLL_GRACE_DAYS', 15),

    /*
    | Days before payroll due date when a liability should be flagged "due soon".
    */
    'payroll_statutory_warning_days' => env('ACC_PAYROLL_WARNING_DAYS', 5),

    /*
    |--------------------------------------------------------------------------
    | Payroll Anomaly Detection Thresholds
    |--------------------------------------------------------------------------
    | Used by PayrollAnomalyDetector to flag suspicious payslip values before
    | a pay run is approved / posted. All values are configurable via .env.
    */
    'payroll_anomaly' => [
        // Flag if net pay deviates more than X% from the lookback-period average
        'net_pay_deviation_pct'   => (int) env('PAYROLL_NET_PAY_DEVIATION', 15),

        // Flag if gross pay deviates more than X% from the lookback-period average
        'gross_pay_deviation_pct' => (int) env('PAYROLL_GROSS_PAY_DEVIATION', 20),

        // Flag if total deductions exceed X% of gross earnings
        'deduction_cap_pct'       => (int) env('PAYROLL_DEDUCTION_CAP', 50),

        // Flag if overtime amount is X% above the lookback-period average
        'overtime_spike_pct'      => (int) env('PAYROLL_OT_SPIKE', 100),

        // Number of prior months to use as the comparison baseline
        'lookback_months'         => (int) env('PAYROLL_LOOKBACK_MONTHS', 3),

        // Minimum prior payslips needed before comparing current pay against history
        'minimum_history_count'    => (int) env('PAYROLL_MIN_HISTORY_COUNT', 2),

        // Do not flag "new overtime" unless the amount is meaningful
        'overtime_min_amount'      => (float) env('PAYROLL_OT_MIN_AMOUNT', 100),

        // Allowed difference when checking year-to-date continuity
        'ytd_tolerance'            => (float) env('PAYROLL_YTD_TOLERANCE', 1.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Statutory Compliance Settings
    |--------------------------------------------------------------------------
    | Configuration for automated compliance monitoring and alerts.
    */
    'compliance' => [
        // Enable/disable automatic compliance event generation
        'auto_generate_events' => env('COMPLIANCE_AUTO_GENERATE', true),

        // Enable/disable automatic regulatory change checking
        'auto_check_changes' => env('COMPLIANCE_AUTO_CHECK_CHANGES', true),

        // Default alert expiration days
        'alert_expiration_days' => env('COMPLIANCE_ALERT_EXPIRATION_DAYS', 30),

        // Penalty calculation settings
        'penalty_calculation' => [
            'enable_auto_calculation' => env('COMPLIANCE_PENALTY_AUTO_CALC', true),
            'include_compound_interest' => env('COMPLIANCE_PENALTY_COMPOUND', false),
        ],

        // Notification settings
        'notifications' => [
            'email_enabled' => env('COMPLIANCE_EMAIL_NOTIFICATIONS', true),
            'sms_enabled' => env('COMPLIANCE_SMS_NOTIFICATIONS', false),
            'slack_enabled' => env('COMPLIANCE_SLACK_NOTIFICATIONS', false),
        ],
    ],

];
