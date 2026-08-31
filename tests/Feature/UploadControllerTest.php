<?php

namespace Tests\Feature;

use App\Jobs\ProcessTransactionUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh')->run();
    }

    public function test_admin_can_submit_excel_upload_and_dispatch_processing_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create([
            'role' => 'admin_penjualan',
        ]);

        $file = UploadedFile::fake()->create('test.xlsx', 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($user)->post('/upload', [
            'periode_awal' => '2025-04-01',
            'periode_akhir' => '2025-04-30',
            'excel_file' => $file,
        ]);

        $response->assertRedirect(route('upload.create'));
        Queue::assertPushed(ProcessTransactionUpload::class);
    }
}
