<?php

use yii\db\Migration;

/**
 * Adds schedule and name fields to department_evaluation_cycles table.
 */
class m261005_160000_add_schedule_fields_to_department_evaluation_cycles extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%department_evaluation_cycles}}', 'name_th', $this->string(255)->null()->after('department_id'));
        $this->addColumn('{{%department_evaluation_cycles}}', 'period_start', $this->date()->null()->after('name_th'));
        $this->addColumn('{{%department_evaluation_cycles}}', 'period_end', $this->date()->null()->after('period_start'));
        $this->addColumn('{{%department_evaluation_cycles}}', 'self_assessment_start', $this->dateTime()->null()->after('period_end'));
        $this->addColumn('{{%department_evaluation_cycles}}', 'self_assessment_end', $this->dateTime()->null()->after('self_assessment_start'));
        $this->addColumn('{{%department_evaluation_cycles}}', 'supervisor_eval_start', $this->dateTime()->null()->after('self_assessment_end'));
        $this->addColumn('{{%department_evaluation_cycles}}', 'supervisor_eval_end', $this->dateTime()->null()->after('supervisor_eval_start'));

        // Pre-populate existing records with their cycle defaults
        $cycles = (new \yii\db\Query())->from('{{%evaluation_cycles}}')->all();
        foreach ($cycles as $c) {
            $this->update('{{%department_evaluation_cycles}}', [
                'period_start' => $c['period_start'],
                'period_end' => $c['period_end'],
                'self_assessment_start' => $c['self_assessment_start'],
                'self_assessment_end' => $c['self_assessment_end'],
                'supervisor_eval_start' => $c['supervisor_eval_start'],
                'supervisor_eval_end' => $c['supervisor_eval_end'],
            ], ['evaluation_cycle_id' => $c['id']]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%department_evaluation_cycles}}', 'supervisor_eval_end');
        $this->dropColumn('{{%department_evaluation_cycles}}', 'supervisor_eval_start');
        $this->dropColumn('{{%department_evaluation_cycles}}', 'self_assessment_end');
        $this->dropColumn('{{%department_evaluation_cycles}}', 'self_assessment_start');
        $this->dropColumn('{{%department_evaluation_cycles}}', 'period_end');
        $this->dropColumn('{{%department_evaluation_cycles}}', 'period_start');
        $this->dropColumn('{{%department_evaluation_cycles}}', 'name_th');
    }
}
