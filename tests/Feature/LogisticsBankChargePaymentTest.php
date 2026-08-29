<?php

namespace Tests\Feature;

use App\Http\Controllers\Logistics\BankChargesController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogisticsBankChargePaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('logistics.lc.bank-charges.store')) {
            Route::middleware('auth')
                ->prefix('logistics/lc/bank-charges')
                ->name('logistics.lc.bank-charges.')
                ->controller(BankChargesController::class)
                ->group(function (): void {
                    Route::post('/', 'store')->name('store');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                });
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }

        $this->createTables();

        $user = new User;
        $user->forceFill([
            'id' => 102,
            'company_id' => 1,
            'first_name' => 'Logistics',
            'last_name' => 'User',
            'email' => 'bank-charges@example.test',
        ]);
        $user->exists = true;

        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logistics_lc_payments');
        Schema::dropIfExists('logistics_bank_charges');
        Schema::dropIfExists('logistics_letters_of_credit');
        Schema::dropIfExists('logistics_bookings');

        parent::tearDown();
    }

    public function test_usd_bank_charge_creates_a_linked_payment_and_calculates_pkr(): void
    {
        $bookingId = DB::table('logistics_bookings')->insertGetId([
            'booking_number' => 'BK-1001',
        ]);
        $instrumentId = DB::table('logistics_letters_of_credit')->insertGetId([
            'lc_number' => 'LC-2026-1001',
            'record_type' => 'lc',
            'beneficiary' => 'Example Supplier',
            'booking_id' => $bookingId,
        ]);

        $this->postJson(route('logistics.lc.bank-charges.store'), [
            'charge_type' => 'swift_telex',
            'bank' => 'hbl',
            'lc_id' => $instrumentId,
            'charge_date' => '2026-07-28',
            'charge_currency' => 'USD',
            'amount_usd' => 4,
            'amount_pkr' => 1,
            'exchange_rate' => 280,
            'payment_mode' => 'third_party',
            'bank_reference' => 'BANK-REF-1001',
        ])->assertOk();

        $charge = DB::table('logistics_bank_charges')->first();

        $this->assertSame(1120.0, (float) $charge->amount_pkr);
        $this->assertSame(4.0, (float) $charge->amount_usd);
        $this->assertSame('third_party', $charge->payment_mode);

        $payment = DB::table('logistics_lc_payments')->first();

        $this->assertSame($charge->id, $payment->bank_charge_id);
        $this->assertSame($bookingId, $payment->booking_id);
        $this->assertSame('USD', $payment->currency);
        $this->assertSame(4.0, (float) $payment->amount);
        $this->assertSame(280.0, (float) $payment->currency_value);
        $this->assertSame('bank_charge', $payment->entry_source);
        $this->assertStringContainsString('Bank Charge', $payment->details);
    }

    public function test_updating_and_deleting_a_charge_keeps_its_payment_in_sync(): void
    {
        $this->postJson(route('logistics.lc.bank-charges.store'), [
            'charge_type' => 'other',
            'bank' => 'ubl',
            'charge_date' => '2026-07-28',
            'charge_currency' => 'PKR',
            'amount_pkr' => 200,
            'exchange_rate' => 280,
            'payment_mode' => 'cash',
        ])->assertOk();

        $chargeId = DB::table('logistics_bank_charges')->value('id');

        $this->putJson(route('logistics.lc.bank-charges.update', $chargeId), [
            'charge_type' => 'bank_guarantee_fee',
            'bank' => 'mcb',
            'charge_date' => '2026-07-29',
            'charge_currency' => 'PKR',
            'amount_pkr' => 560,
            'exchange_rate' => 280,
            'payment_mode' => 'third_party',
        ])->assertOk();

        $this->assertDatabaseHas('logistics_lc_payments', [
            'bank_charge_id' => $chargeId,
            'amount' => 560,
            'currency' => 'PKR',
            'entry_source' => 'bank_charge',
        ]);
        $this->assertSame(1, DB::table('logistics_lc_payments')->count());

        $this->deleteJson(route('logistics.lc.bank-charges.destroy', $chargeId))->assertOk();

        $this->assertSame(0, DB::table('logistics_lc_payments')->count());
        $this->assertNotNull(DB::table('logistics_bank_charges')->where('id', $chargeId)->value('deleted_at'));
    }

    private function createTables(): void
    {
        Schema::dropIfExists('logistics_lc_payments');
        Schema::dropIfExists('logistics_bank_charges');
        Schema::dropIfExists('logistics_letters_of_credit');
        Schema::dropIfExists('logistics_bookings');

        Schema::create('logistics_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->nullable();
        });

        Schema::create('logistics_letters_of_credit', function (Blueprint $table) {
            $table->id();
            $table->string('lc_number');
            $table->string('record_type')->default('lc');
            $table->string('beneficiary')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('logistics_bank_charges', function (Blueprint $table) {
            $table->id();
            $table->string('charge_number')->unique();
            $table->string('charge_type');
            $table->string('bank');
            $table->string('linked_lc')->nullable();
            $table->unsignedBigInteger('lc_id')->nullable();
            $table->date('charge_date');
            $table->decimal('amount_pkr', 15, 2)->nullable();
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->decimal('exchange_rate', 10, 4);
            $table->string('charge_currency', 3)->default('PKR');
            $table->string('payment_mode')->nullable();
            $table->string('bank_reference')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('logistics_lc_payments', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->text('details')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 3)->default('PKR');
            $table->decimal('currency_value', 15, 6)->nullable();
            $table->string('payment_type')->default('product_payment');
            $table->string('entry_source')->default('lc_payment');
            $table->text('remarks')->nullable();
            $table->string('status')->nullable();
            $table->string('receipt_no')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('bank_charge_id')->nullable()->unique();
            $table->timestamps();
        });
    }
}
