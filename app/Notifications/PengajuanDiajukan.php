<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

// Dikirim ke pengawas + manager saat petugas mengajukan tukar shift/lembur baru.
class PengajuanDiajukan extends Notification
{
    public function __construct(
        public string $jenis,        // 'shift' | 'lembur'
        public string $namaPetugas,
        public string $tanggalLabel,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->jenis === 'shift' ? 'tukar shift' : 'lembur';

        return [
            'pesan' => "Pengajuan {$label} baru dari {$this->namaPetugas} ({$this->tanggalLabel})",
            'url'   => $this->url,
        ];
    }
}
