// Halaman beranda karyawan: jam, cek lokasi, kamera, dan kirim absen.
(() => {
  'use strict';

  const jamEl = document.getElementById('jam');
  const formatJam = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Makassar', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
  if (jamEl) setInterval(() => { jamEl.textContent = formatJam.format(new Date()).replace('.', ':'); }, 10000);

  const wadah = document.getElementById('absen');
  if (!wadah) return;

  const kantor = { lat: Number(wadah.dataset.lat), lng: Number(wadah.dataset.lng), radius: Number(wadah.dataset.radius) };
  const csrf = document.querySelector('meta[name="csrf-token"]').content;

  const jarakMeter = (lat1, lon1, lat2, lon2) => {
    const R = 6371000, t = Math.PI / 180, dLat = (lat2 - lat1) * t, dLon = (lon2 - lon1) * t;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * t) * Math.cos(lat2 * t) * Math.sin(dLon / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
  };

  const posisi = () => new Promise((ok, gagal) => {
    if (!navigator.geolocation) return gagal(new Error('Browser ini tidak mendukung GPS.'));
    navigator.geolocation.getCurrentPosition(ok,
      () => gagal(new Error('Izin lokasi diperlukan. Aktifkan GPS dan izinkan lokasi di browser.')),
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
  });

  // Info radius (hanya petunjuk; keputusan akhir tetap di server)
  const info = document.getElementById('lokasi');
  posisi().then((p) => {
    const j = Math.round(jarakMeter(p.coords.latitude, p.coords.longitude, kantor.lat, kantor.lng));
    const dalam = j <= kantor.radius;
    info.textContent = dalam ? `Dalam radius kantor (${j} m)` : `Di luar radius kantor (${j} m, batas ${kantor.radius} m)`;
    info.className = 'lokasi ' + (dalam ? 'baik' : 'waspada');
  }).catch((e) => { info.textContent = e.message; info.className = 'lokasi buruk'; });

  // Kamera
  const dlg = document.getElementById('kamera');
  const video = document.getElementById('video');
  const pesan = document.getElementById('kamera-pesan');
  const kirim = document.getElementById('kirim');
  let stream = null;

  const matikan = () => { if (stream) stream.getTracks().forEach((t) => t.stop()); stream = null; };
  dlg.addEventListener('close', matikan);
  document.getElementById('batal').addEventListener('click', () => dlg.close());

  document.getElementById('tombol-absen').addEventListener('click', async () => {
    pesan.textContent = '';
    kirim.disabled = false;
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 } }, audio: false });
    } catch {
      alert('Izin kamera diperlukan. Izinkan kamera di browser, lalu coba lagi.');
      return;
    }
    video.srcObject = stream;
    dlg.showModal();
  });

  kirim.addEventListener('click', async () => {
    kirim.disabled = true;
    pesan.className = 'pesan mut';
    pesan.textContent = 'Mengambil lokasi…';
    try {
      const p = await posisi();
      const w = video.videoWidth || 640, h = video.videoHeight || 480;
      const kanvas = document.createElement('canvas');
      kanvas.width = Math.min(w, 640);
      kanvas.height = Math.round((kanvas.width * h) / w);
      kanvas.getContext('2d').drawImage(video, 0, 0, kanvas.width, kanvas.height);

      pesan.textContent = 'Mengirim…';
      const res = await fetch(wadah.dataset.kirim, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({
          foto: kanvas.toDataURL('image/jpeg', 0.7),
          lat: p.coords.latitude,
          lng: p.coords.longitude,
          akurasi: p.coords.accuracy,
        }),
      });
      if (res.status === 401 || res.status === 419) { location.reload(); return; }
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.error || data.message || 'Absen gagal dikirim. Coba lagi.');
      dlg.close();
      location.reload();
    } catch (e) {
      pesan.className = 'pesan buruk';
      pesan.textContent = e.message;
      kirim.disabled = false;
    }
  });
})();
