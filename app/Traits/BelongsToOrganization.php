<?php

namespace App\Traits;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToOrganization
{
    /**
     * Boot the trait to attach global scope and creating event.
     */
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope());

        static::creating(function ($model) {
            if (!$model->organization_id) {
                if (Auth::check() && Auth::user()->organization_id) {
                    $model->organization_id = Auth::user()->organization_id;
                } elseif (session()->has('active_tenant_id') && session('active_tenant_id')) {
                    $model->organization_id = session('active_tenant_id');
                }
            }
        });
    }

    /**
     * Relationship to Organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
