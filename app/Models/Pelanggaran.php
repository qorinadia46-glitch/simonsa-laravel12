<?php

namespace App\Models;

use App\Models\Concerns\MencatatWaktu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggaran extends Model
{
    use MencatatWaktu;

    protected $table = 'pelanggaran';

    protected $primaryKey = 'id_pelanggaran';

    public $timestamps = false;

    protected $fillable = [
        'id_santri',
        'id_kategori',
        'id_user',
        'tanggal_pelanggaran',
        'kronologi',
        'poin_pelanggaran',
        'tindak_lanjut',
        'bukti_foto',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pelanggaran' => 'date',
            'poin_pelanggaran' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'id_santri', 'id_santri');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPelanggaran::class, 'id_kategori', 'id_kategori');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_user', 'id_user');
    }

    public function tazir(): HasMany
    {
        return $this->hasMany(Tazir::class, 'id_pelanggaran', 'id_pelanggaran');
    }
}
