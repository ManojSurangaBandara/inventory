<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'category_id',      // Category 1 (Required)
        'category_2_id',    // Category 2 (Optional)
        'category_3_id',    // Category 3 (Optional)
        'category_4_id',    // Category 4 (Optional)
        'sku',
        'name',
        'description',
        'unit',
        'unit_cost',
        'reorder_level',
        'current_stock',
        'status',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function category1(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function category2(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_2_id');
    }

    public function category3(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_3_id');
    }

    public function category4(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_4_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    /**
     * Get complete category trail (e.g. "Category 1 > Category 2 > Category 3 > Category 4").
     */
    public function getCategoryTrailAttribute(): string
    {
        $trail = [];
        if ($this->category1) $trail[] = $this->category1->name;
        if ($this->category2) $trail[] = $this->category2->name;
        if ($this->category3) $trail[] = $this->category3->name;
        if ($this->category4) $trail[] = $this->category4->name;

        return count($trail) > 0 ? implode(' > ', $trail) : 'Uncategorized';
    }
}
