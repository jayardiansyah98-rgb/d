<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rincian barang pada distribusi.
 *
 * Satu distribusi barang dapat memiliki beberapa baris item;
 * setiap baris wajib memiliki nama/jenis barang, kuantitas, dan satuan.
 */
class CreateDetailDistribusiTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'distribution_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'item_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'quantity' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'unit_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('distribution_id');
        $this->forge->addForeignKey('distribution_id', 'distribusi', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('detail_distribusi', true);

        $this->db->query(
            'ALTER TABLE detail_distribusi ADD CONSTRAINT chk_detail_distribusi_quantity '
            . 'CHECK (quantity > 0)'
        );

        $this->db->query(
            'ALTER TABLE detail_distribusi ADD CONSTRAINT chk_detail_distribusi_unit_value '
            . 'CHECK (unit_value IS NULL OR unit_value >= 0)'
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('detail_distribusi', true);
    }
}
