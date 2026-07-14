<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokUpload extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stok_uploads';
    protected $guarded = [];

    // ── Simplified status set ────────────────────────────────────
    const STATUS_DRAFT              = 'Draft';
    const STATUS_PERLU_PERBAIKAN    = 'Perlu Perbaikan';
    const STATUS_MENUNGGU_VERIFIKASI = 'Menunggu Verifikasi';
    const STATUS_SIAP_DIFINALISASI  = 'Siap Difinalisasi';
    const STATUS_SELESAI            = 'Selesai';
    const STATUS_DIBATALKAN         = 'Dibatalkan';

    public static function validStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PERLU_PERBAIKAN,
            self::STATUS_MENUNGGU_VERIFIKASI,
            self::STATUS_SIAP_DIFINALISASI,
            self::STATUS_SELESAI,
            self::STATUS_DIBATALKAN,
        ];
    }

    // ── Stepper constants ────────────────────────────────────────
    const STEP_UPLOAD      = 1;
    const STEP_PEMERIKSAAN = 2;
    const STEP_VERIFIKASI  = 3;
    const STEP_REVIEW      = 4;

    protected $casts = [
        'upload_date'         => 'datetime',
        'sheets_count'        => 'integer',
        'rows_count'          => 'integer',
        'valid_rows_count'    => 'integer',
        'error_rows_count'    => 'integer',
        'rejected_rows_count' => 'integer',
        'current_step'        => 'integer',
        'cancelled_at'        => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    // ── Relationships ────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(StokUploadDetail::class, 'stok_upload_id');
    }

    public function histories()
    {
        return $this->hasMany(StockHistory::class, 'stok_upload_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Active batches (not soft-deleted) */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /** Soft-deleted / trashed batches */
    public function scopeTrashedOnly($query)
    {
        return $query->onlyTrashed();
    }

    /** Batches eligible for deletion (not yet finalized) */
    public function scopeDeletable($query)
    {
        return $query->whereNotIn('status', [self::STATUS_SELESAI, self::STATUS_DIBATALKAN]);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** True if this batch can be soft-deleted (not finalised) */
    public function isDeletable(): bool
    {
        return ! in_array($this->status, [self::STATUS_SELESAI, self::STATUS_DIBATALKAN]);
    }

    /** True if Batalkan Transaksi is available (only after finalisation) */
    public function isCancellable(): bool
    {
        return $this->status === self::STATUS_SELESAI;
    }

    /** Derive next stepper step based on status */
    public function resolveNextStep(): int
    {
        return match ($this->status) {
            self::STATUS_DRAFT              => self::STEP_PEMERIKSAAN,
            self::STATUS_PERLU_PERBAIKAN    => self::STEP_PEMERIKSAAN,
            self::STATUS_MENUNGGU_VERIFIKASI => self::STEP_VERIFIKASI,
            self::STATUS_SIAP_DIFINALISASI  => self::STEP_REVIEW,
            default                         => self::STEP_REVIEW,
        };
    }

    /** Human-readable status badge colour for Tailwind */
    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT              => 'bg-slate-100 text-slate-700',
            self::STATUS_PERLU_PERBAIKAN    => 'bg-rose-100 text-rose-800',
            self::STATUS_MENUNGGU_VERIFIKASI => 'bg-amber-100 text-amber-800',
            self::STATUS_SIAP_DIFINALISASI  => 'bg-indigo-100 text-indigo-800',
            self::STATUS_SELESAI            => 'bg-emerald-100 text-emerald-800',
            self::STATUS_DIBATALKAN         => 'bg-gray-200 text-gray-600 line-through',
            default                         => 'bg-slate-100 text-slate-600',
        };
    }
}
