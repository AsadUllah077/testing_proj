<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mass_mailer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('mass_mailer_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->unique();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('position')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zipcode')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('mass_mailer_contact_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('mass_mailer_contacts')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('mass_mailer_tags')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('mass_mailer_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            $table->timestamps();
        });

        Schema::create('mass_mailer_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('mass_mailer_templates')->onDelete('cascade');
            $table->string('subject');
            $table->longText('body');
            $table->string('status')->default('pending'); // pending, sending, completed, failed
            $table->integer('total_contacts')->default(0);
            $table->integer('sent_contacts')->default(0);
            $table->integer('failed_contacts')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_mailer_campaigns');
        Schema::dropIfExists('mass_mailer_templates');
        Schema::dropIfExists('mass_mailer_contact_tag');
        Schema::dropIfExists('mass_mailer_contacts');
        Schema::dropIfExists('mass_mailer_tags');
    }
};
