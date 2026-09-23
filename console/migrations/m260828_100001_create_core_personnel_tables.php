<?php

use yii\db\Migration;

/**
 * Class m260828_100001_create_core_personnel_tables
 */
class m260828_100001_create_core_personnel_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // 1. ประเภทบุคลากร
        $this->createTable('{{%personnel_types}}', [
            'id' => $this->primaryKey()->unsigned(),
            'code' => $this->string(30)->notNull()->unique()->comment('CIVIL, UNIVERSITY, GOVT, SPECIAL'),
            'name_th' => $this->string(150)->notNull(),
            'name_en' => $this->string(150)->null(),
            'description' => $this->text()->null(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // 2. ฝ่าย/กลุ่มงาน/หน่วยงาน
        $this->createTable('{{%departments}}', [
            'id' => $this->primaryKey()->unsigned(),
            'parent_id' => $this->integer()->unsigned()->null(),
            'code' => $this->string(50)->null(),
            'name_th' => $this->string(255)->notNull(),
            'name_en' => $this->string(255)->null(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey(
            'fk-departments-parent_id',
            '{{%departments}}',
            'parent_id',
            '{{%departments}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // 3. ตำแหน่ง
        $this->createTable('{{%positions}}', [
            'id' => $this->primaryKey()->unsigned(),
            'code' => $this->string(50)->null(),
            'name_th' => $this->string(255)->notNull(),
            'level_label' => $this->string(100)->null()->comment('ชำนาญการ, ชำนาญการพิเศษ, ฯลฯ'),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // 4. ข้อมูลบุคลากร
        $this->createTable('{{%personnel}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->notNull()->unique(),
            'personnel_type_id' => $this->integer()->unsigned()->notNull(),
            'department_id' => $this->integer()->unsigned()->notNull(),
            'position_id' => $this->integer()->unsigned()->notNull(),
            'supervisor_id' => $this->integer()->unsigned()->null()->comment('ผู้บังคับบัญชาโดยตรง'),
            'employee_code' => $this->string(50)->null()->unique(),
            'citizen_id' => $this->string(20)->null(),
            'prefix_th' => $this->string(30)->null(),
            'first_name_th' => $this->string(100)->notNull(),
            'last_name_th' => $this->string(100)->notNull(),
            'prefix_en' => $this->string(30)->null(),
            'first_name_en' => $this->string(100)->null(),
            'last_name_en' => $this->string(100)->null(),
            'phone' => $this->string(30)->null(),
            'email' => $this->string(150)->null(),
            'salary' => $this->decimal(10, 2)->null(),
            'hire_date' => $this->date()->null(),
            'contract_start_date' => $this->date()->null(),
            'contract_end_date' => $this->date()->null(),
            'is_supervisor' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'status' => $this->tinyInteger()->notNull()->defaultValue(10)->comment('10=Active, 0=Inactive'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-personnel-user_id', '{{%personnel}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-personnel-personnel_type_id', '{{%personnel}}', 'personnel_type_id', '{{%personnel_types}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-personnel-department_id', '{{%personnel}}', 'department_id', '{{%departments}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-personnel-position_id', '{{%personnel}}', 'position_id', '{{%positions}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-personnel-supervisor_id', '{{%personnel}}', 'supervisor_id', '{{%personnel}}', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-personnel-supervisor_id', '{{%personnel}}');
        $this->dropForeignKey('fk-personnel-position_id', '{{%personnel}}');
        $this->dropForeignKey('fk-personnel-department_id', '{{%personnel}}');
        $this->dropForeignKey('fk-personnel-personnel_type_id', '{{%personnel}}');
        $this->dropForeignKey('fk-personnel-user_id', '{{%personnel}}');
        $this->dropTable('{{%personnel}}');

        $this->dropTable('{{%positions}}');

        $this->dropForeignKey('fk-departments-parent_id', '{{%departments}}');
        $this->dropTable('{{%departments}}');

        $this->dropTable('{{%personnel_types}}');
    }
}
