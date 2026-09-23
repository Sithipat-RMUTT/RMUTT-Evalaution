<?php
use yii\db\Migration;

class m260828_180000_harden_evaluation_workflow extends Migration
{
    public function safeUp()
    {
        $cycleIndexes = $this->db->createCommand("SHOW INDEX FROM {{%evaluation_cycles}} WHERE Key_name = 'uq-evaluation-cycle-year-number'")->queryAll();
        if (empty($cycleIndexes)) {
            $this->createIndex('uq-evaluation-cycle-year-number', '{{%evaluation_cycles}}', ['fiscal_year', 'cycle_number'], true);
        }

        $evalSchema = $this->db->getTableSchema('{{%evaluations}}', true);
        if ($evalSchema) {
            if (!isset($evalSchema->columns['committee_reviewed_at'])) {
                $this->addColumn('{{%evaluations}}', 'committee_reviewed_at', $this->dateTime()->null());
            }
            if (!isset($evalSchema->columns['committee_reviewer_id'])) {
                $this->addColumn('{{%evaluations}}', 'committee_reviewer_id', $this->integer()->null());
            } else {
                // Ensure signed integer to match user.id
                $this->alterColumn('{{%evaluations}}', 'committee_reviewer_id', $this->integer()->null());
            }
            if (!isset($evalSchema->columns['director_approved_at'])) {
                $this->addColumn('{{%evaluations}}', 'director_approved_at', $this->dateTime()->null());
            }
            if (!isset($evalSchema->columns['director_approver_id'])) {
                $this->addColumn('{{%evaluations}}', 'director_approver_id', $this->integer()->null());
            } else {
                // Ensure signed integer to match user.id
                $this->alterColumn('{{%evaluations}}', 'director_approver_id', $this->integer()->null());
            }
            if (!isset($evalSchema->columns['workflow_version'])) {
                $this->addColumn('{{%evaluations}}', 'workflow_version', $this->string(20)->notNull()->defaultValue('1.1'));
            }
        }

        $evalFks = $this->db->createCommand("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluations' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->queryColumn();
        if (!in_array('fk-evaluations-committee-reviewer', $evalFks, true)) {
            $this->addForeignKey('fk-evaluations-committee-reviewer', '{{%evaluations}}', 'committee_reviewer_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
        }
        if (!in_array('fk-evaluations-director-approver', $evalFks, true)) {
            $this->addForeignKey('fk-evaluations-director-approver', '{{%evaluations}}', 'director_approver_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
        }

        $evalIndexes = $this->db->createCommand("SHOW INDEX FROM {{%evaluations}} WHERE Key_name = 'idx-evaluations-status-cycle'")->queryAll();
        if (empty($evalIndexes)) {
            $this->createIndex('idx-evaluations-status-cycle', '{{%evaluations}}', ['evaluation_cycle_id', 'status']);
        }

        $annualSchema = $this->db->getTableSchema('{{%annual_evaluations}}', true);
        if (!$annualSchema) {
            $this->createTable('{{%annual_evaluations}}', [
                'id' => $this->primaryKey()->unsigned(),
                'fiscal_year' => $this->integer()->notNull(),
                'personnel_id' => $this->integer()->unsigned()->notNull(),
                'cycle1_evaluation_id' => $this->integer()->unsigned()->null(),
                'cycle2_evaluation_id' => $this->integer()->unsigned()->null(),
                'cycle1_score' => $this->decimal(8, 4)->null(),
                'cycle2_score' => $this->decimal(8, 4)->null(),
                'annual_average' => $this->decimal(8, 4)->null(),
                'performance_level' => $this->string(50)->null(),
                'status' => $this->string(30)->notNull()->defaultValue('draft'),
                'approved_at' => $this->dateTime()->null(),
                'approved_by' => $this->integer()->null(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

            $this->createIndex('uq-annual-evaluation', '{{%annual_evaluations}}', ['fiscal_year', 'personnel_id'], true);
            $this->addForeignKey('fk-annual-personnel', '{{%annual_evaluations}}', 'personnel_id', '{{%personnel}}', 'id', 'CASCADE', 'CASCADE');
            $this->addForeignKey('fk-annual-cycle1', '{{%annual_evaluations}}', 'cycle1_evaluation_id', '{{%evaluations}}', 'id', 'SET NULL', 'CASCADE');
            $this->addForeignKey('fk-annual-cycle2', '{{%annual_evaluations}}', 'cycle2_evaluation_id', '{{%evaluations}}', 'id', 'SET NULL', 'CASCADE');
            $this->addForeignKey('fk-annual-approved-by', '{{%annual_evaluations}}', 'approved_by', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
        }
    }

    public function safeDown()
    {
        $annualSchema = $this->db->getTableSchema('{{%annual_evaluations}}', true);
        if ($annualSchema) {
            $this->dropTable('{{%annual_evaluations}}');
        }
    }
}
