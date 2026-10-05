<?php

use yii\db\Migration;

/**
 * Class m261005_150000_create_department_evaluation_cycles
 */
class m261005_150000_create_department_evaluation_cycles extends Migration
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

        $this->createTable('{{%department_evaluation_cycles}}', [
            'id' => $this->primaryKey()->unsigned(),
            'evaluation_cycle_id' => $this->integer()->unsigned()->notNull(),
            'department_id' => $this->integer()->unsigned()->notNull(),
            'status' => $this->string(30)->notNull()->defaultValue('pending'), // pending, active, completed, closed
            'opened_at' => $this->integer()->null(),
            'opened_by' => $this->integer()->null(),
            'closed_at' => $this->integer()->null(),
            'closed_by' => $this->integer()->null(),
            'notes' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex(
            'idx-dept_eval_cycles-cycle_dept',
            '{{%department_evaluation_cycles}}',
            ['evaluation_cycle_id', 'department_id'],
            true
        );

        $this->createIndex(
            'idx-dept_eval_cycles-status',
            '{{%department_evaluation_cycles}}',
            'status'
        );

        $this->addForeignKey(
            'fk-dept_eval_cycles-cycle_id',
            '{{%department_evaluation_cycles}}',
            'evaluation_cycle_id',
            '{{%evaluation_cycles}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-dept_eval_cycles-dept_id',
            '{{%department_evaluation_cycles}}',
            'department_id',
            '{{%departments}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-dept_eval_cycles-opened_by',
            '{{%department_evaluation_cycles}}',
            'opened_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-dept_eval_cycles-closed_by',
            '{{%department_evaluation_cycles}}',
            'closed_by',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Seed initial records for active cycle if exists
        $activeCycle = (new \yii\db\Query())
            ->from('{{%evaluation_cycles}}')
            ->where(['status' => ['active', 'evaluation']])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if ($activeCycle) {
            $rootDepts = (new \yii\db\Query())
                ->from('{{%departments}}')
                ->where(['parent_id' => null, 'status' => 1])
                ->all();

            $adminUser = (new \yii\db\Query())
                ->from('{{%user}}')
                ->where(['username' => 'superadmin'])
                ->one();
            $adminUserId = $adminUser ? $adminUser['id'] : null;

            $now = time();
            foreach ($rootDepts as $rd) {
                // Check if evaluations already exist for personnel in this root department
                $childIds = (new \yii\db\Query())
                    ->select('id')
                    ->from('{{%departments}}')
                    ->where(['parent_id' => $rd['id']])
                    ->column();
                $scopedIds = array_merge([(int)$rd['id']], array_map('intval', $childIds));

                $evalCount = (new \yii\db\Query())
                    ->from('{{%evaluations}} e')
                    ->innerJoin('{{%personnel}} p', 'p.id = e.personnel_id')
                    ->where(['e.evaluation_cycle_id' => $activeCycle['id']])
                    ->andWhere(['in', 'p.department_id', $scopedIds])
                    ->count();

                // If evaluations already exist, mark as active so current tests continue working seamlessly
                $status = ($evalCount > 0) ? 'active' : 'pending';
                $openedAt = ($evalCount > 0) ? $now : null;
                $openedBy = ($evalCount > 0) ? $adminUserId : null;

                $this->insert('{{%department_evaluation_cycles}}', [
                    'evaluation_cycle_id' => $activeCycle['id'],
                    'department_id' => $rd['id'],
                    'status' => $status,
                    'opened_at' => $openedAt,
                    'opened_by' => $openedBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%department_evaluation_cycles}}');
    }
}
