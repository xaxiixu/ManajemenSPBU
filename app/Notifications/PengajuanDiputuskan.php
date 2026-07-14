<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

// Dikirim ke petugas saat pengajuan tukar shift/lembur miliknya disetujui/ditolak.
class PengajuanDiputuskan extends Notification
{
    public function __construct(
        public string $jenis,        // 'shift' | 'lembur'
        public string $status,       // 'approved' | 'rejected'
        public string $tanggalLabel,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->jenis === 'shift' ? 'Tukar shift' : 'Lembur';
        $aksi  = $this->status === 'approved' ? 'disetujui' : 'ditolak';

        return [
            'pesan' => "{$label} kamu tanggal {$this->tanggalLabel} {$aksi}",
            'url'   => $this->url,
        ];
    }
}
