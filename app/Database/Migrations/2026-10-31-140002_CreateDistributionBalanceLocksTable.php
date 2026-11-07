<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel kunci saldo distribusi.
 *
 * Satu baris per kunci saldo:
 * - `money` untuk saldo dana global (program_id NULL).
 * - `goods:<nama>|<satuan>` untuk saldo barang per nama + satuan ternormalisasi.
 *
 * Service distribusi mengunci baris ini dengan SELECT ... FOR UPDATE di dalam
 * transaksi sebelum menghitung saldo, sehingga dua permintaan bersamaan tidak
 * dapat memakai saldo yang sama. Tabel ini hanya berisi kunci, bukan saldo;
 * saldo selalu dihitung dari tabel donasi, distribusi, dan detail_distribusi.
 */
class CreateDistributionBalanceLocksTable extends Migration
{
    public function up(): void
    {
        $this->db->resetDataCache();

        if ($this->db->tableExists('distribution_balance_locks')) {
            return;
        }

        $this->forge->addField([
            'balance_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('balance_key', true);
        $this->forge->createTable('distribution_balance_locks', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('distribution_balance_locks', true);
    }
}
