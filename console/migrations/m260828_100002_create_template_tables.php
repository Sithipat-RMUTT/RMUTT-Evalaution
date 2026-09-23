<?php

use yii\db\Migration;

/**
 * Class m260828_100002_create_template_tables
 */
class m260828_100002_create_template_tables extends Migration
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

        // 1. แม่แบบแบบประเมิน
        $this->createTable('{{%evaluation_templates}}', [
            'id' => $this->primaryKey()->unsigned(),
            'personnel_type_id' => $this->integer()->unsigned()->notNull(),
            'code' => $this->string(50)->notNull()->unique(),
            'name_th' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-eval_templates-personnel_type_id', '{{%evaluation_templates}}', 'personnel_type_id', '{{%personnel_types}}', 'id', 'CASCADE', 'CASCADE');

        // 2. Template Versions
        $this->createTable('{{%template_versions}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_template_id' => $this->integer()->unsigned()->notNull(),
            'version_number' => $this->integer()->notNull(),
            'version_label' => $this->string(100)->notNull(),
            'is_active' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'effective_from' => $this->date()->notNull(),
            'effective_to' => $this->date()->null(),
            'total_weight' => $this->decimal(5, 2)->notNull()->defaultValue(100.00),
            'score_formula_config' => $this->json()->null(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-template_version-unique', '{{%template_versions}}', ['evaluation_template_id', 'version_number'], true);
        $this->addForeignKey('fk-template_versions-template_id', '{{%template_versions}}', 'evaluation_template_id', '{{%evaluation_templates}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-template_versions-created_by', '{{%template_versions}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');

        // 3. หมวด/ส่วนของแบบประเมิน (Sections)
        $this->createTable('{{%evaluation_sections}}', [
            'id' => $this->primaryKey()->unsigned(),
            'template_version_id' => $this->integer()->unsigned()->notNull(),
            'parent_section_id' => $this->integer()->unsigned()->null(),
            'section_code' => $this->string(50)->null(),
            'name_th' => $this->string(300)->notNull(),
            'description' => $this->text()->null(),
            'weight' => $this->decimal(5, 2)->null()->defaultValue(0.00),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'is_evaluator_fill' => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('0=ผู้รับการประเมินกรอก, 1=ผู้ประเมินกรอก'),
            'section_type' => $this->string(50)->notNull()->defaultValue('general')->comment('main_work, secondary_work, competency, general, summary'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-eval_sections-template_version_id', '{{%evaluation_sections}}', 'template_version_id', '{{%template_versions}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-eval_sections-parent_section_id', '{{%evaluation_sections}}', 'parent_section_id', '{{%evaluation_sections}}', 'id', 'SET NULL', 'CASCADE');

        // 4. รายการประเมิน (Items)
        $this->createTable('{{%evaluation_items}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_section_id' => $this->integer()->unsigned()->notNull(),
            'item_code' => $this->string(50)->null(),
            'name_th' => $this->string(500)->notNull(),
            'description' => $this->text()->null(),
            'input_type' => $this->string(50)->notNull()->defaultValue('pdca_level')->comment('pdca_level, scale_1_5, score_direct, checkbox_list, weight_input, text, textarea'),
            'max_score' => $this->decimal(6, 2)->null()->defaultValue(5.00),
            'max_weight' => $this->decimal(5, 2)->null()->defaultValue(30.00),
            'default_weight' => $this->decimal(5, 2)->null()->defaultValue(0.00),
            'expected_level' => $this->tinyInteger()->null()->defaultValue(3),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'is_required' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'requires_evidence' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'evidence_instruction' => $this->text()->null(),
            'options_data' => $this->json()->null()->comment('JSON structure for complex options/criteria rules'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-eval_items-evaluation_section_id', '{{%evaluation_items}}', 'evaluation_section_id', '{{%evaluation_sections}}', 'id', 'CASCADE', 'CASCADE');

        // 5. เกณฑ์การให้คะแนน (Criteria)
        $this->createTable('{{%evaluation_criteria}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_item_id' => $this->integer()->unsigned()->notNull(),
            'level_value' => $this->integer()->notNull()->comment('1, 2, 3, 4, 5'),
            'level_label' => $this->string(200)->notNull(),
            'score_value' => $this->decimal(6, 2)->notNull(),
            'description' => $this->text()->null(),
            'condition_rule' => $this->string(255)->null(),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-eval_criteria-evaluation_item_id', '{{%evaluation_criteria}}', 'evaluation_item_id', '{{%evaluation_items}}', 'id', 'CASCADE', 'CASCADE');

        // 6. นิยามสมรรถนะ (Competency Definitions)
        $this->createTable('{{%competency_definitions}}', [
            'id' => $this->primaryKey()->unsigned(),
            'template_version_id' => $this->integer()->unsigned()->notNull(),
            'competency_code' => $this->string(50)->null(),
            'competency_type' => $this->string(30)->notNull()->defaultValue('core')->comment('core, functional, behavior'),
            'name_th' => $this->string(300)->notNull(),
            'name_en' => $this->string(300)->null(),
            'definition' => $this->text()->notNull(),
            'expected_level' => $this->tinyInteger()->notNull()->defaultValue(3),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk-comp_defs-template_version_id', '{{%competency_definitions}}', 'template_version_id', '{{%template_versions}}', 'id', 'CASCADE', 'CASCADE');

        // 7. ระดับพฤติกรรมบ่งชี้ของสมรรถนะ (Competency Levels 1-5)
        $this->createTable('{{%competency_levels}}', [
            'id' => $this->primaryKey()->unsigned(),
            'competency_definition_id' => $this->integer()->unsigned()->notNull(),
            'level_value' => $this->tinyInteger()->notNull()->comment('1 to 5'),
            'level_label' => $this->string(100)->notNull()->comment('Basic, Apply, Competence, Proficiency, Expert'),
            'behavior_description' => $this->text()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-comp_levels-unique', '{{%competency_levels}}', ['competency_definition_id', 'level_value'], true);
        $this->addForeignKey('fk-comp_levels-def_id', '{{%competency_levels}}', 'competency_definition_id', '{{%competency_definitions}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-comp_levels-def_id', '{{%competency_levels}}');
        $this->dropTable('{{%competency_levels}}');

        $this->dropForeignKey('fk-comp_defs-template_version_id', '{{%competency_definitions}}');
        $this->dropTable('{{%competency_definitions}}');

        $this->dropForeignKey('fk-eval_criteria-evaluation_item_id', '{{%evaluation_criteria}}');
        $this->dropTable('{{%evaluation_criteria}}');

        $this->dropForeignKey('fk-eval_items-evaluation_section_id', '{{%evaluation_items}}');
        $this->dropTable('{{%evaluation_items}}');

        $this->dropForeignKey('fk-eval_sections-parent_section_id', '{{%evaluation_sections}}');
        $this->dropForeignKey('fk-eval_sections-template_version_id', '{{%evaluation_sections}}');
        $this->dropTable('{{%evaluation_sections}}');

        $this->dropForeignKey('fk-template_versions-created_by', '{{%template_versions}}');
        $this->dropForeignKey('fk-template_versions-template_id', '{{%template_versions}}');
        $this->dropTable('{{%template_versions}}');

        $this->dropForeignKey('fk-eval_templates-personnel_type_id', '{{%evaluation_templates}}');
        $this->dropTable('{{%evaluation_templates}}');
    }
}
