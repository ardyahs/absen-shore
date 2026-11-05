<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AbsenTest extends TestCase
{
    use RefreshDatabase;

    private function kiriman(float $geserLat = 0): array
    {
        $p = Pengaturan::aktif();

        return [
            'foto' => 'data:image/jpeg;base64,'.base64_encode("\xFF\xD8".str_repeat("\x00", 800)),
            'lat' => $p->lat + $geserLat,
            'lng' => $p->lng,
            'akurasi' => 12,
        ];
    }

    public function test_absen_lebih_awal_dapat_reward_dan_hanya_sekali_sehari(): void
    {
        Storage::fake('local');
        config(['absensi.simulasi_jam' => '15:42']);
        $karyawan = User::factory()->create();

        $this->actingAs($karyawan)->postJson('/absen', $this->kiriman())->assertOk();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $karyawan->id, 'jam' => '15:42', 'kategori' => 'reward', 'nominal' => 5000, 'status' => 'valid',
        ]);
        Storage::disk('local')->assertExists(Attendance::first()->foto);

        $this->actingAs($karyawan)->postJson('/absen', $this->kiriman())->assertStatus(409);
    }

    public function test_di_luar_radius_masuk_antrean_lalu_disetujui_admin(): void
    {
        Storage::fake('local');
        config(['absensi.simulasi_jam' => '16:20']);
        $karyawan = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($karyawan)->postJson('/absen', $this->kiriman(0.01))->assertOk(); // ±1,1 km
        $absen = Attendance::first();
        $this->assertSame('perlu_ditinjau', $absen->status);
        $this->assertSame(-10000, $absen->nominal);

        $this->actingAs($admin)->post("/admin/tinjau/{$absen->id}", ['keputusan' => 'setuju'])->assertRedirect();
        $this->assertSame('valid', $absen->fresh()->status);
    }

    public function test_absen_di_luar_jam_buka_ditolak(): void
    {
        Storage::fake('local');
        config(['absensi.simulasi_jam' => '03:37']);
        $karyawan = User::factory()->create();

        $this->actingAs($karyawan)->get('/absen')->assertSee('Absen dibuka pukul 15:00')->assertDontSee('tombol-absen');
        $this->actingAs($karyawan)->postJson('/absen', $this->kiriman())->assertStatus(422);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_foto_wajib_jpeg_dari_kamera(): void
    {
        config(['absensi.simulasi_jam' => '15:30']);
        $karyawan = User::factory()->create();
        $kiriman = ['foto' => 'data:image/png;base64,AAAA'] + $this->kiriman();

        $this->actingAs($karyawan)->postJson('/absen', $kiriman)->assertStatus(422);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_admin_mengubah_gaji_harian_dan_rekap_menghitung_total_gaji(): void
    {
        Storage::fake('local');
        config(['absensi.simulasi_jam' => '15:42']);
        $karyawan = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->assertSame(50000, $karyawan->fresh()->gaji_harian);

        $this->actingAs($admin)->put("/admin/karyawan/{$karyawan->id}/gaji", ['gaji_harian' => 60000])->assertRedirect();
        $this->assertSame(60000, $karyawan->fresh()->gaji_harian);

        $this->actingAs($karyawan)->postJson('/absen', $this->kiriman())->assertOk();
        $this->actingAs($admin)->get('/admin/rekap')->assertSee('Rp65.000');
    }

    public function test_karyawan_tidak_bisa_mengubah_gaji(): void
    {
        $karyawan = User::factory()->create();

        $this->actingAs($karyawan)->put("/admin/karyawan/{$karyawan->id}/gaji", ['gaji_harian' => 999999]);
        $this->assertSame(50000, $karyawan->fresh()->gaji_harian);
    }

    public function test_admin_menambah_absen_manual_dengan_nominal_sendiri(): void
    {
        $karyawan = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $kemarin = now(config('absensi.zona'))->subDay()->toDateString();

        $this->actingAs($admin)->post("/admin/karyawan/{$karyawan->id}/absen", [
            'tanggal' => $kemarin, 'jam' => '16:40', 'nominal' => -2000, 'catatan' => 'HP rusak',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $karyawan->id, 'tanggal' => $kemarin, 'jam' => '16:40', 'kategori' => 'terlambat',
            'nominal' => -2000, 'status' => 'valid', 'foto' => null, 'dibuat_oleh' => $admin->id,
        ]);

        // Tanggal yang sama tidak boleh dobel
        $this->actingAs($admin)->post("/admin/karyawan/{$karyawan->id}/absen", [
            'tanggal' => $kemarin, 'jam' => '15:00', 'nominal' => 5000,
        ])->assertSessionHasErrors('tanggal');

        $this->actingAs($admin)->get("/admin/karyawan/{$karyawan->id}/absen")->assertOk()->assertSee('HP rusak')->assertSee('Rp48.000');
    }

    public function test_admin_mengubah_status_lalu_menghapus_absen(): void
    {
        Storage::fake('local');
        config(['absensi.simulasi_jam' => '15:42']);
        $karyawan = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($karyawan)->postJson('/absen', $this->kiriman())->assertOk();
        $absen = Attendance::first();

        $this->actingAs($admin)->patch("/admin/absen/{$absen->id}/status", ['status' => 'ditolak'])->assertRedirect();
        $this->assertSame('ditolak', $absen->fresh()->status);

        $this->actingAs($admin)->delete("/admin/absen/{$absen->id}")->assertRedirect();
        $this->assertDatabaseCount('attendances', 0);
        Storage::disk('local')->assertMissing($absen->foto);
    }

    public function test_dashboard_menampilkan_absen_tanggal_sebelumnya(): void
    {
        $karyawan = User::factory()->create(['nama' => 'Sari Lestari']);
        $admin = User::factory()->admin()->create();
        $kemarin = now(config('absensi.zona'))->subDay()->toDateString();
        $this->actingAs($admin)->post("/admin/karyawan/{$karyawan->id}/absen", ['tanggal' => $kemarin, 'jam' => '15:50', 'nominal' => 0]);

        $this->actingAs($admin)->get('/admin')->assertDontSee('Sari Lestari');
        $this->actingAs($admin)->get('/admin?tanggal='.$kemarin)->assertOk()->assertSee('Sari Lestari')->assertSee('15:50');
    }

    public function test_karyawan_tidak_bisa_mengelola_absen(): void
    {
        $karyawan = User::factory()->create();

        $this->actingAs($karyawan)->post("/admin/karyawan/{$karyawan->id}/absen", [
            'tanggal' => now()->toDateString(), 'jam' => '15:00', 'nominal' => 100000,
        ]);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_karyawan_tidak_bisa_membuka_halaman_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertRedirect(route('home'));
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => 'rahasia1', 'aktif' => false]);

        $this->post('/login', ['username' => 'budi', 'password' => 'rahasia1'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
