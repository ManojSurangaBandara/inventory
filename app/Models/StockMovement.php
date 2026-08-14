<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMovement extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'reference_code',
        'type',
        'warehouse_id',
        'target_warehouse_id',
        'inventory_item_id',
        'quantity',
        'item_lot_number',
        'source_system',
        'current_state',
        'created_by',
        'notes',
        'rejection_reason',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockMovementItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get total quantity across all line items (or fallback to legacy single quantity).
     */
    public function getTotalQuantityAttribute(): int
    {
        if ($this->items()->exists()) {
            return (int) $this->items()->sum('quantity');
        }
        return (int) ($this->quantity ?? 0);
    }
}
