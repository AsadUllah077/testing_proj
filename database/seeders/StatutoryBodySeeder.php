<?php

namespace Database\Seeders;

use App\Models\StatutoryBody;
use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatutoryBodySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statutoryBodies = [
            [
                'code' => 'EPF',
                'name' => 'Employees Provident Fund',
                'description' => 'Mandatory retirement savings scheme for employees in Malaysia',
                'country_code' => 'MYS',
                'website' => 'https://www.kwsp.gov.my',
                'contact_info' => [
                    'phone' => '+603-8922-6000',
                    'email' => 'kwsp@kwsp.gov.my',
                    'address' => 'Level 36, Tower 1, PETRONAS Twin Towers, Kuala Lumpur City Centre, 50088 Kuala Lumpur'
                ],
                'default_grace_days' => 15,
                'default_warning_days' => 5,
                'penalty_rate_per_day' => 0.05, // 5% per day
                'max_penalty_percentage' => 50, // Max 50% of amount
            ],
            [
                'code' => 'SOCSO',
                'name' => 'Social Security Organisation',
                'description' => 'Social security and insurance scheme for employment injuries and disabilities',
                'country_code' => 'MYS',
                'website' => 'https://www.perkeso.gov.my',
                'contact_info' => [
                    'phone' => '+603-6279-1200',
                    'email' => 'pro@perkeso.gov.my',
                    'address' => 'Menara PERKESO, No. 281, Jalan Ampang, 50450 Kuala Lumpur'
                ],
                'default_grace_days' => 15,
                'default_warning_days' => 5,
                'penalty_rate_per_day' => 0.03,
                'max_penalty_percentage' => 25,
            ],
            [
                'code' => 'LHDN',
                'name' => 'Lembaga Hasil Dalam Negeri',
                'description' => 'Inland Revenue Board of Malaysia - Tax collection authority',
                'country_code' => 'MYS',
                'website' => 'https://www.hasil.gov.my',
                'contact_info' => [
                    'phone' => '+603-7710-2000',
                    'email' => 'enquiry@hasil.gov.my',
                    'address' => 'Level 15-21, Menara Hasil, Persiaran Rimba Permai, Cyber 8, 63000 Cyberjaya'
                ],
                'default_grace_days' => 30,
                'default_warning_days' => 7,
                'penalty_rate_per_day' => 0.02,
                'max_penalty_percentage' => 100,
            ],
            [
                'code' => 'GST',
                'name' => 'Goods and Services Tax',
                'description' => 'Goods and Services Tax collection under LHDN',
                'country_code' => 'MYS',
                'website' => 'https://www.hasil.gov.my',
                'contact_info' => [
                    'phone' => '+603-7710-2000',
                    'email' => 'gst@hasil.gov.my',
                    'address' => 'Level 15-21, Menara Hasil, Persiaran Rimba Permai, Cyber 8, 63000 Cyberjaya'
                ],
                'default_grace_days' => 20,
                'default_warning_days' => 7,
                'penalty_rate_per_day' => 0.01,
                'max_penalty_percentage' => 200,
            ],
            [
                'code' => 'FBR',
                'name' => 'Federal Board of Revenue',
                'description' => 'The premier federal agency of Pakistan that is responsible for collecting taxation and managing the administration of taxation.',
                'country_code' => 'PAK',
                'website' => 'https://fbr.gov.pk',
                'contact_info' => [
                    'phone' => '051-111-772-772',
                    'email' => 'helpline@fbr.gov.pk',
                    'address' => 'Constitution Ave, G-5/2 G-5, Islamabad, Islamabad Capital Territory 44000'
                ],
                'default_grace_days' => 15,
                'default_warning_days' => 5,
                'penalty_rate_per_day' => 0.02,
                'max_penalty_percentage' => 100,
            ],
            [
                'code' => 'EOBI',
                'name' => 'Employees Old-Age Benefits Institution',
                'description' => 'The statutory body for providing social security and pension to employees of industrial and commercial establishments.',
                'country_code' => 'PAK',
                'website' => 'https://www.eobi.gov.pk',
                'contact_info' => [
                    'phone' => '021-99243431',
                    'email' => 'info@eobi.gov.pk',
                    'address' => 'EOBI House, Plot No. 190/1/B, Block-2, PECHS, Nursery, Karachi.'
                ],
                'default_grace_days' => 20,
                'default_warning_days' => 7,
                'penalty_rate_per_day' => 0.01,
                'max_penalty_percentage' => 50,
            ],
            [
                'code' => 'PESSI',
                'name' => 'Punjab Employees Social Security Institution',
                'description' => 'Provides medical facilities and cash benefits to the secured workers and their dependents.',
                'country_code' => 'PAK',
                'website' => 'https://pessi.punjab.gov.pk',
                'contact_info' => [
                    'phone' => '042-99263124',
                    'email' => 'dg.pessi@punjab.gov.pk',
                    'address' => '3-A, Gulberg V, Jail Road, Lahore.'
                ],
                'default_grace_days' => 15,
                'default_warning_days' => 5,
                'penalty_rate_per_day' => 0.015,
                'max_penalty_percentage' => 40,
            ],
        ];

        foreach (Company::query()->pluck('id') as $companyId) {
            foreach ($statutoryBodies as $body) {
                StatutoryBody::withoutGlobalScopes()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'code' => $body['code'],
                    ],
                    $body
                );
            }
        }
    }
}
