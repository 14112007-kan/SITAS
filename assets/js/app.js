// Interaksi dasar: buka/tutup sidebar di ponsel dan dialog konfirmasi keluar.
(function () {
  var sidebar = document.getElementById('sidebar');
  var btnMenu = document.getElementById('btnMenu');
  if (btnMenu && sidebar) {
    btnMenu.addEventListener('click', function () {
      sidebar.classList.toggle('terbuka');
    });
  }

  var dialog = document.getElementById('dialogKeluar');
  var btnKeluar = document.getElementById('btnKeluar');
  var btnBatal = document.getElementById('btnBatalKeluar');
  if (dialog && btnKeluar) {
    btnKeluar.addEventListener('click', function () { dialog.showModal(); });
  }
  if (dialog && btnBatal) {
    btnBatal.addEventListener('click', function () { dialog.close(); });
  }
})();
