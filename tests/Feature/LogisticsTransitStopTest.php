<?php

namespace Tests\Feature;

use App\Http\Controllers\Logistics\ManageBookingV2Controller;
use App\Models\LogisticsTransitStop;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogisticsTransitStopTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Route::has('logistics.manage-booking.v2.transit-stops.search')) {
            Route::middleware('auth')
                ->prefix('logistics/manage-booking/v2')
                ->name('logistics.manage-booking.v2.')
                ->controller(ManageBookingV2Controller::class)
                ->group(function (): void {
                    Route::get('/transit-stops', 'searchTransitStops')->name('transit-stops.search');
                    Route::post('/transit-stops', 'storeTransitStop')->name('transit-stops.store');
                });
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }

        Schema::dropIfExists('logistics_transit_stops');
        Schema::create('logistics_transit_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('name_search');
            $table->timestamps();
            $table->unique(['company_id', 'name_search']);
        });

        $user = new User;
        $user->forceFill([
            'id' => 101,
            'company_id' => 1,
            'first_name' => 'Logistics',
            'last_name' => 'User',
            'email' => 'transit-stops@example.test',
        ]);
        $user->exists = true;

        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logistics_transit_stops');

        parent::tearDown();
    }

    public function test_transit_stop_can_be_created_and_normalized_duplicates_are_reused(): void
    {
        $this->postJson(route('logistics.manage-booking.v2.transit-stops.store'), [
            'name' => 'Karachi Dry Port',
        ])
            ->assertCreated()
            ->assertJsonPath('created', true)
            ->assertJsonPath('transit_stop.name', 'Karachi Dry Port');

        $this->postJson(route('logistics.manage-booking.v2.transit-stops.store'), [
            'name' => 'Karachi---Dry Port',
        ])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('transit_stop.name', 'Karachi Dry Port');

        $this->assertSame(1, DB::table('logistics_transit_stops')->count());
    }

    public function test_transit_stop_search_is_tenant_scoped_and_cursor_paginated(): void
    {
        $now = now();
        $stops = [];

        for ($index = 1; $index <= 55; $index++) {
            $name = sprintf('Warehouse Stop %02d', $index);
            $stops[] = [
                'company_id' => 1,
                'name' => $name,
                'name_search' => LogisticsTransitStop::normalizeName($name),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $stops[] = [
            'company_id' => 2,
            'name' => 'Warehouse Stop Other Company',
            'name_search' => 'warehouse stop other company',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        DB::table('logistics_transit_stops')->insert($stops);

        $this->getJson(route('logistics.manage-booking.v2.transit-stops.search'))
            ->assertOk()
            ->assertJsonCount(50, 'results')
            ->assertJsonPath('pagination.more', true);

        $firstPage = $this->getJson(route('logistics.manage-booking.v2.transit-stops.search', [
            'q' => 'warehouse',
        ]))
            ->assertOk()
            ->assertJsonCount(50, 'results')
            ->assertJsonPath('pagination.more', true)
            ->assertJsonMissing(['text' => 'Warehouse Stop Other Company']);

        $this->getJson(route('logistics.manage-booking.v2.transit-stops.search', [
            'q' => 'warehouse',
            'cursor' => $firstPage->json('next_cursor'),
        ]))
            ->assertOk()
            ->assertJsonCount(5, 'results')
            ->assertJsonPath('pagination.more', false)
            ->assertJsonMissing(['text' => 'Warehouse Stop Other Company']);
    }

    public function test_default_stop_alias_is_treated_as_an_exact_match(): void
    {
        $this->getJson(route('logistics.manage-booking.v2.transit-stops.search', [
            'q' => 'peshawar',
        ]))
            ->assertOk()
            ->assertJsonPath('exact_match', true)
            ->assertJsonFragment([
                'id' => 'peshawar',
                'text' => 'Peshawar Dry Port',
            ]);
    }
}
