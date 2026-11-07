<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Program donasi dipakai untuk mengelompokkan alokasi dana/barang.
 */
class CreateProgramDonasiTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'target_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive', 'completed'],
                'default'    => 'active',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
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
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('status');
        $this->forge->addKey('deleted_at');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('program_donasi', true);

        $this->db->query(
            'ALTER TABLE program_donasi ADD CONSTRAINT chk_program_donasi_target '
            . 'CHECK (target_amount IS NULL OR target_amount >= 0)'
        );

        $this->db->query(
            'ALTER TABLE program_donasi ADD CONSTRAINT chk_program_donasi_period '
            . 'CHECK (start_date IS NULL OR end_date IS NULL OR end_date >= start_date)'
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('program_donasi', true);
    }
}
