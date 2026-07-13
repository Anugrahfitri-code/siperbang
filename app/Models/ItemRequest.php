<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty_requested'   => 'integer',
        'qty_available'   => 'integer',
        'qty_fulfilled'   => 'integer',
        'qty_to_procure'  => 'integer',
        'stock_allocated' => 'boolean',
        'date'            => 'date',
        'last_updated'    => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class);
    }

    public function distribution()
    {
        return $this->hasOne(Distribution::class);
    }

    public function procurements()
    {
        return $this->hasMany(Procurement::class);
    }

    // ── Helpers ──────────────────────────────────────────────────

    /** Qty still unfulfilled from stock, needing procurement */
    public function getQtyUnfulfilledAttribute(): int
    {
        return max(0, $this->qty_requested - $this->qty_fulfilled);
    }

    /** True if ALL requested qty is covered (stock + procurement) */
    public function getIsFullyFulfilledAttribute(): bool
    {
        return $this->qty_fulfilled >= $this->qty_requested;
    }

    /** Allowed status values */
    public static function validStatuses(): array
    {
        return [
            'Diajukan',
            'Dicek',
            'Terpenuhi',
            'Terpenuhi Sebagian',
            'Siap Didistribusikan',
            'Perlu Pengadaan',
            'Dalam Pengadaan',
            'Ditolak',
            'Selesai',
        ];
    }
}
