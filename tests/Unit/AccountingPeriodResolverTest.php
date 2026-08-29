<?php

namespace Tests\Unit;

use App\Services\Account\AccountingPeriodResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountingPeriodResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('accounting_periods');
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });
        DB::table('accounting_periods')->insert([
            ['company_id' => 4, 'name' => 'FY2025-2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => 1, 'is_locked' => 0],
            ['company_id' => 4, 'name' => 'FY2026-2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => 1, 'is_locked' => 0],
            ['company_id' => 5, 'name' => 'Calendar 2024', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'is_active' => 1, 'is_locked' => 0],
        ]);
    }

    public function test_fiscal_boundary_is_resolved_exclusively_from_configured_ranges(): void
    {
        $resolver = app(AccountingPeriodResolver::class);

        $this->assertSame('FY2025-2026', $resolver->resolve(4, '2026-06-30')->name);
        $this->assertSame('FY2026-2027', $resolver->resolve(4, '2026-07-01')->name);
        $this->assertSame('Calendar 2024', $resolver->resolve(5, '2024-02-29', false)->name);
    }

    public function test_closed_period_is_rejected_for_posting(): void
    {
        DB::table('accounting_periods')->where('name', 'FY2026-2027')->update(['is_locked' => 1]);
        $this->expectException(ValidationException::class);

        app(AccountingPeriodResolver::class)->resolve(4, '2026-07-01');
    }
}
