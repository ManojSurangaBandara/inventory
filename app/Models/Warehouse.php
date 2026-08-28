<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'type', // main, sub, unit
        'location',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        $type = $this->attributes['type'] ?? 'main';
        return match ($type) {
            'main' => 'Main Warehouse (Central)',
            'sub' => 'Sub Warehouse (Regional)',
            'unit' => 'Unit Warehouse (Workshop/Field)',
            default => ucfirst($type ?? 'main') . ' Warehouse',
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        $type = $this->attributes['type'] ?? 'main';
        return match ($type) {
            'main' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
            'sub' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
            'unit' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
            default => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
        };
    }
}
