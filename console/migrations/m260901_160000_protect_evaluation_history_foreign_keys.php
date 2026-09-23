<?php

use yii\db\Migration;

/**
 * Class m260901_160000_protect_evaluation_history_foreign_keys
 * 
 * Protects historical evaluation records from accidental cascade deletion:
 * Changes fk-evaluations-personnel_id and fk-annual-personnel from ON DELETE CASCADE to ON DELETE RESTRICT.
 */
class m260901_160000_protect_evaluation_history_foreign_keys extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Protect evaluations table
        try {
            $this->dropForeignKey('fk-evaluations-personnel_id', '{{%evaluations}}');
        } catch (\Throwable $e) {}

        $this->addForeignKey(
            'fk-evaluations-personnel_id',
            '{{%evaluations}}',
            'personnel_id',
            '{{%personnel}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        // 2. Protect annual_evaluations table
        try {
            $this->dropForeignKey('fk-annual-personnel', '{{%annual_evaluations}}');
        } catch (\Throwable $e) {}

        $this->addForeignKey(
            'fk-annual-personnel',
            '{{%annual_evaluations}}',
            'personnel_id',
            '{{%personnel}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        try {
            $this->dropForeignKey('fk-evaluations-personnel_id', '{{%evaluations}}');
        } catch (\Throwable $e) {}

        $this->addForeignKey(
            'fk-evaluations-personnel_id',
            '{{%evaluations}}',
            'personnel_id',
            '{{%personnel}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        try {
            $this->dropForeignKey('fk-annual-personnel', '{{%annual_evaluations}}');
        } catch (\Throwable $e) {}

        $this->addForeignKey(
            'fk-annual-personnel',
            '{{%annual_evaluations}}',
            'personnel_id',
            '{{%personnel}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }
}
