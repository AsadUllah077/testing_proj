<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class PdfService
{
    public function generateInvoicePdf(Sale $sale)
    {
        try {
            // Load company data (if available)
            $company = Company::first();

            // Get customer data
            $customer = $sale->customer;

            // Prepare data for PDF
            $data = [
                'sale' => $sale,
                'customer' => $customer,
                'company' => $company,
                'items' => $sale->items,
                'date' => $sale->date->format('d-m-Y'),
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount,
                'tax' => $sale->tax,
                'total' => $sale->total,
                'paid_amount' => $sale->paid_amount,
                'remaining_amount' => $sale->remaining_amount,
                'amount_in_words' => $this->convertToWords($sale->total),
            ];

            // Generate PDF
            $pdf = PDF::loadView('pdf.invoice', $data);

            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');

            // Return PDF content
            return $pdf->output();

        } catch (\Exception $e) {
            Log::error('PDF generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function convertToWords($number)
    {
        // Simple number to words converter for AED
        // You might want to use a more robust package for production
        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $decimal     = ' point ';
        $dictionary  = array(
            0                   => 'zero',
            1                   => 'one',
            2                   => 'two',
            3                   => 'three',
            4                   => 'four',
            5                   => 'five',
            6                   => 'six',
            7                   => 'seven',
            8                   => 'eight',
            9                   => 'nine',
            10                  => 'ten',
            11                  => 'eleven',
            12                  => 'twelve',
            13                  => 'thirteen',
            14                  => 'fourteen',
            15                  => 'fifteen',
            16                  => 'sixteen',
            17                  => 'seventeen',
            18                  => 'eighteen',
            19                  => 'nineteen',
            20                  => 'twenty',
            30                  => 'thirty',
            40                  => 'fourty',
            50                  => 'fifty',
            60                  => 'sixty',
            70                  => 'seventy',
            80                  => 'eighty',
            90                  => 'ninety',
            100                 => 'hundred',
            1000                => 'thousand',
            1000000             => 'million',
            1000000000          => 'billion',
            1000000000000       => 'trillion',
            1000000000000000    => 'quadrillion',
            1000000000000000000 => 'quintillion'
        );

        if ($number < 0) {
            return $negative . $this->convertToWords(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->convertToWords($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convertToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convertToWords($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return strtoupper($string) . ' ONLY';
    }
}
