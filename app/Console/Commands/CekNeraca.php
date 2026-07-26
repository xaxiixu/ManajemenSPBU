<?php

namespace App\Console\Commands;

use App\Services\NeracaService;
use Illuminate\Console\Command;

class CekNeraca extends Command
{
    protected $signature = 'neraca:cek {tanggal? : Tanggal snapshot (YYYY-MM-DD), default hari ini}';

    protected $description = 'Tampilkan snapshot Neraca (aset/kewajiban/modal + balance check) per tanggal tertentu, tanpa UI';

    public function handle(): int
    {
        $tanggal = $this->argument('tanggal') ?? now()->format('Y-m-d');

        $hasil = NeracaService::hitung($tanggal);

        $this->info("Neraca per tanggal: {$tanggal}");
        $this->newLine();

        $this->line('<fg=blue;options=bold>AKTIVA (ASET)</>');
        $this->table(
            ['Kode Akun', 'Nama Akun', 'Saldo (Rp)'],
            $hasil['aset']->where('saldo', '!=', 0)->map(fn ($coa) => [
                $coa->kode_akun,
                $coa->nama_akun,
                number_format($coa->saldo),
            ])->all()
        );
        $this->line('Total Aktiva: <fg=blue;options=bold>Rp '.number_format($hasil['totalAset']).'</>');
        $this->newLine();

        $this->line('<fg=magenta;options=bold>KEWAJIBAN</>');
        $this->table(
            ['Kode Akun', 'Nama Akun', 'Saldo (Rp)'],
            $hasil['kewajiban']->where('saldo', '!=', 0)->map(fn ($coa) => [
                $coa->kode_akun,
                $coa->nama_akun,
                number_format($coa->saldo),
            ])->all()
        );
        $this->line('Total Kewajiban: Rp '.number_format($hasil['totalKewajiban']));
        $this->newLine();

        $this->line('<fg=magenta;options=bold>MODAL</>');
        $modalRows = $hasil['modal']->where('saldo', '!=', 0)->map(fn ($coa) => [
            $coa->kode_akun,
            $coa->nama_akun,
            number_format($coa->saldo),
        ])->all();
        $modalRows[] = ['—', 'Laba/Rugi Berjalan (s/d tanggal ini)', number_format($hasil['labaBerjalan'])];
        $this->table(['Kode Akun', 'Nama Akun', 'Saldo (Rp)'], $modalRows);
        $this->line('Total Modal: Rp '.number_format($hasil['totalModal']));
        $this->newLine();

        $this->line('<fg=magenta;options=bold>Total Pasiva (Kewajiban + Modal): Rp '.number_format($hasil['totalPasiva']).'</>');
        $this->newLine();

        if ($hasil['balance']) {
            $this->line("<fg=green;options=bold>[SEIMBANG] Total Aktiva Rp ".number_format($hasil['totalAset'])
                ." = Total Pasiva Rp ".number_format($hasil['totalPasiva'])."</>");
        } else {
            $this->line("<fg=red;options=bold>[TIDAK SEIMBANG] Selisih Rp ".number_format(abs($hasil['selisih']))
                .' ('.($hasil['selisih'] > 0 ? 'Aktiva lebih besar' : 'Pasiva lebih besar').")</>");
        }

        return self::SUCCESS;
    }
}
