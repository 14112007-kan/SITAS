# SITAS - Sistem Informasi Tugas Akhir dan Skripsi

Stack: HTML, CSS, JavaScript, PHP (PDO), MySQL. Dijalankan dengan XAMPP.
Status: **Tahap 1 selesai** (fondasi, login, peran, dashboard).

## Cara menjalankan (Tahap 1)

1. Pasang **XAMPP** (PHP 8.0 atau lebih baru) dan **VS Code**.
2. Salin folder `sitas` ke `C:\xampp\htdocs\sitas`.
3. Buka XAMPP Control Panel, tekan **Start** pada **Apache** dan **MySQL**.
4. Buka http://localhost/phpmyadmin, tab **Import**, pilih `database/schema.sql`, klik **Import**.
5. Buka http://localhost/sitas/database/seed.php (sekali saja). Muncul "Seeder selesai".
6. Buka http://localhost/sitas/ lalu masuk dengan akun demo (kata sandi semua: `sitas123`).

| Peran            | Email                |
|------------------|----------------------|
| Mahasiswa        | aisyah@sitas.test    |
| Dosen Pembimbing | siti@sitas.test      |
| Admin            | admin@sitas.test     |
| Dosen + Admin    | kaprodi@sitas.test   |

Kalau folder diberi nama lain, ubah `BASE_URL` di `config/config.php`.

## Struktur folder

```
sitas/
├── index.php            pintu masuk, mengarahkan ke login atau dashboard
├── login.php            halaman login (FR-01)
├── logout.php           proses keluar (FR-03)
├── pilih-peran.php      "Masuk sebagai" untuk akun multi-peran (FR-02)
├── config/              pengaturan aplikasi dan koneksi database
├── includes/            kode bersama: auth, helpers, menu, header, footer
├── mahasiswa/           halaman peran Mahasiswa
├── dosen/               halaman peran Dosen Pembimbing
├── admin/               halaman peran Admin
├── assets/css, js/      tampilan dan interaksi
├── database/            schema.sql dan seed.php
└── storage/             berkas unggahan (diblokir dari akses langsung)
```

## Peta tahapan

| Tahap | Isi | Fitur PRD |
|-------|-----|-----------|
| 1 | Fondasi: setup, database, login, peran, dashboard | FR-01 sampai 03, 05, 13, 33 |
| 2 | Pengajuan judul, cek kemiripan, verifikasi Admin, kuota pembimbing | FR-06, 19 |
| 3 | Bimbingan: logbook, dokumen bab, validasi dan review dosen | FR-07, 08, 14, 15 |
| 4 | Persetujuan maju sidang, pendaftaran, berkas, bebas pustaka | FR-09, 16, 21, 22 |
| 5 | Penguji, jadwal tanpa bentrok, penilaian rubrik | FR-17, 23 |
| 6 | Revisi pasca-sidang, naskah final, arsip repositori | FR-11, 12, 18, 25 |
| 7 | Surat: antrean, nomor otomatis, PDF ber-QR | FR-10, 20, 24 |
| 8 | Admin: pengguna, peran, data master, audit log | FR-27, 28, 30 |
| 9 | Rekap, peringatan otomatis, matriks hak akses | FR-26, 29, 32 |
| 10 | Finalisasi: pengujian, rapikan, skenario demo | semua |

Setelah satu tahap selesai, naikkan `TAHAP_SELESAI` di `config/config.php`
agar menu tahap itu aktif di sidebar.

## Daftar uji Tahap 1

- [ ] Login dengan isian kosong menampilkan pesan galat.
- [ ] Email tidak terdaftar menampilkan "Akun tidak ditemukan."
- [ ] Kata sandi salah menampilkan "Email atau kata sandi salah."
- [ ] aisyah@sitas.test masuk ke dashboard Mahasiswa (bimbingan 3 / 8).
- [ ] siti@sitas.test masuk ke dashboard Dosen (kuota 1 / 10).
- [ ] admin@sitas.test masuk ke dashboard Admin (1 judul menunggu).
- [ ] kaprodi@sitas.test diminta memilih peran, lalu menu menyesuaikan.
- [ ] Tombol Keluar menampilkan dialog konfirmasi; Batal menutup dialog.
- [ ] Mahasiswa membuka http://localhost/sitas/admin/dashboard.php: ditolak (403).
- [ ] Tampilan rapi di ponsel (perkecil jendela peramban).

## Masalah umum

- **Halaman putih atau error 500**: aktifkan tampilan galat PHP di `C:\xampp\php\php.ini`
  (`display_errors=On`), restart Apache.
- **"Database tidak dapat dihubungi"**: pastikan MySQL menyala dan schema.sql sudah diimport.
- **Halaman tidak ketemu (404)**: cek nama folder dan `BASE_URL`.
