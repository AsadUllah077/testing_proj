<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;

trait HasCompanySequence
{
    protected static function bootHasCompanySequence()
    {
        static::creating(function ($model) {

            DB::transaction(function () use ($model) {

                $companyId = $model->company_id;
                $key       = $model->getSequenceKey();
                $prefix    = $model->getSequencePrefix();
                $column    = $model->getSequenceColumn();
                $padding   = $model->getSequencePadding();

                $sequence = DB::table('company_sequences')
                    ->where('company_id', $companyId)
                    ->where('key', $key)
                    ->lockForUpdate() // PostgreSQL-safe
                    ->first();

                if (!$sequence) {
                    DB::table('company_sequences')->insert([
                        'company_id'     => $companyId,
                        'key'            => $key,
                        'current_number' => 1,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);

                    $next = 1;
                } else {
                    $next = $sequence->current_number + 1;

                    DB::table('company_sequences')
                        ->where('id', $sequence->id)
                        ->update([
                            'current_number' => $next,
                            'updated_at'     => now(),
                        ]);
                }

                $model->{$column} =
                    $prefix . str_pad($next, $padding, '0', STR_PAD_LEFT);
            });
        });
    }

    // ========== use in other Model ========== //

    /* use App\Models\Traits\HasCompanySequence;

    use HasCompanySequence; /*

    // ========== mandatory ========== //
    /* public function getSequenceKey(): string
    {
        return 'sale';
    }

    public function getSequencePrefix(): string
    {
        return 'SO-';
    }

    public function getSequenceColumn(): string
    {
        return 'unique_no';
    } */

    // ========== optional ========== //
    /* public function getSequencePadding(): int
    {
        return 5;
    } */
}
