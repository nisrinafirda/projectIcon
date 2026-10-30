<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerDetailIdPelangganTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'username' => 'adminuser',
        ]);

        $this->employee = User::factory()->create([
            'role' => 'user',
            'name' => 'Budi Santoso',
            'email' => 'budi@test.com',
            'username' => 'budisantoso',
        ]);
    }

    public function test_exception_import_stores_id_pelanggan(): void
    {
        $csv = "idPelanggan,namaPelanggan,idPelangganProduk,namaLayananProduk,tahunBulanTagihan,rpException,SID / THBL,STATUS,SALES\n"
            ."141090042621,PT INDONESIA COMNETS PLUS,141202002634,METRONET,2026-03,5000000,141202002634 / 2026-03,CLOSE,Budi Santoso\n";

        $file = UploadedFile::fake()->createWithContent('Exception.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'exception',
        ]);

        $response->assertRedirect(route('tasks.category', 'exception'));
        $response->assertSessionHas('success');

        $task = Task::where('category', 'exception')->where('document_number', '141202002634 / 2026-03')->first();
        $this->assertNotNull($task);
        $this->assertSame('141090042621', $task->id_pelanggan);
    }

    public function test_kontrak_exp_import_stores_id_pelanggan_when_column_present(): void
    {
        $csv = "SID,ID Pelanggan,NAMA PELANGGAN,LAYANAN PRODUK,RUPIAH LAYANAN,Nomor Kontrak Lama,Status,Nama Sales\n"
            ."140000001158,CUST-99901,PT Sumber Rejeki,ICONNET,1000000,AGR/2210/2678,Done,Budi Santoso\n";

        $file = UploadedFile::fake()->createWithContent('Contract_Expired.csv', $csv);

        $response = $this->actingAs($this->admin)->post(route('tasks.import.process'), [
            'file' => $file,
            'duplicate_action' => 'skip',
            'target_category' => 'kontrak_exp',
        ]);

        $response->assertRedirect(route('tasks.category', 'kontrak_exp'));
        $response->assertSessionHas('success');

        $task = Task::where('category', 'kontrak_exp')->where('document_number', '140000001158')->first();
        $this->assertNotNull($task);
        $this->assertSame('CUST-99901', $task->id_pelanggan);
    }

    public function test_exception_customer_detail_displays_id_pa_produk_and_periode(): void
    {
        $task = Task::create([
            'category' => Task::CATEGORY_EXCEPTION,
            'document_number' => '141202002634 / 2026-10',
            'id_pelanggan' => '141090042621',
            'customer_name' => 'PT Semen Indonesia',
            'title' => 'Tugas Exception Semen Indonesia',
            'service_type' => 'METRONET',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->employee->id,
            'rp_exception' => 5000000,
            'kategori_segmen' => Task::SEGMEN_PUBLIK,
        ]);

        $slug = Str::slug($task->customer_name);
        $response = $this->actingAs($this->admin)->get(route('tasks.exception.customer', $slug));

        $response->assertOk();
        $response->assertSee('ID PA / SID');
        $response->assertSee('141202002634');
        $response->assertSee('2026-10');
        $response->assertDontSee('141090042621');
    }

    public function test_kontrak_exp_customer_detail_displays_no_kontrak_and_id_pa(): void
    {
        $task = Task::create([
            'category' => Task::CATEGORY_KONTRAK_EXP,
            'document_number' => '140000001158',
            'id_pelanggan' => '141090099888',
            'customer_name' => 'Dinas Kominfo Mojokerto',
            'title' => 'Tugas Kontrak Exp Mojokerto',
            'description' => "No. Kontrak: AGR/2210/2678\nTarget: 30 Sep",
            'service_type' => 'ICONNET 50 MBPS',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->employee->id,
            'kategori_segmen' => Task::SEGMEN_PUBLIK,
        ]);

        $slug = Str::slug($task->customer_name);
        $response = $this->actingAs($this->admin)->get(route('tasks.category.customer', [
            'category' => 'kontrak_exp',
            'customer' => $slug,
        ]));

        $response->assertOk();
        $response->assertSee('NO KONTRAK / ID PA');
        $response->assertSee('AGR/2210/2678');
        $response->assertSee('ID PA: 140000001158');
        $response->assertDontSee('ID Pelanggan: 141090099888');
    }

    public function test_so_open_and_all_tasks_customer_detail_keep_original_id_column(): void
    {
        $task = Task::create([
            'category' => Task::CATEGORY_SSO_OPEN,
            'document_number' => 'PA-SO-12345',
            'id_pelanggan' => 'CUST-SO-999',
            'customer_name' => 'PT Bangun Jaya',
            'title' => 'Tugas SO Bangun Jaya',
            'service_type' => 'METRONET',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->employee->id,
            'kategori_segmen' => Task::SEGMEN_PUBLIK,
        ]);

        $slug = Str::slug($task->customer_name);

        // In SO Open category
        $responseSo = $this->actingAs($this->admin)->get(route('tasks.category.customer', [
            'category' => 'sso_open',
            'customer' => $slug,
        ]));
        $responseSo->assertOk();
        $responseSo->assertSee('ID PA / SID');
        $responseSo->assertDontSee('NO KONTRAK / ID PELANGGAN');
        $responseSo->assertDontSee('ID PELANGGAN / PERIODE');
        $responseSo->assertSee('PA-SO-12345');

        // In Semua Tugas (generic customer detail)
        $responseAll = $this->actingAs($this->admin)->get(route('tasks.customer', $slug));
        $responseAll->assertOk();
        $responseAll->assertSee('ID PA / SID');
        $responseAll->assertSee('PA-SO-12345');
    }

    public function test_search_can_find_by_id_pelanggan_and_id_pelanggan_produk(): void
    {
        Task::create([
            'category' => Task::CATEGORY_EXCEPTION,
            'document_number' => '141202002634 / 2026-10',
            'id_pelanggan' => '141090042621',
            'customer_name' => 'PT Multiusaha Jaya',
            'title' => 'Tugas Multiusaha 1',
            'service_type' => 'METRONET',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->employee->id,
            'rp_exception' => 2000000,
            'kategori_segmen' => Task::SEGMEN_PUBLIK,
        ]);

        Task::create([
            'category' => Task::CATEGORY_EXCEPTION,
            'document_number' => '141202008888 / 2026-10',
            'id_pelanggan' => '141090049999',
            'customer_name' => 'PT Multiusaha Jaya',
            'title' => 'Tugas Multiusaha 2',
            'service_type' => 'IP VPN',
            'status' => Task::STATUS_IN_PROGRESS,
            'user_id' => $this->employee->id,
            'rp_exception' => 3000000,
            'kategori_segmen' => Task::SEGMEN_PUBLIK,
        ]);

        $slug = Str::slug('PT Multiusaha Jaya');

        // Search by idPelanggan
        $responseIdPelanggan = $this->actingAs($this->admin)->get(route('tasks.exception.customer', [
            'customer' => $slug,
            'search' => '141090042621',
            'back_to' => route('tasks.category', 'exception'),
        ]));
        $responseIdPelanggan->assertOk();
        $responseIdPelanggan->assertSee('141202002634');
        $responseIdPelanggan->assertDontSee('141202008888');

        // Search by idPelangganProduk (stored inside document_number)
        $responseIdProduk = $this->actingAs($this->admin)->get(route('tasks.exception.customer', [
            'customer' => $slug,
            'search' => '141202008888',
            'back_to' => route('tasks.category', 'exception'),
        ]));
        $responseIdProduk->assertOk();
        $responseIdProduk->assertSee('141202008888');
        $responseIdProduk->assertDontSee('141202002634');
    }
}
