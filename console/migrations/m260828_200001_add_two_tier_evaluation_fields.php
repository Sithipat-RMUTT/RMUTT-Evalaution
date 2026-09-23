<?php

use yii\db\Migration;

/**
 * Handles adding two-tier evaluation workflow fields (Staff -> Section Head -> Division Head)
 */
class m260828_200001_add_two_tier_evaluation_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Personnel table updates
        $pSchema = $this->db->getTableSchema('{{%personnel}}', true);
        if ($pSchema) {
            if (!isset($pSchema->columns['position_level'])) {
                $this->addColumn('{{%personnel}}', 'position_level', $this->string(30)->notNull()->defaultValue('staff')->after('is_supervisor'));
            }
            if (!isset($pSchema->columns['division_head_id'])) {
                $this->addColumn('{{%personnel}}', 'division_head_id', $this->integer()->unsigned()->null()->after('supervisor_id'));
            }
        }

        $pFks = $this->db->createCommand("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'personnel' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->queryColumn();
        if (!in_array('fk-personnel-division_head_id', $pFks, true)) {
            $this->addForeignKey(
                'fk-personnel-division_head_id',
                '{{%personnel}}',
                'division_head_id',
                '{{%personnel}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        // 2. Evaluations table updates
        $eSchema = $this->db->getTableSchema('{{%evaluations}}', true);
        if ($eSchema) {
            if (!isset($eSchema->columns['evaluator_l1_id'])) {
                $this->addColumn('{{%evaluations}}', 'evaluator_l1_id', $this->integer()->unsigned()->null()->after('evaluator_id'));
            }
            if (!isset($eSchema->columns['evaluator_l2_id'])) {
                $this->addColumn('{{%evaluations}}', 'evaluator_l2_id', $this->integer()->unsigned()->null()->after('evaluator_l1_id'));
            }
            if (!isset($eSchema->columns['l1_evaluated_at'])) {
                $this->addColumn('{{%evaluations}}', 'l1_evaluated_at', $this->dateTime()->null()->after('supervisor_evaluated_at'));
            }
            if (!isset($eSchema->columns['l2_evaluated_at'])) {
                $this->addColumn('{{%evaluations}}', 'l2_evaluated_at', $this->dateTime()->null()->after('l1_evaluated_at'));
            }
            if (!isset($eSchema->columns['returned_by_role'])) {
                $this->addColumn('{{%evaluations}}', 'returned_by_role', $this->string(20)->null()->after('return_reason'));
            }
            if (!isset($eSchema->columns['l1_comment_strength'])) {
                $this->addColumn('{{%evaluations}}', 'l1_comment_strength', $this->text()->null()->after('return_reason'));
            }
            if (!isset($eSchema->columns['l1_comment_improvement'])) {
                $this->addColumn('{{%evaluations}}', 'l1_comment_improvement', $this->text()->null()->after('l1_comment_strength'));
            }
            if (!isset($eSchema->columns['l1_comment_suggestion'])) {
                $this->addColumn('{{%evaluations}}', 'l1_comment_suggestion', $this->text()->null()->after('l1_comment_improvement'));
            }
            if (!isset($eSchema->columns['l2_comment_strength'])) {
                $this->addColumn('{{%evaluations}}', 'l2_comment_strength', $this->text()->null()->after('supervisor_comment_suggestion'));
            }
            if (!isset($eSchema->columns['l2_comment_improvement'])) {
                $this->addColumn('{{%evaluations}}', 'l2_comment_improvement', $this->text()->null()->after('l2_comment_strength'));
            }
            if (!isset($eSchema->columns['l2_comment_suggestion'])) {
                $this->addColumn('{{%evaluations}}', 'l2_comment_suggestion', $this->text()->null()->after('l2_comment_improvement'));
            }
        }

        $eFks = $this->db->createCommand("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluations' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->queryColumn();
        if (!in_array('fk-evaluations-evaluator_l1_id', $eFks, true)) {
            $this->addForeignKey(
                'fk-evaluations-evaluator_l1_id',
                '{{%evaluations}}',
                'evaluator_l1_id',
                '{{%personnel}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
        if (!in_array('fk-evaluations-evaluator_l2_id', $eFks, true)) {
            $this->addForeignKey(
                'fk-evaluations-evaluator_l2_id',
                '{{%evaluations}}',
                'evaluator_l2_id',
                '{{%personnel}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        // 3. Evaluation Results updates
        $rSchema = $this->db->getTableSchema('{{%evaluation_results}}', true);
        if ($rSchema) {
            if (!isset($rSchema->columns['l1_performance_score'])) {
                $this->addColumn('{{%evaluation_results}}', 'l1_performance_score', $this->decimal(8, 4)->null()->after('self_total_score'));
            }
            if (!isset($rSchema->columns['l1_competency_score'])) {
                $this->addColumn('{{%evaluation_results}}', 'l1_competency_score', $this->decimal(8, 4)->null()->after('l1_performance_score'));
            }
            if (!isset($rSchema->columns['l1_total_score'])) {
                $this->addColumn('{{%evaluation_results}}', 'l1_total_score', $this->decimal(8, 4)->null()->after('l1_competency_score'));
            }
            if (!isset($rSchema->columns['l2_performance_score'])) {
                $this->addColumn('{{%evaluation_results}}', 'l2_performance_score', $this->decimal(8, 4)->null()->after('supervisor_total_score'));
            }
            if (!isset($rSchema->columns['l2_competency_score'])) {
                $this->addColumn('{{%evaluation_results}}', 'l2_competency_score', $this->decimal(8, 4)->null()->after('l2_performance_score'));
            }
            if (!isset($rSchema->columns['l2_total_score'])) {
                $this->addColumn('{{%evaluation_results}}', 'l2_total_score', $this->decimal(8, 4)->null()->after('l2_competency_score'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // 1. Drop evaluation_results columns
        $rCols = ['l2_total_score', 'l2_competency_score', 'l2_performance_score', 'l1_total_score', 'l1_competency_score', 'l1_performance_score'];
        foreach ($rCols as $c) {
            try { $this->dropColumn('{{%evaluation_results}}', $c); } catch (\Throwable $e) {}
        }

        // 2. Drop evaluations FKs and columns
        try { $this->dropForeignKey('fk-evaluations-evaluator_l2_id', '{{%evaluations}}'); } catch (\Throwable $e) {}
        try { $this->dropForeignKey('fk-evaluations-evaluator_l1_id', '{{%evaluations}}'); } catch (\Throwable $e) {}
        $eCols = [
            'l2_comment_suggestion', 'l2_comment_improvement', 'l2_comment_strength',
            'l1_comment_suggestion', 'l1_comment_improvement', 'l1_comment_strength',
            'returned_by_role', 'l2_evaluated_at', 'l1_evaluated_at',
            'evaluator_l2_id', 'evaluator_l1_id'
        ];
        foreach ($eCols as $c) {
            try { $this->dropColumn('{{%evaluations}}', $c); } catch (\Throwable $e) {}
        }

        // 3. Drop personnel FK and columns
        try { $this->dropForeignKey('fk-personnel-division_head_id', '{{%personnel}}'); } catch (\Throwable $e) {}
        try { $this->dropColumn('{{%personnel}}', 'division_head_id'); } catch (\Throwable $e) {}
        try { $this->dropColumn('{{%personnel}}', 'position_level'); } catch (\Throwable $e) {}
    }
}
