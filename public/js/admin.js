// Halaman admin: minta konfirmasi sebelum mengirim form berbahaya (data-konfirmasi).
(() => {
  'use strict';

  document.querySelectorAll('form[data-konfirmasi]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (!confirm(form.dataset.konfirmasi)) e.preventDefault();
    });
  });
})();
