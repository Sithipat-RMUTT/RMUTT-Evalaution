<?php

use yii\db\Migration;

/**
 * Class m260828_210001_add_department_to_templates
 */
class m260828_210001_add_department_to_templates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Add department_id and is_default to evaluation_templates
        $tSchema = $this->db->getTableSchema('{{%evaluation_templates}}', true);
        if ($tSchema) {
            if (!isset($tSchema->columns['department_id'])) {
                $this->addColumn('{{%evaluation_templates}}', 'department_id', $this->integer()->unsigned()->null()->after('personnel_type_id'));
            }
            if (!isset($tSchema->columns['is_default'])) {
                $this->addColumn('{{%evaluation_templates}}', 'is_default', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('status'));
            }
        }

        $tIndexes = $this->db->createCommand("SHOW INDEX FROM {{%evaluation_templates}} WHERE Key_name = 'idx-eval_templates-department_id'")->queryAll();
        if (empty($tIndexes)) {
            $this->createIndex('idx-eval_templates-department_id', '{{%evaluation_templates}}', 'department_id');
        }

        $tFks = $this->db->createCommand("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluation_templates' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->queryColumn();
        if (!in_array('fk-eval_templates-department_id', $tFks, true)) {
            $this->addForeignKey(
                'fk-eval_templates-department_id',
                '{{%evaluation_templates}}',
                'department_id',
                '{{%departments}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        // 2. Add department_id to cycle_template_mappings
        $mSchema = $this->db->getTableSchema('{{%cycle_template_mappings}}', true);
        if ($mSchema && !isset($mSchema->columns['department_id'])) {
            $this->addColumn('{{%cycle_template_mappings}}', 'department_id', $this->integer()->unsigned()->null()->after('evaluation_cycle_id'));
        }

        try {
            $this->dropIndex('evaluation_cycle_id', '{{%cycle_template_mappings}}');
        } catch (\Throwable $e) {}

        $mIndexes = $this->db->createCommand("SHOW INDEX FROM {{%cycle_template_mappings}} WHERE Key_name = 'idx-cycle_mappings-cycle_type_dept'")->queryAll();
        if (empty($mIndexes)) {
            $this->createIndex(
                'idx-cycle_mappings-cycle_type_dept',
                '{{%cycle_template_mappings}}',
                ['evaluation_cycle_id', 'personnel_type_id', 'department_id'],
                false
            );
        }

        $mFks = $this->db->createCommand("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cycle_template_mappings' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->queryColumn();
        if (!in_array('fk-cycle_mappings-department_id', $mFks, true)) {
            $this->addForeignKey(
                'fk-cycle_mappings-department_id',
                '{{%cycle_template_mappings}}',
                'department_id',
                '{{%departments}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // 1. Revert cycle_template_mappings
        try { $this->dropForeignKey('fk-cycle_mappings-department_id', '{{%cycle_template_mappings}}'); } catch (\Throwable $e) {}
        try { $this->dropIndex('idx-cycle_mappings-cycle_type_dept', '{{%cycle_template_mappings}}'); } catch (\Throwable $e) {}
        try { $this->dropColumn('{{%cycle_template_mappings}}', 'department_id'); } catch (\Throwable $e) {}

        // 2. Revert evaluation_templates
        try { $this->dropForeignKey('fk-eval_templates-department_id', '{{%evaluation_templates}}'); } catch (\Throwable $e) {}
        try { $this->dropIndex('idx-eval_templates-department_id', '{{%evaluation_templates}}'); } catch (\Throwable $e) {}
        try { $this->dropColumn('{{%evaluation_templates}}', 'is_default'); } catch (\Throwable $e) {}
        try { $this->dropColumn('{{%evaluation_templates}}', 'department_id'); } catch (\Throwable $e) {}
    }
}
