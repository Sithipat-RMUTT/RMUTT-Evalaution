<?php

use yii\db\Migration;

/**
 * Class m260831_100001_fix_cycle_template_mapping_unique_index
 * 
 * Drops the old (evaluation_cycle_id, personnel_type_id) unique constraint
 * and ensures the new composite unique constraint (cycle, type, department) is properly applied.
 */
class m260831_100001_fix_cycle_template_mapping_unique_index extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = '{{%cycle_template_mappings}}';

        // 1. Drop old 2-column unique index if exists
        $oldIndexes = $this->db->createCommand("SHOW INDEX FROM {$table} WHERE Key_name = 'idx-cycle_template_mapping-unique'")->queryAll();
        if (!empty($oldIndexes)) {
            try {
                $this->dropIndex('idx-cycle_template_mapping-unique', $table);
            } catch (\Throwable $e) {}
        }

        // 2. Create proper 3-column unique index first
        $newUnique = $this->db->createCommand("SHOW INDEX FROM {$table} WHERE Key_name = 'idx-cycle_mappings-cycle_type_dept-unique'")->queryAll();
        if (empty($newUnique)) {
            $this->createIndex(
                'idx-cycle_mappings-cycle_type_dept-unique',
                $table,
                ['evaluation_cycle_id', 'personnel_type_id', 'department_id'],
                true
            );
        }

        // 3. Drop redundant non-unique index if exists
        $legacyIndexes = $this->db->createCommand("SHOW INDEX FROM {$table} WHERE Key_name = 'idx-cycle_mappings-cycle_type_dept'")->queryAll();
        if (!empty($legacyIndexes)) {
            try {
                $this->dropIndex('idx-cycle_mappings-cycle_type_dept', $table);
            } catch (\Throwable $e) {}
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = '{{%cycle_template_mappings}}';

        $newUnique = $this->db->createCommand("SHOW INDEX FROM {$table} WHERE Key_name = 'idx-cycle_mappings-cycle_type_dept-unique'")->queryAll();
        if (!empty($newUnique)) {
            try {
                $this->dropIndex('idx-cycle_mappings-cycle_type_dept-unique', $table);
            } catch (\Throwable $e) {}
        }

        $oldIndexes = $this->db->createCommand("SHOW INDEX FROM {$table} WHERE Key_name = 'idx-cycle_template_mapping-unique'")->queryAll();
        if (empty($oldIndexes)) {
            try {
                $this->createIndex(
                    'idx-cycle_template_mapping-unique',
                    $table,
                    ['evaluation_cycle_id', 'personnel_type_id'],
                    true
                );
            } catch (\Throwable $e) {}
        }
    }
}
