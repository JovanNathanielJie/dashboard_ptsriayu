<?php

namespace Tests\Feature;

use App\Models\AnalysisRun;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh')->run();
    }

    public function test_admin_can_submit_excel_upload_and_call_python_api(): void
    {
        // Mock Python API response
        Http::fake([
            'http://127.0.0.1:8001/parse-excel' => Http::response([
                'summary' => [
                    'total_baris_raw' => 100,
                    'total_baris_clean' => 95,
                    'total_faktur_unik' => 10,
                    'total_produk_unik' => 25,
                    'baris_duplikat_dihapus' => 5,
                ],
                'items' => [
                    [
                        'nomor_faktur' => 'INV-001',
                        'tanggal' => '2025-04-15',
                        'nama_barang' => 'Produk A',
                    ],
                    [
                        'nomor_faktur' => 'INV-001',
                        'tanggal' => '2025-04-15',
                        'nama_barang' => 'Produk B',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => 'admin_penjualan',
        ]);

        $file = UploadedFile::fake()->create('test.xlsx', 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($user)->post('/upload', [
            'periode_awal' => '2025-04-01',
            'periode_akhir' => '2025-04-30',
            'excel_file' => $file,
        ]);

        // Check redirect to analysis.parameter
        $analysisRun = AnalysisRun::first();
        $response->assertRedirect(route('analysis.parameter', $analysisRun));

        // Check database
        $this->assertDatabaseHas('analysis_runs', [
            'user_id' => $user->id,
            'total_baris_raw' => 100,
            'total_baris_clean' => 95,
            'total_faktur_unik' => 10,
            'total_produk_unik' => 25,
            'status' => 'done',
        ]);

        // Check transaksi items inserted
        $this->assertDatabaseCount('transaksi_items', 2);
    }
}
