<?php

namespace Tests\Feature;

use App\Http\Controllers\Logistics\ManageBookingV2Controller;
use App\Models\LogisticsFreightAgent;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogisticsFreightAgentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // This project includes route files with require_once, so register these
        // focused routes when Laravel refreshes the test application in-process.
        if (! Route::has('logistics.manage-booking.v2.freight-agents.search')) {
            Route::middleware('auth')
                ->prefix('logistics/manage-booking/v2')
                ->name('logistics.manage-booking.v2.')
                ->controller(ManageBookingV2Controller::class)
                ->group(function (): void {
                    Route::get('/freight-agents', 'searchFreightAgents')->name('freight-agents.search');
                    Route::post('/freight-agents', 'storeFreightAgent')->name('freight-agents.store');
                });
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }

        Schema::dropIfExists('logistics_freight_agents');
        Schema::create('logistics_freight_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('name_search');
            $table->string('email')->nullable();
            $table->string('contact', 50)->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name_search']);
        });

        $user = new User;
        $user->forceFill([
            'id' => 100,
            'company_id' => 1,
            'first_name' => 'Logistics',
            'last_name' => 'User',
            'email' => 'logistics@example.test',
        ]);
        $user->exists = true;

        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logistics_freight_agents');

        parent::tearDown();
    }

    public function test_agent_can_be_created_without_optional_contact_details_and_duplicates_are_reused(): void
    {
        $this->postJson(route('logistics.manage-booking.v2.freight-agents.store'), [
            'name' => 'Kühne + Nagel',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('created', true)
            ->assertJsonPath('agent.email', null)
            ->assertJsonPath('agent.contact', null);

        $this->assertDatabaseHas('logistics_freight_agents', [
            'company_id' => 1,
            'name' => 'Kühne + Nagel',
            'name_search' => 'kuhne nagel',
        ]);

        $this->postJson(route('logistics.manage-booking.v2.freight-agents.store'), [
            'name' => 'Kuhne---Nagel',
            'email' => 'different@example.test',
        ])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('agent.name', 'Kühne + Nagel');

        $this->assertSame(1, DB::table('logistics_freight_agents')->count());
    }

    public function test_search_is_company_scoped_and_uses_cursor_pagination(): void
    {
        $now = now();
        $agents = [];

        for ($index = 1; $index <= 55; $index++) {
            $name = sprintf('Acme Agent %02d', $index);
            $agents[] = [
                'company_id' => 1,
                'name' => $name,
                'name_search' => LogisticsFreightAgent::normalizeName($name),
                'email' => null,
                'contact' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $agents[] = [
            'company_id' => 2,
            'name' => 'Acme Agent from another company',
            'name_search' => 'acme agent from another company',
            'email' => null,
            'contact' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        DB::table('logistics_freight_agents')->insert($agents);

        $this->getJson(route('logistics.manage-booking.v2.freight-agents.search'))
            ->assertOk()
            ->assertJsonCount(50, 'results')
            ->assertJsonPath('pagination.more', true);

        $firstPage = $this->getJson(route('logistics.manage-booking.v2.freight-agents.search', [
            'q' => 'acme',
        ]))
            ->assertOk()
            ->assertJsonCount(50, 'results')
            ->assertJsonPath('pagination.more', true)
            ->assertJsonMissing(['text' => 'Acme Agent from another company']);

        $cursor = $firstPage->json('next_cursor');
        $this->assertNotEmpty($cursor);

        $this->getJson(route('logistics.manage-booking.v2.freight-agents.search', [
            'q' => 'acme',
            'cursor' => $cursor,
        ]))
            ->assertOk()
            ->assertJsonCount(5, 'results')
            ->assertJsonPath('pagination.more', false)
            ->assertJsonMissing(['text' => 'Acme Agent from another company']);
    }

    public function test_agent_email_is_optional_but_must_be_valid_when_supplied(): void
    {
        $this->postJson(route('logistics.manage-booking.v2.freight-agents.store'), [
            'name' => 'Invalid Email Agent',
            'email' => 'not-an-email',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
