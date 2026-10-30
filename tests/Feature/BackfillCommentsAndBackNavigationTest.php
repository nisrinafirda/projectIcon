<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\UrlSecurity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackfillCommentsAndBackNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@icon.co.id',
            'role' => 'admin',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@icon.co.id',
            'role' => 'user',
        ]);
    }

    public function test_extract_kendala_from_description_extracts_only_kendala_portion(): void
    {
        // 1. Exception format: Kendala, Alasan, Keterangan
        $desc1 = "Kendala: Proses Billing Corection\nAlasan: ACCRUE UN-INVOICE > 3 BULAN\nKeterangan: DITAGIHKAN";
        $this->assertSame('Proses Billing Corection', Task::extractKendalaFromDescription($desc1));

        // 2. SO Open format: Tipe SO, Price, Konfirmasi, Kendala
        $desc2 = "Tipe SO: PDL\nHarga Lama: Rp 0 | Harga Baru: Rp 3.967.742\nKonfirmasi: PDL\nKendala: Perubahan RAB, approval ke manajer";
        $this->assertSame('Perubahan RAB, approval ke manajer', Task::extractKendalaFromDescription($desc2));

        // 3. Kendala is dash or empty
        $desc3 = "Kendala: -\nAlasan: ACCRUE";
        $this->assertNull(Task::extractKendalaFromDescription($desc3));

        $desc4 = "Kendala: \nAlasan: ACCRUE";
        $this->assertNull(Task::extractKendalaFromDescription($desc4));

        // 4. Pure price info (like typical BAI tasks) without kendala
        $desc5 = 'Harga Lama: Rp 0 | Harga Baru: Rp 1.693.548 | Selisih: Rp 1.693.548';
        $this->assertNull(Task::extractKendalaFromDescription($desc5));

        // 5. Contract info without kendala
        $desc6 = 'No. Kontrak: 164.PJ/061/BGAF/2024';
        $this->assertNull(Task::extractKendalaFromDescription($desc6));

        // 6. Simple text without section headers
        $desc7 = 'Menunggu konfirmasi dari pelanggan terkait jadwal instalasi';
        $this->assertSame('Menunggu konfirmasi dari pelanggan terkait jadwal instalasi', Task::extractKendalaFromDescription($desc7));
    }

    public function test_url_security_validates_internal_urls_and_rejects_open_redirects(): void
    {
        $fallback = route('tasks.category', 'exception');

        // Valid internal URLs with query parameters
        $valid1 = 'http://localhost/tasks/category/exception?page=2&status=in_progress&search=pln';
        $this->assertSame($valid1, UrlSecurity::getSafeInternalUrl($valid1, $fallback));

        $valid2 = '/tasks/category/exception?status=approved';
        $this->assertSame($valid2, UrlSecurity::getSafeInternalUrl($valid2, $fallback));

        // Malicious external domain
        $malicious = 'http://evil.com/phishing/login';
        $this->assertSame($fallback, UrlSecurity::getSafeInternalUrl($malicious, $fallback));

        // Javascript scheme
        $js = 'javascript:alert(1)';
        $this->assertSame($fallback, UrlSecurity::getSafeInternalUrl($js, $fallback));

        // Empty / Null
        $this->assertSame($fallback, UrlSecurity::getSafeInternalUrl(null, $fallback));
        $this->assertSame($fallback, UrlSecurity::getSafeInternalUrl('', $fallback));
    }

    public function test_backfill_artisan_command_dry_run_and_execution(): void
    {
        // Setup tasks
        $t1 = Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'description' => "Kendala: Prebill juli agustus sirkulasi\nAlasan: ACCRUE\nKeterangan: DITAGIHKAN",
            'comments' => null,
        ]);

        $t2 = Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'description' => "Kendala: Kendala baru\nAlasan: ACCRUE",
            'comments' => 'Komentar manual yang sudah ada',
        ]);

        $t3 = Task::factory()->create([
            'category' => Task::CATEGORY_BAI,
            'description' => 'Harga Lama: Rp 0 | Harga Baru: Rp 1.000.000',
            'comments' => null,
        ]);

        // 1. Dry run
        $this->artisan('tasks:backfill-comments', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN]')
            ->assertSuccessful();

        $this->assertNull($t1->fresh()->comments, 'Dry run tidak boleh mengubah database.');
        $this->assertSame('Komentar manual yang sudah ada', $t2->fresh()->comments);

        // 2. Real execution
        $this->artisan('tasks:backfill-comments')
            ->expectsOutputToContain('Berhasil mengisi komentar')
            ->assertSuccessful();

        $freshT1 = $t1->fresh();
        $this->assertSame('Prebill juli agustus sirkulasi', $freshT1->comments);
        $this->assertNotNull($freshT1->comment_updated_at);
        $this->assertEquals($this->admin->id, $freshT1->comment_updated_by);

        // T2 must not be overwritten
        $this->assertSame('Komentar manual yang sudah ada', $t2->fresh()->comments);

        // T3 (BAI without kendala) remains null
        $this->assertNull($t3->fresh()->comments);
    }

    public function test_back_button_on_customer_and_detail_pages_preserves_filters_and_rejects_open_redirect(): void
    {
        $task = Task::factory()->create([
            'category' => Task::CATEGORY_EXCEPTION,
            'customer_name' => 'PT PLN PERSERO',
            'description' => "Kendala: Menunggu verifikasi faktur pajak\nAlasan: ACCRUE",
            'comments' => 'Menunggu verifikasi faktur pajak',
        ]);

        $safeOriginUrl = 'http://localhost/tasks/category/exception?status=in_progress&page=2&search=pln';

        // 1. Visit Customer detail page with safe back_to
        $customerSlug = Str::slug('PT PLN PERSERO');
        $response = $this->actingAs($this->admin)->get(route('tasks.exception.customer', [
            'customer' => $customerSlug,
            'back_to' => $safeOriginUrl,
        ]));

        $response->assertOk();
        $response->assertSee(htmlspecialchars($safeOriginUrl, ENT_QUOTES), false);
        $response->assertSee('Kembali ke Daftar Exception');

        // 2. Visit Customer detail page with malicious back_to -> should fallback
        $maliciousUrl = 'https://attacker.site/steal';
        $responseEvil = $this->actingAs($this->admin)->get(route('tasks.exception.customer', [
            'customer' => $customerSlug,
            'back_to' => $maliciousUrl,
        ]));

        $responseEvil->assertOk();
        $responseEvil->assertDontSee('https://attacker.site');
        $responseEvil->assertSee(route('tasks.category', 'exception'));

        // 3. Detail link inside customer page carries back_to forward
        $detailLinkRegex = route('tasks.show', ['task' => $task->id]);
        $response->assertSee($detailLinkRegex);

        // 4. Visit Task Detail page (tasks.show) with back_to pointing to customer page
        $customerPageWithFilters = 'http://localhost/tasks/category/exception/customer/'.$customerSlug.'?back_to='.urlencode($safeOriginUrl).'&status=in_progress&page=1';
        $showResponse = $this->actingAs($this->admin)->get(route('tasks.show', [
            'task' => $task->id,
            'back_to' => $customerPageWithFilters,
        ]));

        $showResponse->assertOk();
        $showResponse->assertSee(htmlspecialchars($customerPageWithFilters, ENT_QUOTES), false);
        $showResponse->assertSee('Kembali ke '.$task->category_label);
    }

    public function test_import_populates_comments_from_kendala_source_and_preserves_detail(): void
    {
        $csv = "idPelanggan,namaPelanggan,idPelangganProduk,namaLayananProduk,tahunBulanTagihan,billingPeriodeAwal,billingPeriodeAkhir, rpLayanan ,rpInstalasi,rpRestitusi, rpException ,bidangRevenue,subBidangRevenue,segmenIcon,alasanException,petugasException,tanggalException,SID / THBL,SBU OWNER,STATUS,TANGGAL KOMITMEN CLOSE (MM/DD/YYYY),Bulan,PROGRES / KENDALA / SUPPORT,KETERANGAN,TANGGAL KUNCI KOMITMEN AWAL,WEEK KOMITMEN,CEK ,SALES,ADEV\n";
        $csv .= "C01,PT TESTING DETAIL,P01,INTERNET,202610,2026-10-01,2026-10-31,1000000,0,0,500000,REV,SUB,PLN,ACCRUE UN-INVOICE > 3 BULAN,Petugas,2026-10-10,EXP-DETAIL-01,SBU,CLOSE,2026-10-20,Okt,\"Kendala: Proses Rekonsiliasi Bank\nAlasan: ACCRUE UN-INVOICE > 3 BULAN\nKeterangan: DITAGIHKAN\",DITAGIHKAN,2026-10-01,W1,Cek1,Sales1,Adev1\n";

        $file = UploadedFile::fake()->createWithContent('exception_detail_test.csv', $csv);
        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => Task::CATEGORY_EXCEPTION,
        ]);
        $response->assertRedirect(route('tasks.category', 'exception'));

        $task = Task::where('document_number', 'EXP-DETAIL-01')->first();
        $this->assertNotNull($task);

        // comments should ONLY have the Kendala portion
        $this->assertSame('Proses Rekonsiliasi Bank', $task->comments);

        // description (Detail page) still contains full kendala, alasan, keterangan
        $this->assertStringContainsString('Kendala: Proses Rekonsiliasi Bank', $task->description);
        $this->assertStringContainsString('Alasan: ACCRUE UN-INVOICE > 3 BULAN', $task->description);
        $this->assertStringContainsString('Keterangan: DITAGIHKAN', $task->description);
    }
}
