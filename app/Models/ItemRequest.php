<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty_requested' => 'integer',
        'qty_available' => 'integer',
        'qty_fulfilled' => 'integer',
        'qty_to_procure' => 'integer',
        'stock_allocated' => 'boolean',
        'date' => 'date:Y-m-d',
        'last_updated' => 'date:Y-m-d',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function bonHeader()
    {
        return $this->belongsTo(BonHeader::class, 'bon_header_id');
    }

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

    /**
     * Batasi data pada pengajuan yang benar-benar berasal dari Ketua Tim.
     */
    public function scopeForTeamLeaders(Builder $query): Builder
    {
        return $query->whereHas('user', function (Builder $userQuery) {
            $userQuery->whereIn('role', ['Ketua Tim', 'Ketua Tim Kerja', 'Superadmin']);
        });
    }

    /**
     * Item yang sudah disetujui/diproses oleh petugas untuk rekap BON.
     *
     * Item Ditolak tetap masuk hanya jika sebelumnya sudah ada bagian
     * yang benar-benar terpenuhi. Contoh: sebagian barang sudah diberikan,
     * kemudian sisa yang belum tersedia dibatalkan.
     */
    public function scopeApprovedForBonRecap(Builder $query): Builder
    {
        return $query->where(function (Builder $approvedQuery) {
            $approvedQuery
                ->whereIn('status', [
                    'Terpenuhi',
                    'Terpenuhi Sebagian',
                    'Siap Didistribusikan',
                    'Perlu Pengadaan',
                    'Dalam Pengadaan',
                    'Selesai',
                ])
                ->orWhere(function (Builder $partiallyApprovedQuery) {
                    $partiallyApprovedQuery
                        ->where('status', 'Ditolak')
                        ->where('qty_fulfilled', '>', 0);
                });
        });
    }

    /**
     * Item yang masih mempunyai sisa kebutuhan untuk dibeli petugas.
     */
    public function scopeNeedsProcurement(Builder $query): Builder
    {
        return $query
            ->where('qty_to_procure', '>', 0)
            ->whereNotIn('status', [
                'Draft',
                'Diajukan',
                'Ditolak',
                'Selesai',
            ]);
    }

    /**
     * Jumlah yang dicetak pada rekap BON yang disetujui.
     *
     * Jika sebagian sudah dipenuhi lalu sisanya dibatalkan,
     * yang dicetak hanya jumlah yang benar-benar disetujui/terpenuhi.
     */
    public function getApprovedRecapQtyAttribute(): int
    {
        if ($this->status === 'Ditolak') {
            return max(0, (int) $this->qty_fulfilled);
        }

        if (
            $this->status === 'Terpenuhi Sebagian'
            && (int) $this->qty_to_procure === 0
        ) {
            return max(0, (int) $this->qty_fulfilled);
        }

        return max(0, (int) $this->qty_requested);
    }
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
            'Draft',
            'Menunggu Verifikasi',
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
