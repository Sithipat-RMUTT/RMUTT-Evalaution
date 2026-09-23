<?php

use yii\db\Migration;

/**
 * Class m260828_100003_create_evaluation_cycle_and_records
 */
class m260828_100003_create_evaluation_cycle_and_records extends Migration
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

        // 1. รอบการประเมิน
        $this->createTable('{{%evaluation_cycles}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name_th' => $this->string(255)->notNull(),
            'cycle_number' => $this->tinyInteger()->notNull()->comment('1 หรือ 2'),
            'fiscal_year' => $this->integer()->notNull()->comment('พ.ศ. เช่น 2569'),
            'period_start' => $this->date()->notNull(),
            'period_end' => $this->date()->notNull(),
            'self_assessment_start' => $this->dateTime()->notNull(),
            'self_assessment_end' => $this->dateTime()->notNull(),
            'supervisor_eval_start' => $this->dateTime()->notNull(),
            'supervisor_eval_end' => $this->dateTime()->notNull(),
            'status' => $this->string(30)->notNull()->defaultValue('draft')->comment('draft, active, evaluation, closed, archived'),
            'description' => $this->text()->null(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-eval_cycles-created_by', '{{%evaluation_cycles}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');

        // 2. Cycle Template Mapping
        $this->createTable('{{%cycle_template_mappings}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_cycle_id' => $this->integer()->unsigned()->notNull(),
            'personnel_type_id' => $this->integer()->unsigned()->notNull(),
            'template_version_id' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-cycle_template_mapping-unique', '{{%cycle_template_mappings}}', ['evaluation_cycle_id', 'personnel_type_id'], true);
        $this->addForeignKey('fk-ctm-cycle_id', '{{%cycle_template_mappings}}', 'evaluation_cycle_id', '{{%evaluation_cycles}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-ctm-personnel_type_id', '{{%cycle_template_mappings}}', 'personnel_type_id', '{{%personnel_types}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-ctm-template_version_id', '{{%cycle_template_mappings}}', 'template_version_id', '{{%template_versions}}', 'id', 'RESTRICT', 'CASCADE');

        // 3. ใบประเมิน (Evaluations)
        $this->createTable('{{%evaluations}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_cycle_id' => $this->integer()->unsigned()->notNull(),
            'personnel_id' => $this->integer()->unsigned()->notNull(),
            'template_version_id' => $this->integer()->unsigned()->notNull(),
            'evaluator_id' => $this->integer()->unsigned()->null()->comment('ผู้ประเมินตามสายงาน'),
            'status' => $this->string(40)->notNull()->defaultValue('draft')->comment('draft, self_assessment, submitted, supervisor_review, evaluated, completed, returned'),
            'self_submitted_at' => $this->dateTime()->null(),
            'supervisor_evaluated_at' => $this->dateTime()->null(),
            'completed_at' => $this->dateTime()->null(),
            'returned_at' => $this->dateTime()->null(),
            'return_reason' => $this->text()->null(),
            'supervisor_comment_strength' => $this->text()->null()->comment('จุดเด่น'),
            'supervisor_comment_improvement' => $this->text()->null()->comment('จุดที่ควรปรับปรุง/แก้ไข'),
            'supervisor_comment_suggestion' => $this->text()->null()->comment('ข้อเสนอแนะการพัฒนา'),
            'employment_recommendation' => $this->string(50)->null()->comment('continue, continue_with_improvement, terminate (สำหรับพนักงานพิเศษ)'),
            'acknowledgement_at' => $this->dateTime()->null()->comment('วันที่ผู้รับการประเมินรับทราบผล'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-evaluations-unique', '{{%evaluations}}', ['evaluation_cycle_id', 'personnel_id'], true);
        $this->addForeignKey('fk-evaluations-cycle_id', '{{%evaluations}}', 'evaluation_cycle_id', '{{%evaluation_cycles}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-evaluations-personnel_id', '{{%evaluations}}', 'personnel_id', '{{%personnel}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-evaluations-template_version_id', '{{%evaluations}}', 'template_version_id', '{{%template_versions}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-evaluations-evaluator_id', '{{%evaluations}}', 'evaluator_id', '{{%personnel}}', 'id', 'SET NULL', 'CASCADE');

        // 4. คำตอบ/รายการที่กรอกในแบบประเมิน (Evaluation Answers)
        $this->createTable('{{%evaluation_answers}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_id' => $this->integer()->unsigned()->notNull(),
            'evaluation_item_id' => $this->integer()->unsigned()->notNull(),
            'answered_by' => $this->string(20)->notNull()->defaultValue('self')->comment('self, supervisor'),
            'text_value' => $this->text()->null(),
            'numeric_value' => $this->decimal(8, 2)->null(),
            'weight_value' => $this->decimal(5, 2)->null(),
            'json_value' => $this->json()->null()->comment('Selected checkboxes, item lists, details'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-eval_answers-unique', '{{%evaluation_answers}}', ['evaluation_id', 'evaluation_item_id', 'answered_by'], true);
        $this->addForeignKey('fk-eval_answers-evaluation_id', '{{%evaluation_answers}}', 'evaluation_id', '{{%evaluations}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-eval_answers-item_id', '{{%evaluation_answers}}', 'evaluation_item_id', '{{%evaluation_items}}', 'id', 'CASCADE', 'CASCADE');

        // 5. คำตอบสมรรถนะ (Competency Answers)
        $this->createTable('{{%evaluation_competency_answers}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_id' => $this->integer()->unsigned()->notNull(),
            'competency_definition_id' => $this->integer()->unsigned()->notNull(),
            'answered_by' => $this->string(20)->notNull()->comment('self, supervisor'),
            'level_value' => $this->tinyInteger()->notNull()->comment('1 to 5'),
            'gap_summary' => $this->text()->null(),
            'importance' => $this->string(20)->null()->comment('high, medium, low'),
            'idp_plan' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-eval_comp_answers-unique', '{{%evaluation_competency_answers}}', ['evaluation_id', 'competency_definition_id', 'answered_by'], true);
        $this->addForeignKey('fk-eca-evaluation_id', '{{%evaluation_competency_answers}}', 'evaluation_id', '{{%evaluations}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-eca-comp_def_id', '{{%evaluation_competency_answers}}', 'competency_definition_id', '{{%competency_definitions}}', 'id', 'CASCADE', 'CASCADE');

        // 6. ผลสรุปการคำนวณคะแนน (Evaluation Results)
        $this->createTable('{{%evaluation_results}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_id' => $this->integer()->unsigned()->notNull()->unique(),
            'template_version_id' => $this->integer()->unsigned()->notNull(),
            'self_performance_score' => $this->decimal(8, 4)->null(),
            'self_competency_score' => $this->decimal(8, 4)->null(),
            'self_total_score' => $this->decimal(8, 4)->null(),
            'supervisor_performance_score' => $this->decimal(8, 4)->null(),
            'supervisor_competency_score' => $this->decimal(8, 4)->null(),
            'supervisor_total_score' => $this->decimal(8, 4)->null(),
            'final_score' => $this->decimal(8, 4)->notNull(),
            'final_percentage' => $this->decimal(6, 2)->notNull(),
            'performance_level' => $this->string(50)->notNull()->comment('ดีเด่น, ดีมาก, ดี, พอใช้, ต้องปรับปรุง'),
            'calculation_details' => $this->json()->notNull(),
            'calculated_by' => $this->integer()->notNull(),
            'calculated_at' => $this->dateTime()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-eval_results-evaluation_id', '{{%evaluation_results}}', 'evaluation_id', '{{%evaluations}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-eval_results-template_version_id', '{{%evaluation_results}}', 'template_version_id', '{{%template_versions}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk-eval_results-calculated_by', '{{%evaluation_results}}', 'calculated_by', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');

        // 7. ไฟล์หลักฐานแนบ (Evidence Files)
        $this->createTable('{{%evidence_files}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_id' => $this->integer()->unsigned()->notNull(),
            'evaluation_item_id' => $this->integer()->unsigned()->null(),
            'original_name' => $this->string(255)->notNull(),
            'stored_name' => $this->string(255)->notNull(),
            'file_path' => $this->string(500)->notNull(),
            'file_size' => $this->integer()->unsigned()->notNull(),
            'file_type' => $this->string(100)->notNull(),
            'file_hash' => $this->string(64)->notNull(),
            'uploaded_by' => $this->integer()->notNull(),
            'deleted_at' => $this->dateTime()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-evidence_files-evaluation_id', '{{%evidence_files}}', 'evaluation_id', '{{%evaluations}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-evidence_files-item_id', '{{%evidence_files}}', 'evaluation_item_id', '{{%evaluation_items}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-evidence_files-uploaded_by', '{{%evidence_files}}', 'uploaded_by', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');

        // 8. Audit Logs
        $this->createTable('{{%audit_logs}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->null(),
            'action' => $this->string(100)->notNull(),
            'entity_type' => $this->string(100)->null(),
            'entity_id' => $this->integer()->unsigned()->null(),
            'old_values' => $this->json()->null(),
            'new_values' => $this->json()->null(),
            'ip_address' => $this->string(45)->null(),
            'user_agent' => $this->string(500)->null(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-audit_logs-user_id', '{{%audit_logs}}', 'user_id');
        $this->createIndex('idx-audit_logs-entity', '{{%audit_logs}}', ['entity_type', 'entity_id']);
        $this->createIndex('idx-audit_logs-action', '{{%audit_logs}}', 'action');
        $this->createIndex('idx-audit_logs-created_at', '{{%audit_logs}}', 'created_at');

        // 9. การแจ้งเตือน (Notifications)
        $this->createTable('{{%notifications}}', [
            'id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->notNull(),
            'type' => $this->string(50)->notNull(),
            'title' => $this->string(255)->notNull(),
            'message' => $this->text()->notNull(),
            'related_id' => $this->integer()->unsigned()->null(),
            'related_type' => $this->string(50)->null(),
            'is_read' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'read_at' => $this->dateTime()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-notifications-user_unread', '{{%notifications}}', ['user_id', 'is_read']);
        $this->addForeignKey('fk-notifications-user_id', '{{%notifications}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-notifications-user_id', '{{%notifications}}');
        $this->dropTable('{{%notifications}}');

        $this->dropTable('{{%audit_logs}}');

        $this->dropForeignKey('fk-evidence_files-uploaded_by', '{{%evidence_files}}');
        $this->dropForeignKey('fk-evidence_files-item_id', '{{%evidence_files}}');
        $this->dropForeignKey('fk-evidence_files-evaluation_id', '{{%evidence_files}}');
        $this->dropTable('{{%evidence_files}}');

        $this->dropForeignKey('fk-eval_results-calculated_by', '{{%evaluation_results}}');
        $this->dropForeignKey('fk-eval_results-template_version_id', '{{%evaluation_results}}');
        $this->dropForeignKey('fk-eval_results-evaluation_id', '{{%evaluation_results}}');
        $this->dropTable('{{%evaluation_results}}');

        $this->dropForeignKey('fk-eca-comp_def_id', '{{%evaluation_competency_answers}}');
        $this->dropForeignKey('fk-eca-evaluation_id', '{{%evaluation_competency_answers}}');
        $this->dropTable('{{%evaluation_competency_answers}}');

        $this->dropForeignKey('fk-eval_answers-item_id', '{{%evaluation_answers}}');
        $this->dropForeignKey('fk-eval_answers-evaluation_id', '{{%evaluation_answers}}');
        $this->dropTable('{{%evaluation_answers}}');

        $this->dropForeignKey('fk-evaluations-evaluator_id', '{{%evaluations}}');
        $this->dropForeignKey('fk-evaluations-template_version_id', '{{%evaluations}}');
        $this->dropForeignKey('fk-evaluations-personnel_id', '{{%evaluations}}');
        $this->dropForeignKey('fk-evaluations-cycle_id', '{{%evaluations}}');
        $this->dropTable('{{%evaluations}}');

        $this->dropForeignKey('fk-ctm-template_version_id', '{{%cycle_template_mappings}}');
        $this->dropForeignKey('fk-ctm-personnel_type_id', '{{%cycle_template_mappings}}');
        $this->dropForeignKey('fk-ctm-cycle_id', '{{%cycle_template_mappings}}');
        $this->dropTable('{{%cycle_template_mappings}}');

        $this->dropForeignKey('fk-eval_cycles-created_by', '{{%evaluation_cycles}}');
        $this->dropTable('{{%evaluation_cycles}}');
    }
}
