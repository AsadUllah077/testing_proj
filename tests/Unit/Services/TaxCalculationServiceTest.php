<?php

namespace Tests\Unit\Services;

use App\Services\TaxCalculationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class TaxCalculationServiceTest extends TestCase
{
    private int $companyId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('accounting_periods');
        Schema::create('accounting_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });

        $this->companyId = 1;
    }

    public function test_it_derives_a_july_to_june_fiscal_year_from_period_dates(): void
    {
        $this->createPeriod('Legacy name without years', '2025-07-01', '2026-06-30');

        $this->assertSame('2025-2026', $this->service()->fiscalYearForDate($this->companyId, '2025-12-15'));
    }

    public function test_it_supports_a_fiscal_year_starting_outside_july(): void
    {
        $this->createPeriod('April period', '2025-04-01', '2026-03-31');

        $this->assertSame('2025-2026', $this->service()->fiscalYearForDate($this->companyId, '2026-01-15'));
    }

    public function test_it_supports_future_fiscal_years(): void
    {
        $this->createPeriod('Future period', '2030-07-01', '2031-06-30');

        $this->assertSame('2030-2031', $this->service()->fiscalYearForDate($this->companyId, '2031-02-01'));
    }

    public function test_it_supports_a_period_crossing_into_2100(): void
    {
        $this->createPeriod('Cross-century period', '2099-07-01', '2100-06-30');

        $this->assertSame('2099-2100', $this->service()->fiscalYearForDate($this->companyId, '2100-01-01'));
    }

    public function test_start_date_is_included(): void
    {
        $this->createPeriod('Boundary period', '2150-01-01', '2151-12-31');

        $this->assertSame('2150-2151', $this->service()->fiscalYearForDate($this->companyId, '2150-01-01'));
    }

    public function test_end_date_is_included(): void
    {
        $this->createPeriod('Boundary period', '2150-01-01', '2151-12-31');

        $this->assertSame('2150-2151', $this->service()->fiscalYearForDate($this->companyId, '2151-12-31'));
    }

    public function test_latest_start_date_wins_when_periods_overlap(): void
    {
        $this->createPeriod('Earlier period', '2025-01-01', '2026-12-31');
        $this->createPeriod('Later period', '2025-04-01', '2027-03-31');

        $this->assertSame('2025-2027', $this->service()->fiscalYearForDate($this->companyId, '2025-08-01'));
    }

    public function test_resolved_fiscal_year_is_cached_by_company_and_payroll_date(): void
    {
        $periodId = $this->createPeriod('Original period', '2025-04-01', '2026-03-31');
        $service = $this->service();

        $this->assertSame('2025-2026', $service->fiscalYearForDate($this->companyId, '2025-08-01'));

        DB::table('accounting_periods')->where('id', $periodId)->update([
            'start_date' => '2024-04-01',
            'end_date' => '2025-03-31',
        ]);

        $this->assertSame('2025-2026', $service->fiscalYearForDate($this->companyId, '2025-08-01'));
    }

    public function test_it_throws_when_no_accounting_period_matches(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "No accounting period found for company ID {$this->companyId} and payroll date 2025-08-01."
        );

        $this->service()->fiscalYearForDate($this->companyId, '2025-08-01');
    }

    private function service(): TaxCalculationService
    {
        return new TaxCalculationService;
    }

    private function createPeriod(string $name, string $startDate, string $endDate): int
    {
        return DB::table('accounting_periods')->insertGetId([
            'company_id' => $this->companyId,
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true,
            'is_locked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
