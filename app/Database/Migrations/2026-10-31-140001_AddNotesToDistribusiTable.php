<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan kolom keterangan pada distribusi.
 *
 * Kolom description dipakai untuk tujuan penyaluran, sedangkan notes
 * menyimpan keterangan tambahan. Migration ini tidak mengubah migration
 * tahap 2 yang sudah berjalan.
 */
class AddNotesToDistribusiTable extends Migration
{
    public function up(): void
    {
        // Migration berjalan pada koneksi yang sama dengan migration lain;
        // cache skema harus dibersihkan agar fieldExists() membaca kondisi
        // tabel yang baru dibuat ulang (mis. saat refresh database test).
        $this->db->resetDataCache();

        if ($this->db->fieldExists('notes', 'distribusi')) {
            return;
        }

        $this->forge->addColumn('distribusi', [
            'notes' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'description',
            ],
        ]);
    }

    public function down(): void
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('notes', 'distribusi')) {
            $this->forge->dropColumn('distribusi', 'notes');
        }
    }
}
