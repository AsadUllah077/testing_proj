<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class TicketPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ========== TICKET MODULE ==========
            ['name' => 'ticket.dashboard.view'],

            // ========== TICKETS ==========
            ['name' => 'ticket.tickets.view'],
            ['name' => 'ticket.tickets.show'],
            ['name' => 'ticket.tickets.create'],
            ['name' => 'ticket.tickets.edit'],
            ['name' => 'ticket.tickets.delete'],
            ['name' => 'ticket.tickets.bulk-assign'],
            ['name' => 'ticket.tickets.assigned'],

            // ========== TICKET COMMENTS ==========
            ['name' => 'ticket.tickets.comments.create'],
            ['name' => 'ticket.tickets.comments.delete'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}
