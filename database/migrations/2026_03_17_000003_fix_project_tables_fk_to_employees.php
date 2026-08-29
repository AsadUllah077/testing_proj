<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix projects.manager_id → employees
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign('projects_manager_id_foreign');
            $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
        });

        // 2. Fix project_members: drop FK + unique, rename user_id → employee_id, re-add FK + unique
        Schema::table('project_members', function (Blueprint $table) {
            $table->dropForeign('project_members_employee_id_foreign');
            $table->dropUnique('project_members_project_id_employee_id_unique');
            $table->renameColumn('employee_id', 'user_id');
        });
        Schema::table('project_members', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('employees')->onDelete('cascade');
            $table->unique(['project_id', 'user_id']);
        });

        // 3. Fix project_tasks.assignee_id → employees
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropForeign('project_tasks_assignee_id_foreign');
            $table->foreign('assignee_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('project_members', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique(['project_id', 'employee_id']);
            $table->renameColumn('employee_id', 'user_id');
        });
        Schema::table('project_members', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['project_id', 'user_id']);
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropForeign(['assignee_id']);
            $table->foreign('assignee_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
