<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statutory_bodies', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->dropUnique(['code']);
        });

        $companyIds = DB::table('companies')->orderBy('id')->pluck('id');
        $bodies = DB::table('statutory_bodies')->whereNull('company_id')->orderBy('id')->get();

        foreach ($bodies as $body) {
            $attributes = (array) $body;
            unset($attributes['id']);

            foreach ($companyIds as $companyId) {
                $attributes['company_id'] = $companyId;
                $newBodyId = DB::table('statutory_bodies')->insertGetId($attributes);

                DB::table('pay_items')
                    ->where('company_id', $companyId)
                    ->where('statutory_body_id', $body->id)
                    ->update(['statutory_body_id' => $newBodyId]);

                DB::table('compliance_events')
                    ->where('company_id', $companyId)
                    ->where('statutory_body_id', $body->id)
                    ->update(['statutory_body_id' => $newBodyId]);
            }
        }

        DB::table('statutory_bodies')->whereNull('company_id')->delete();

        Schema::table('statutory_bodies', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        $bodiesByCode = DB::table('statutory_bodies')->orderBy('id')->get()->groupBy('code');

        foreach ($bodiesByCode as $bodies) {
            $canonical = $bodies->first();

            foreach ($bodies->skip(1) as $duplicate) {
                DB::table('pay_items')
                    ->where('statutory_body_id', $duplicate->id)
                    ->update(['statutory_body_id' => $canonical->id]);

                DB::table('compliance_events')
                    ->where('statutory_body_id', $duplicate->id)
                    ->update(['statutory_body_id' => $canonical->id]);

                DB::table('statutory_bodies')->where('id', $duplicate->id)->delete();
            }
        }

        Schema::table('statutory_bodies', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropConstrainedForeignId('company_id');
            $table->unique('code');
        });
    }
};
