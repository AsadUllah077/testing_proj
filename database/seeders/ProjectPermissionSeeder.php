<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class ProjectPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ========== PROJECT MODULE ==========
            ['name' => 'project.dashboard.view'],

            // ========== PROJECTS ==========
            ['name' => 'project.projects.view'],
            ['name' => 'project.projects.show'],
            ['name' => 'project.projects.create'],
            ['name' => 'project.projects.edit'],
            ['name' => 'project.projects.delete'],
            ['name' => 'project.projects.export-pdf'],
            ['name' => 'project.projects.export-excel'],

            // ========== PROJECT TASKS ==========
            ['name' => 'project.projects.tasks.view'],
            ['name' => 'project.projects.tasks.create'],
            ['name' => 'project.projects.tasks.edit'],
            ['name' => 'project.projects.tasks.delete'],

            // ========== CLIENTS ==========
            ['name' => 'project.clients.view'],
            ['name' => 'project.clients.show'],
            ['name' => 'project.clients.create'],
            ['name' => 'project.clients.edit'],
            ['name' => 'project.clients.delete'],
            ['name' => 'project.clients.export-pdf'],
            ['name' => 'project.clients.export-excel'],

            // ========== TEAMS ==========
            ['name' => 'project.teams.view'],
            ['name' => 'project.teams.create'],
            ['name' => 'project.teams.edit'],
            ['name' => 'project.teams.delete'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}
