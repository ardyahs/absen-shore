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

    public function test_foto_wajib_jpeg_dari_kamera(): void
    {
        $karyawan = User::factory()->create();
        $kiriman = ['foto' => 'data:image/png;base64,AAAA'] + $this->kiriman();

        $this->actingAs($karyawan)->postJson('/absen', $kiriman)->assertStatus(422);
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
