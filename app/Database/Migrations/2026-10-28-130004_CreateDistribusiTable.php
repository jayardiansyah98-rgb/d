<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Distribusi donasi kepada penerima manfaat.
 *
 * Distribusi uang memakai kolom amount; distribusi barang memakai
 * baris detail_distribusi (jenis barang, kuantitas, satuan).
 */
class CreateDistribusiTable extends Migration
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
            'distribution_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'program_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'donation_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'distribution_type' => [
                'type'       => 'ENUM',
                'constraint' => ['money', 'goods'],
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'approved', 'distributed', 'cancelled'],
                'default'    => 'draft',
            ],
            'distribution_date' => [
                'type' => 'DATE',
            ],
            'recipient_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'recipient_type' => [
                'type'       => 'ENUM',
                'constraint' => ['individual', 'organization', 'community'],
                'default'    => 'individual',
            ],
            'recipient_address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'distributed_at' => [
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
        $this->forge->addUniqueKey('distribution_number');
        $this->forge->addKey('program_id');
        $this->forge->addKey('donation_id');
        $this->forge->addKey('distribution_type');
        $this->forge->addKey('status');
        $this->forge->addKey('distribution_date');
        $this->forge->addKey('deleted_at');

        $this->forge->addForeignKey('program_id', 'program_donasi', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('donation_id', 'donasi', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'CASCADE', 'SET NULL');

        $this->forge->createTable('distribusi', true);

        $this->db->query(
            'ALTER TABLE distribusi ADD CONSTRAINT chk_distribusi_amount '
            . 'CHECK (amount IS NULL OR amount > 0)'
        );

        $this->db->query(
            'ALTER TABLE distribusi ADD CONSTRAINT chk_distribusi_type_fields CHECK ('
            . "(distribution_type = 'money' AND amount IS NOT NULL) "
            . 'OR '
            . "(distribution_type = 'goods' AND amount IS NULL)"
            . ')'
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('distribusi', true);
    }
}
