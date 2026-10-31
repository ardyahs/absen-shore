// Halaman pengaturan admin: tambah/hapus tingkat pinalti, geser jam, ringkasan aturan.
(() => {
  'use strict';

  const form = document.getElementById('form-pengaturan');
  if (!form) return;

  const wadah = document.getElementById('pinalti');
  const ringkasanEl = document.getElementById('ringkasan');
  const galat = document.getElementById('aturan-galat');
  const jamMasuk = document.getElementById('jam_masuk');

  const keMenit = (v) => { const [h, m] = String(v).split(':').map(Number); return h * 60 + m; };
  const keJam = (m) => { m = ((m % 1440) + 1440) % 1440; return String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0'); };
  const rp = (n) => 'Rp' + Number(n || 0).toLocaleString('id-ID');
  const baris = () => [...wadah.querySelectorAll('[data-pinalti]')];
  const el = (nama) => form.elements[nama];

  function rapikan() {
    const semua = baris();
    semua.forEach((r, i) => {
      const akhir = i === semua.length - 1;
      const jam = r.querySelector('input[type="time"]');
      const nominal = r.querySelector('input[type="number"]');
      jam.name = `pinalti[${i}][sampai]`;
      nominal.name = `pinalti[${i}][nominal]`;
      jam.hidden = jam.disabled = akhir;
      jam.required = !akhir;
      r.querySelector('[data-awal]').textContent = akhir ? 'Absen setelahnya' : 'Absen sampai';
      r.querySelector('[data-hapus]').hidden = semua.length === 1;
    });
    ringkas();
  }

  function ringkas() {
    const rw = el('reward_sampai').value, tp = el('tepat_sampai').value, nRw = Number(el('reward_nominal').value);
    const semua = baris();
    const baris_ = [[`Sampai ${rw}`, nRw > 0 ? `Reward ${rp(nRw)}` : 'Tepat waktu']];
    baris_.push([`${keJam(keMenit(rw) + 1)} sampai ${tp}`, 'Tepat waktu']);

    const batas = [rw, tp];
    let sebelum = tp;
    semua.forEach((r, i) => {
      const akhir = i === semua.length - 1;
      const t = r.querySelector('input[type="time"]').value;
      const n = r.querySelector('input[type="number"]').value;
      baris_.push([akhir ? `${keJam(keMenit(sebelum) + 1)} dan seterusnya` : `${keJam(keMenit(sebelum) + 1)} sampai ${t}`, `Pinalti ${rp(n)}`]);
      if (!akhir) { batas.push(t); sebelum = t; }
    });

    ringkasanEl.replaceChildren(...baris_.map(([a, b]) => {
      const d = document.createElement('div');
      const s1 = document.createElement('span'); s1.textContent = a;
      const s2 = document.createElement('b'); s2.textContent = b;
      d.append(s1, s2);
      return d;
    }));

    const salah = batas.findIndex((t, i) => !t || (i > 0 && keMenit(t) <= keMenit(batas[i - 1])));
    galat.textContent = salah === -1 ? '' : `Batas jam harus berurutan dari yang paling awal (periksa ${batas[salah] || 'jam yang kosong'}).`;
  }

  document.getElementById('tambah-pinalti').addEventListener('click', () => {
    const semua = baris();
    const terakhir = semua[semua.length - 1];
    const sebelumnya = semua.length > 1 ? semua[semua.length - 2].querySelector('input[type="time"]').value : el('tepat_sampai').value;
    const baru = terakhir.cloneNode(true);
    const jam = baru.querySelector('input[type="time"]');
    jam.value = keJam(keMenit(sebelumnya) + 15);
    baru.querySelector('input[type="number"]').value = 5000;
    wadah.insertBefore(baru, terakhir);
    rapikan();
  });

  wadah.addEventListener('click', (e) => {
    const tombol = e.target.closest('[data-hapus]');
    if (!tombol || baris().length === 1) return;
    tombol.closest('[data-pinalti]').remove();
    rapikan();
  });

  // Geser semua batas jam ketika jam masuk diubah
  jamMasuk.addEventListener('change', () => {
    if (!jamMasuk.value) return;
    const geser = keMenit(jamMasuk.value) - keMenit(jamMasuk.dataset.sebelum);
    jamMasuk.dataset.sebelum = jamMasuk.value;
    form.querySelectorAll('.aturan input[type="time"]').forEach((i) => { if (i.value) i.value = keJam(keMenit(i.value) + geser); });
    ringkas();
  });

  form.addEventListener('input', (e) => { if (e.target.closest('.aturan')) ringkas(); });
  form.addEventListener('submit', (e) => { ringkas(); if (galat.textContent) { e.preventDefault(); galat.scrollIntoView({ block: 'center' }); } });

  // Isi koordinat kantor dari lokasi admin saat ini
  const info = document.getElementById('lokasi-info');
  document.getElementById('pakai-lokasi').addEventListener('click', () => {
    if (!navigator.geolocation) { info.textContent = 'Browser ini tidak mendukung GPS.'; return; }
    info.textContent = 'Mengambil lokasi…';
    navigator.geolocation.getCurrentPosition((p) => {
      el('lat').value = p.coords.latitude.toFixed(7);
      el('lng').value = p.coords.longitude.toFixed(7);
      info.textContent = `Akurasi ±${Math.round(p.coords.accuracy)} m. Simpan untuk menerapkan.`;
    }, () => { info.textContent = 'Izin lokasi ditolak.'; }, { enableHighAccuracy: true, timeout: 15000 });
  });

  rapikan();
})();
