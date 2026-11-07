<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penerimaan donasi uang dan barang.
 *
 * - donation_type membedakan uang/barang.
 * - is_anonymous memungkinkan identitas donatur disembunyikan pada transaksi.
 * - donor_id boleh NULL untuk donasi anonim tanpa data donatur.
 * - Nominal memakai DECIMAL, bukan FLOAT.
 */
class CreateDonasiTable extends Migration
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
            'transaction_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'donor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'is_anonymous' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'program_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'donation_type' => [
                'type'       => 'ENUM',
                'constraint' => ['money', 'goods'],
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'verified', 'cancelled'],
                'default'    => 'pending',
            ],
            'donation_date' => [
                'type' => 'DATE',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
            ],
            'payment_method' => [
                'type'       => 'ENUM',
                'constraint' => ['cash', 'bank_transfer', 'other'],
                'null'       => true,
            ],
            'reference_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'goods_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'goods_quantity' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'goods_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'estimated_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'verified_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'cancelled_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('transaction_number');
        $this->forge->addKey('donor_id');
        $this->forge->addKey('program_id');
        $this->forge->addKey('donation_type');
        $this->forge->addKey('status');
        $this->forge->addKey('donation_date');
        $this->forge->addKey('deleted_at');

        $this->forge->addForeignKey('donor_id', 'donatur', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('program_id', 'program_donasi', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('verified_by', 'users', 'id', 'CASCADE', 'SET NULL');

        $this->forge->createTable('donasi', true);

        $this->db->query(
            'ALTER TABLE donasi ADD CONSTRAINT chk_donasi_amount '
            . 'CHECK (amount IS NULL OR amount > 0)'
        );

        $this->db->query(
            'ALTER TABLE donasi ADD CONSTRAINT chk_donasi_goods_quantity '
            . 'CHECK (goods_quantity IS NULL OR goods_quantity > 0)'
        );

        $this->db->query(
            'ALTER TABLE donasi ADD CONSTRAINT chk_donasi_estimated_value '
            . 'CHECK (estimated_value IS NULL OR estimated_value >= 0)'
        );

        $this->db->query(
            'ALTER TABLE donasi ADD CONSTRAINT chk_donasi_type_fields CHECK ('
            . "(donation_type = 'money' AND amount IS NOT NULL AND goods_name IS NULL "
            . 'AND goods_quantity IS NULL AND goods_unit IS NULL) '
            . 'OR '
            . "(donation_type = 'goods' AND amount IS NULL AND goods_name IS NOT NULL "
            . 'AND goods_quantity IS NOT NULL AND goods_unit IS NOT NULL)'
            . ')'
        );

        $this->db->query(
            'ALTER TABLE donasi ADD CONSTRAINT chk_donasi_anonymous CHECK ('
            . 'is_anonymous IN (0, 1) AND (is_anonymous = 1 OR donor_id IS NOT NULL)'
            . ')'
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('donasi', true);
    }
}
