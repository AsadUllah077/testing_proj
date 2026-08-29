<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany()
    {
        // used to retrive data based on this
        // Global scope
        // static::addGlobalScope('company', function (Builder $builder) {
        //     if (loggedCompanyId()) {
        //         $builder->where('company_id', loggedCompanyId()['company_id']);
        //     }
        // });

        static::addGlobalScope('company', function ($builder) {
            $user = auth('web')->user();
            if ($user && !empty($user->company_id)) {
                $builder->where($builder->getQuery()->from . '.company_id', $user->company_id);
            }
        });


        // used to create data to insert this id
        // Assign company_id automatically if not already set
        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $user = auth('web')->user();
                if ($user && !empty($user->company_id)) {
                    $model->company_id = $user->company_id;
                }
            }
        });
    }
}
