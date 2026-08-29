<?php

namespace Tests\Feature;

use App\Http\Controllers\Logistics\GDController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogisticsGoodsDeclarationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('logistics.gd.store')) {
            Route::middleware('auth')
                ->prefix('logistics/gd')
                ->name('logistics.gd.')
                ->controller(GDController::class)
                ->group(function (): void {
                    Route::post('/', 'store')->name('store');
                });
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }

        Schema::dropIfExists('logistics_goods_declarations');
        Schema::create('logistics_goods_declarations', function (Blueprint $table) {
            $table->id();
            $table->string('gd_number')->unique();
            $table->unsignedBigInteger('booking_v2_id')->nullable();
            $table->string('declaration_type')->nullable();
            $table->string('collectorate')->nullable();
            $table->string('bl_awb_number')->nullable();
            $table->string('importer')->nullable();
            $table->string('ntn_cnic')->nullable();
            $table->string('exporter')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->string('hs_code')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('gross_weight', 12, 2)->nullable();
            $table->decimal('net_weight', 12, 2)->nullable();
            $table->decimal('customs_value', 15, 2)->nullable();
            $table->string('delivery_term')->nullable();
            $table->decimal('delivery_term_value', 15, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('customs_duty', 15, 2)->nullable();
            $table->decimal('sales_tax', 15, 2)->nullable();
            $table->decimal('income_tax', 15, 2)->nullable();
            $table->decimal('additional_duty', 15, 2)->nullable();
            $table->decimal('total_duty', 15, 2)->nullable();
            $table->string('duty_payment_mode')->nullable();
            $table->json('duty_payment_details')->nullable();
            $table->decimal('landed_cost', 15, 2)->nullable();
            $table->string('status')->default('Draft');
            $table->string('exam_status')->default('no_exam');
            $table->date('filed_date')->nullable();
            $table->date('approval_date')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $user = new User;
        $user->forceFill([
            'id' => 103,
            'company_id' => 1,
            'first_name' => 'Logistics',
            'last_name' => 'User',
            'email' => 'goods-declaration@example.test',
        ]);
        $user->exists = true;

        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logistics_goods_declarations');

        parent::tearDown();
    }

    public function test_user_entered_gd_is_created_with_server_calculated_duties(): void
    {
        $this->postJson(route('logistics.gd.store'), [
            'gd_number' => 'GD-USER-1001',
            'declaration_type' => 'import',
            'importer' => 'Example Importer',
            'hs_code' => '0101.2100',
            'description' => 'Test cargo',
            'country_of_origin' => 'US',
            'quantity' => 1200,
            'unit' => 'KG',
            'net_weight' => 1100,
            'delivery_term' => 'fob',
            'delivery_term_value' => 100,
            'exchange_rate' => 280,
            'customs_duty_rate' => 5,
            'additional_duty_rate' => 3,
            'sales_tax_rate' => 17,
            'income_tax_rate' => 2,
            'status' => 'Filed',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $gd = DB::table('logistics_goods_declarations')->first();

        $this->assertSame('GD-USER-1001', $gd->gd_number);
        $this->assertSame('Filed', $gd->status);
        $this->assertSame('US', $gd->country_of_origin);
        $this->assertSame(1200.0, (float) $gd->gross_weight);
        $this->assertSame(100.0, (float) $gd->customs_value);
        $this->assertSame(1400.0, (float) $gd->customs_duty);
        $this->assertSame(840.0, (float) $gd->additional_duty);
        $this->assertSame(5140.8, (float) $gd->sales_tax);
        $this->assertSame(560.0, (float) $gd->income_tax);
        $this->assertSame(7940.8, (float) $gd->total_duty);
        $this->assertSame(35940.8, (float) $gd->landed_cost);
    }

    public function test_invalid_nan_input_is_rejected_before_database_insert(): void
    {
        $this->postJson(route('logistics.gd.store'), [
            'gd_number' => 'GD-USER-1002',
            'declaration_type' => 'import',
            'importer' => 'Example Importer',
            'hs_code' => '0101.2100',
            'description' => 'Test cargo',
            'delivery_term' => 'fob',
            'delivery_term_value' => 100,
            'exchange_rate' => 280,
            'customs_duty_rate' => 'NaN',
            'status' => 'Draft',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('customs_duty_rate');

        $this->assertSame(0, DB::table('logistics_goods_declarations')->count());
    }

    public function test_country_configuration_contains_the_complete_iso_list(): void
    {
        $this->assertCount(249, config('countries'));
        $this->assertSame('Pakistan', config('countries.PK'));
        $this->assertSame('United States', config('countries.US'));
    }
}
