<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'status' => 1],
            ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€', 'status' => 1],
            ['name' => 'British Pound Sterling', 'code' => 'GBP', 'symbol' => '£', 'status' => 1],
            ['name' => 'Pakistani Rupee', 'code' => 'PKR', 'symbol' => '₨', 'status' => 1],
            ['name' => 'Japanese Yen', 'code' => 'JPY', 'symbol' => '¥', 'status' => 1],
            ['name' => 'Australian Dollar', 'code' => 'AUD', 'symbol' => 'A$', 'status' => 1],
            ['name' => 'Canadian Dollar', 'code' => 'CAD', 'symbol' => 'C$', 'status' => 1],
            ['name' => 'Swiss Franc', 'code' => 'CHF', 'symbol' => 'CHF', 'status' => 1],
            ['name' => 'Chinese Yuan Renminbi', 'code' => 'CNY', 'symbol' => '¥', 'status' => 1],
            ['name' => 'Indian Rupee', 'code' => 'INR', 'symbol' => '₹', 'status' => 1],
            ['name' => 'Bangladeshi Taka', 'code' => 'BDT', 'symbol' => '৳', 'status' => 1],
            ['name' => 'United Arab Emirates Dirham', 'code' => 'AED', 'symbol' => 'د.إ', 'status' => 1],
            ['name' => 'Saudi Riyal', 'code' => 'SAR', 'symbol' => '﷼', 'status' => 1],
            ['name' => 'Kuwaiti Dinar', 'code' => 'KWD', 'symbol' => 'د.ك', 'status' => 1],
            ['name' => 'Qatari Riyal', 'code' => 'QAR', 'symbol' => '﷼', 'status' => 1],
            ['name' => 'Omani Rial', 'code' => 'OMR', 'symbol' => '﷼', 'status' => 1],
            ['name' => 'Bahraini Dinar', 'code' => 'BHD', 'symbol' => '.د.ب', 'status' => 1],
            ['name' => 'Egyptian Pound', 'code' => 'EGP', 'symbol' => '£', 'status' => 1],
            ['name' => 'Turkish Lira', 'code' => 'TRY', 'symbol' => '₺', 'status' => 1],
            ['name' => 'Indonesian Rupiah', 'code' => 'IDR', 'symbol' => 'Rp', 'status' => 1],
            ['name' => 'Malaysian Ringgit', 'code' => 'MYR', 'symbol' => 'RM', 'status' => 1],
            ['name' => 'Singapore Dollar', 'code' => 'SGD', 'symbol' => 'S$', 'status' => 1],
            ['name' => 'Hong Kong Dollar', 'code' => 'HKD', 'symbol' => 'HK$', 'status' => 1],
            ['name' => 'New Zealand Dollar', 'code' => 'NZD', 'symbol' => 'NZ$', 'status' => 1],
            ['name' => 'South African Rand', 'code' => 'ZAR', 'symbol' => 'R', 'status' => 1],
            ['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦', 'status' => 1],
            ['name' => 'Sri Lankan Rupee', 'code' => 'LKR', 'symbol' => 'Rs', 'status' => 1],
            ['name' => 'Philippine Peso', 'code' => 'PHP', 'symbol' => '₱', 'status' => 1],
            ['name' => 'Vietnamese Dong', 'code' => 'VND', 'symbol' => '₫', 'status' => 1],
            ['name' => 'South Korean Won', 'code' => 'KRW', 'symbol' => '₩', 'status' => 1],
            ['name' => 'Brazilian Real', 'code' => 'BRL', 'symbol' => 'R$', 'status' => 1],
            ['name' => 'Russian Ruble', 'code' => 'RUB', 'symbol' => '₽', 'status' => 1],
            ['name' => 'Mexican Peso', 'code' => 'MXN', 'symbol' => '$', 'status' => 1],
            ['name' => 'Argentine Peso', 'code' => 'ARS', 'symbol' => '$', 'status' => 1],
            ['name' => 'Chilean Peso', 'code' => 'CLP', 'symbol' => '$', 'status' => 1],
            ['name' => 'Colombian Peso', 'code' => 'COP', 'symbol' => '$', 'status' => 1],
            ['name' => 'Peruvian Sol', 'code' => 'PEN', 'symbol' => 'S/', 'status' => 1],
            ['name' => 'Israeli Shekel', 'code' => 'ILS', 'symbol' => '₪', 'status' => 1],
            ['name' => 'Kenyan Shilling', 'code' => 'KES', 'symbol' => 'KSh', 'status' => 1],
            ['name' => 'Ugandan Shilling', 'code' => 'UGX', 'symbol' => 'USh', 'status' => 1],
            ['name' => 'Tanzanian Shilling', 'code' => 'TZS', 'symbol' => 'TSh', 'status' => 1],
            ['name' => 'Ethiopian Birr', 'code' => 'ETB', 'symbol' => 'Br', 'status' => 1],
            ['name' => 'Ghanaian Cedi', 'code' => 'GHS', 'symbol' => '₵', 'status' => 1],
            ['name' => 'Moroccan Dirham', 'code' => 'MAD', 'symbol' => 'د.م.', 'status' => 1],
            ['name' => 'Jordanian Dinar', 'code' => 'JOD', 'symbol' => 'د.ا', 'status' => 1],
            ['name' => 'Lebanese Pound', 'code' => 'LBP', 'symbol' => 'ل.ل', 'status' => 1],
            ['name' => 'Iranian Rial', 'code' => 'IRR', 'symbol' => '﷼', 'status' => 1],
            ['name' => 'Iraqi Dinar', 'code' => 'IQD', 'symbol' => 'ع.د', 'status' => 1],
            ['name' => 'Afghan Afghani', 'code' => 'AFN', 'symbol' => '؋', 'status' => 1],
            ['name' => 'Nepalese Rupee', 'code' => 'NPR', 'symbol' => '₨', 'status' => 1],
            ['name' => 'Myanmar Kyat', 'code' => 'MMK', 'symbol' => 'Ks', 'status' => 1],
            ['name' => 'Cambodian Riel', 'code' => 'KHR', 'symbol' => '៛', 'status' => 1],
            ['name' => 'Thai Baht', 'code' => 'THB', 'symbol' => '฿', 'status' => 1],
            ['name' => 'Taiwan Dollar', 'code' => 'TWD', 'symbol' => 'NT$', 'status' => 1],
            ['name' => 'Kazakhstani Tenge', 'code' => 'KZT', 'symbol' => '₸', 'status' => 1],
            ['name' => 'Uzbekistani Som', 'code' => 'UZS', 'symbol' => 'soʻm', 'status' => 1],
            ['name' => 'Polish Zloty', 'code' => 'PLN', 'symbol' => 'zł', 'status' => 1],
            ['name' => 'Norwegian Krone', 'code' => 'NOK', 'symbol' => 'kr', 'status' => 1],
            ['name' => 'Swedish Krona', 'code' => 'SEK', 'symbol' => 'kr', 'status' => 1],
            ['name' => 'Danish Krone', 'code' => 'DKK', 'symbol' => 'kr', 'status' => 1],
            ['name' => 'Czech Koruna', 'code' => 'CZK', 'symbol' => 'Kč', 'status' => 1],
            ['name' => 'Hungarian Forint', 'code' => 'HUF', 'symbol' => 'Ft', 'status' => 1],
            ['name' => 'Romanian Leu', 'code' => 'RON', 'symbol' => 'lei', 'status' => 1],
            ['name' => 'Bulgarian Lev', 'code' => 'BGN', 'symbol' => 'лв', 'status' => 1],
            ['name' => 'Ukrainian Hryvnia', 'code' => 'UAH', 'symbol' => '₴', 'status' => 1],
            ['name' => 'Croatian Kuna', 'code' => 'HRK', 'symbol' => 'kn', 'status' => 1],
            ['name' => 'Serbian Dinar', 'code' => 'RSD', 'symbol' => 'дин.', 'status' => 1],
            ['name' => 'Georgian Lari', 'code' => 'GEL', 'symbol' => '₾', 'status' => 1],
            ['name' => 'Armenian Dram', 'code' => 'AMD', 'symbol' => '֏', 'status' => 1],
            ['name' => 'Azerbaijani Manat', 'code' => 'AZN', 'symbol' => '₼', 'status' => 1],
            ['name' => 'Kazakh Tenge', 'code' => 'KZT', 'symbol' => '₸', 'status' => 1],
            ['name' => 'Algerian Dinar', 'code' => 'DZD', 'symbol' => 'د.ج', 'status' => 1],
            ['name' => 'Tunisian Dinar', 'code' => 'TND', 'symbol' => 'د.ت', 'status' => 1],
            ['name' => 'Ethiopian Birr', 'code' => 'ETB', 'symbol' => 'Br', 'status' => 1],
            ['name' => 'Zambian Kwacha', 'code' => 'ZMW', 'symbol' => 'ZK', 'status' => 1],
            ['name' => 'Malawian Kwacha', 'code' => 'MWK', 'symbol' => 'MK', 'status' => 1],
            ['name' => 'Botswana Pula', 'code' => 'BWP', 'symbol' => 'P', 'status' => 1],
            ['name' => 'Mauritian Rupee', 'code' => 'MUR', 'symbol' => '₨', 'status' => 1],
            ['name' => 'Seychellois Rupee', 'code' => 'SCR', 'symbol' => '₨', 'status' => 1],
            ['name' => 'Gambian Dalasi', 'code' => 'GMD', 'symbol' => 'D', 'status' => 1],
            ['name' => 'West African CFA Franc', 'code' => 'XOF', 'symbol' => 'CFA', 'status' => 1],
            ['name' => 'Central African CFA Franc', 'code' => 'XAF', 'symbol' => 'FCFA', 'status' => 1],
            ['name' => 'Somali Shilling', 'code' => 'SOS', 'symbol' => 'Sh', 'status' => 1],
        ];

        $now = now();

        // Prepare for insert with status + timestamps
        $insertData = array_map(function ($currency) use ($now) {
            return array_merge($currency, [
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $currencies);

        // Insert or update duplicates using currency code
        foreach ($insertData as $data) {
            Currency::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command->info('🚀 Currencies inserted/updated successfully!');
    }
}
