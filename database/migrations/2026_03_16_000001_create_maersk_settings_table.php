<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maersk_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('consumer_key')->nullable();
            $table->text('consumer_secret')->nullable();
            $table->string('base_url')->default('https://api.maersk.com');
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maersk_settings');
    }
};
