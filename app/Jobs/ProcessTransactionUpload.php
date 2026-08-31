<?php

namespace App\Jobs;

use App\Models\AnalysisRun;
use App\Models\TransaksiItem;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessTransactionUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $analysisRunId,
        public string $fullPath,
        public string $periodeAwal,
        public string $periodeAkhir,
    ) {}

    public function handle(): void
    {
        $analysisRun = AnalysisRun::findOrFail($this->analysisRunId);

        $analysisRun->update(['status' => 'processing']);

        try {
            $sheets = Excel::toCollection(null, $this->fullPath);
            $rows = $sheets->first();

            if ($rows === null || $rows->isEmpty()) {
                $analysisRun->update(['status' => 'failed']);
                return;
            }

            $currentFaktur = null;
            $currentTanggal = null;
            $foundNomorFaktur = false;
            $insertBatch = [];
            $totalInserted = 0;
            $now = now();

            foreach ($rows as $row) {
                $kolomD = $row->get(3);
                $kolomG = $row->get(6);
                $kolomC = $row->get(2);
                $kolomH = $row->get(7);
                $kolomL = $row->get(11);

                $label = trim((string) ($kolomD ?? ''));
                $value = trim((string) ($kolomG ?? ''));
                $kodeBarang = trim((string) ($kolomC ?? ''));
                $namaBarang = trim((string) ($kolomH ?? ''));
                $isKuantitasValid = is_numeric($kolomL);

                if ($label === 'Nomor #') {
                    $currentFaktur = $value !== '' ? $value : null;
                    $foundNomorFaktur = true;
                    continue;
                }

                if ($label === 'Tanggal') {
                    if ($kolomG instanceof \DateTimeInterface) {
                        $currentTanggal = Carbon::instance($kolomG)->toDateString();
                    } elseif (is_numeric($kolomG)) {
                        $currentTanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($kolomG)->format('Y-m-d');
                    } elseif (! empty($kolomG)) {
                        try {
                            $currentTanggal = Carbon::parse((string) $kolomG)->toDateString();
                        } catch (\Exception $e) {
                            $currentTanggal = null;
                        }
                    }
                    continue;
                }

                $dalamRentang = $currentTanggal !== null
                    && $currentTanggal >= $this->periodeAwal
                    && $currentTanggal <= $this->periodeAkhir;

                if ($currentFaktur !== null && $kodeBarang !== '' && $namaBarang !== '' && $isKuantitasValid && $dalamRentang) {
                    $insertBatch[] = [
                        'analysis_run_id' => $analysisRun->id,
                        'nomor_faktur' => $currentFaktur,
                        'tanggal' => $currentTanggal,
                        'nama_barang' => $namaBarang,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (count($insertBatch) >= 500) {
                        TransaksiItem::insert($insertBatch);
                        $totalInserted += count($insertBatch);
                        $insertBatch = [];
                    }
                }
            }

            if (! $foundNomorFaktur) {
                $analysisRun->update(['status' => 'failed']);
                return;
            }

            if (! empty($insertBatch)) {
                TransaksiItem::insert($insertBatch);
                $totalInserted += count($insertBatch);
            }

            if ($totalInserted === 0) {
                $analysisRun->update(['status' => 'failed']);
                return;
            }

            $analysisRun->update([
                'total_baris_raw' => $totalInserted,
                'status' => 'done',
            ]);

            Storage::delete($this->fullPath);
        } catch (\Throwable $e) {
            $analysisRun->update(['status' => 'failed']);
            throw $e;
        }
    }
}
