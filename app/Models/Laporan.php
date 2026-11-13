<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    use HasFactory;

    protected $table = 'laporan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'no_pekerjaan',
        'minggu',
        'rw',
        'kat',
        'jenis_pekerjaan',
        'judul',
        'alamat',
        'lokasi_detail',
        'kendala',
        'petugas',
        'regu',
        'no_hp',
        'jumlah_petugas',
        'progres',
        'status',
        'foto_sebelum',
        'foto_proses',
        'foto_sesudah',
        'lat',
        'lng',
        'tgl',
        'created_at',
    ];
}

