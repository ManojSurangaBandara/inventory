<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class OrganizationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Super Admin can bypass organization scope if explicitly un-scoped, 
            // but for normal tenant operation, scope to user's organization.
            if (!$user->is_super_admin && $user->organization_id) {
                $builder->where($model->getTable() . '.organization_id', '=', $user->organization_id);
            } elseif (session()->has('active_tenant_id') && session('active_tenant_id')) {
                // If superadmin has selected a tenant scope in session
                $builder->where($model->getTable() . '.organization_id', '=', session('active_tenant_id'));
            }
        }
    }
}
