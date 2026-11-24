-- ============================================================
-- SITAS - Skema Database (MySQL 8 / MariaDB 10.5+, InnoDB, utf8mb4)
-- Cara pakai: buka phpMyAdmin > tab Import > pilih file ini.
-- ============================================================

CREATE DATABASE IF NOT EXISTS sitas_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sitas_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_log, notifikasi, master_template, master_aturan, master_periode,
  master_ruang, ganti_pembimbing, surat, naskah_final, sidang_nilai, sidang, bab_diskusi,
  bab_dokumen, logbook, judul_skripsi, user_roles, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Pengguna dan peran (FR-01, FR-02, FR-27) ----------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nim_nidn      VARCHAR(30)  NULL,
  no_wa         VARCHAR(20)  NULL,
  aktif         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id INT UNSIGNED NOT NULL,
  role    ENUM('mahasiswa','dosen','admin') NOT NULL,
  PRIMARY KEY (user_id, role),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Pengajuan judul (FR-06, FR-19) ----------
CREATE TABLE judul_skripsi (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mahasiswa_id     INT UNSIGNED NOT NULL,
  judul            VARCHAR(255) NOT NULL,
  abstrak          TEXT NULL,
  bidang_minat     VARCHAR(100) NULL,
  proposal_path    VARCHAR(255) NULL,
  kemiripan        DECIMAL(5,2) NOT NULL DEFAULT 0,
  status           ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  pembimbing_id    INT UNSIGNED NULL,
  alasan_penolakan TEXT NULL,
  diajukan_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diputuskan_at    DATETIME NULL,
  FOREIGN KEY (mahasiswa_id)  REFERENCES users(id),
  FOREIGN KEY (pembimbing_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- Bimbingan (FR-07, FR-08, FR-14, FR-15) ----------
CREATE TABLE logbook (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul_id     INT UNSIGNED NOT NULL,
  tanggal      DATE NOT NULL,
  topik        VARCHAR(200) NOT NULL,
  uraian       TEXT NULL,
  status       ENUM('menunggu_dosen','tervalidasi','perlu_revisi') NOT NULL DEFAULT 'menunggu_dosen',
  catatan_dosen TEXT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  validated_at DATETIME NULL,
  FOREIGN KEY (judul_id) REFERENCES judul_skripsi(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bab_dokumen (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul_id   INT UNSIGNED NOT NULL,
  bab        TINYINT UNSIGNED NOT NULL,           -- 1 sampai 5
  versi      INT UNSIGNED NOT NULL DEFAULT 1,
  file_path  VARCHAR(255) NOT NULL,
  status     ENUM('menunggu_dosen','disetujui','perlu_revisi') NOT NULL DEFAULT 'menunggu_dosen',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (judul_id) REFERENCES judul_skripsi(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bab_diskusi (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul_id   INT UNSIGNED NOT NULL,
  bab        TINYINT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  pesan      TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (judul_id) REFERENCES judul_skripsi(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- Master (FR-28) ----------
CREATE TABLE master_ruang (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama  VARCHAR(80) NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE master_periode (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  tgl_mulai   DATE NOT NULL,
  tgl_selesai DATE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE master_aturan (
  kunci      VARCHAR(50) PRIMARY KEY,
  nilai      VARCHAR(100) NOT NULL,
  keterangan VARCHAR(200) NULL
) ENGINE=InnoDB;

CREATE TABLE master_template (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama      VARCHAR(100) NOT NULL,
  file_path VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ---------- Sidang (FR-09, FR-17, FR-21 sampai FR-23) ----------
CREATE TABLE sidang (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul_id            INT UNSIGNED NOT NULL,
  jenis               ENUM('proposal','akhir') NOT NULL DEFAULT 'proposal',
  persetujuan_dosen   TINYINT(1) NOT NULL DEFAULT 0,      -- FR-16
  status              ENUM('didaftarkan','berkas_dikembalikan','berkas_lengkap','terjadwal','dinilai')
                      NOT NULL DEFAULT 'didaftarkan',
  chk_transkrip       TINYINT(1) NOT NULL DEFAULT 0,
  chk_surat_penelitian TINYINT(1) NOT NULL DEFAULT 0,
  chk_naskah          TINYINT(1) NOT NULL DEFAULT 0,
  chk_formulir        TINYINT(1) NOT NULL DEFAULT 0,
  chk_pinjaman        TINYINT(1) NOT NULL DEFAULT 0,      -- bebas pustaka
  chk_denda           TINYINT(1) NOT NULL DEFAULT 0,
  chk_survei          TINYINT(1) NOT NULL DEFAULT 0,
  alasan_kembali      TEXT NULL,
  penguji_id          INT UNSIGNED NULL,
  ruang_id            INT UNSIGNED NULL,
  jadwal              DATETIME NULL,
  revisi_status       ENUM('belum','menunggu_mahasiswa','perlu_diperiksa','disetujui')
                      NOT NULL DEFAULT 'belum',
  revisi_path         VARCHAR(255) NULL,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (judul_id)   REFERENCES judul_skripsi(id) ON DELETE CASCADE,
  FOREIGN KEY (penguji_id) REFERENCES users(id),
  FOREIGN KEY (ruang_id)   REFERENCES master_ruang(id)
) ENGINE=InnoDB;

CREATE TABLE sidang_nilai (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sidang_id        INT UNSIGNED NOT NULL UNIQUE,
  penguji_id       INT UNSIGNED NOT NULL,
  nilai_materi     DECIMAL(5,2) NOT NULL,   -- bobot 30%
  nilai_metodologi DECIMAL(5,2) NOT NULL,   -- bobot 30%
  nilai_penulisan  DECIMAL(5,2) NOT NULL,   -- bobot 20%
  nilai_presentasi DECIMAL(5,2) NOT NULL,   -- bobot 20%
  nilai_akhir      DECIMAL(5,2) NOT NULL,
  catatan_revisi   TEXT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sidang_id)  REFERENCES sidang(id) ON DELETE CASCADE,
  FOREIGN KEY (penguji_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- Pasca-sidang (FR-12, FR-25) ----------
CREATE TABLE naskah_final (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul_id      INT UNSIGNED NOT NULL,
  file_path     VARCHAR(255) NOT NULL,
  status        ENUM('terkirim_admin','diarsipkan') NOT NULL DEFAULT 'terkirim_admin',
  akses         ENUM('publik','internal','ditunda') NULL,
  diarsipkan_at DATETIME NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (judul_id) REFERENCES judul_skripsi(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Surat (FR-10, FR-20, FR-24) ----------
CREATE TABLE surat (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jenis          ENUM('sk_pembimbing','izin_penelitian','keterangan_aktif','undangan_sidang') NOT NULL,
  judul_id       INT UNSIGNED NULL,
  pemohon_id     INT UNSIGNED NOT NULL,
  status         ENUM('menunggu','terbit','ditolak') NOT NULL DEFAULT 'menunggu',
  nomor_urut     INT UNSIGNED NULL,
  tahun          SMALLINT UNSIGNED NULL,
  nomor          VARCHAR(50) NULL,                 -- format nomor/SITAS/tahun (BR-12)
  qr_token       VARCHAR(64) NULL,
  diterbitkan_at DATETIME NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (judul_id)   REFERENCES judul_skripsi(id) ON DELETE SET NULL,
  FOREIGN KEY (pemohon_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE ganti_pembimbing (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul_id           INT UNSIGNED NOT NULL,
  alasan             TEXT NOT NULL,
  status             ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  pembimbing_baru_id INT UNSIGNED NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (judul_id)           REFERENCES judul_skripsi(id) ON DELETE CASCADE,
  FOREIGN KEY (pembimbing_baru_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- Notifikasi dan audit (FR-30, FR-31) ----------
CREATE TABLE notifikasi (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  pesan      VARCHAR(255) NOT NULL,
  tautan     VARCHAR(255) NULL,
  dibaca     TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  waktu      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id    INT UNSIGNED NULL,
  aksi       VARCHAR(60) NOT NULL,
  keterangan VARCHAR(255) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
