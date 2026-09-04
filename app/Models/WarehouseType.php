<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WarehouseType extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'color', // emerald, blue, amber, purple, rose, cyan, indigo
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'warehouse_type_id');
    }

    /**
     * Map color keyword to Tailwind badge classes.
     */
    public function getBadgeClassAttribute(): string
    {
        return match ($this->color ?? 'emerald') {
            'emerald' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
            'blue' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
            'amber' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
            'purple' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
            'rose' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
            'cyan' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
            'indigo' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
            default => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
        };
    }

    /**
     * Ensure baseline default warehouse types exist for an organization.
     */
    public static function ensureDefaults(int $organizationId): Collection
    {
        $existing = static::where('organization_id', $organizationId)->get();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $defaults = [
            [
                'organization_id' => $organizationId,
                'name' => 'Main Warehouse (Central)',
                'color' => 'emerald',
                'description' => 'Central primary distribution depot and headquarters storage facility',
                'is_default' => true,
            ],
            [
                'organization_id' => $organizationId,
                'name' => 'Sub Warehouse (Regional)',
                'color' => 'blue',
                'description' => 'Regional forward distribution depot and divisional warehouse',
                'is_default' => false,
            ],
            [
                'organization_id' => $organizationId,
                'name' => 'Unit Warehouse (Workshop/Field)',
                'color' => 'amber',
                'description' => 'Field unit storeroom, workshop depot, or department storage',
                'is_default' => false,
            ],
        ];

        foreach ($defaults as $data) {
            static::create($data);
        }

        // Link any pre-existing warehouses with string type
        $types = static::where('organization_id', $organizationId)->get();
        $mainType = $types->firstWhere('name', 'Main Warehouse (Central)') ?? $types->first();
        $subType = $types->firstWhere('name', 'Sub Warehouse (Regional)') ?? $types->first();
        $unitType = $types->firstWhere('name', 'Unit Warehouse (Workshop/Field)') ?? $types->first();

        Warehouse::where('organization_id', $organizationId)->whereNull('warehouse_type_id')->each(function ($wh) use ($mainType, $subType, $unitType) {
            $matched = match (strtolower($wh->type ?? 'main')) {
                'sub' => $subType,
                'unit' => $unitType,
                default => $mainType,
            };
            if ($matched) {
                $wh->update(['warehouse_type_id' => $matched->id]);
            }
        });

        return static::where('organization_id', $organizationId)->get();
    }
}
