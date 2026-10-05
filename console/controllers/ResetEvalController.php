<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use common\models\Evaluation;
use common\models\EvaluationAnswer;
use common\models\EvaluationCompetencyAnswer;
use common\models\EvaluationResult;
use common\models\EvidenceFile;

/**
 * ResetEvalController resets evaluations to self_assessment status for interactive testing.
 */
class ResetEvalController extends Controller
{
    public function actionIndex()
    {
        $this->stdout("Resetting all evaluations for interactive testing...\n", Console::FG_YELLOW);

        $db = Yii::$app->db;

        // Delete results and answers
        $db->createCommand("DELETE FROM {{%evaluation_results}}")->execute();
        $db->createCommand("DELETE FROM {{%evaluation_answers}}")->execute();
        $db->createCommand("DELETE FROM {{%evaluation_competency_answers}}")->execute();
        $db->createCommand("DELETE FROM {{%evidence_files}}")->execute();

        // Ensure evaluations exist for all personnel
        $activeCycle = \common\models\EvaluationCycle::findOne(['status' => [\common\models\EvaluationCycle::STATUS_ACTIVE, \common\models\EvaluationCycle::STATUS_EVALUATION]])
            ?: \common\models\EvaluationCycle::find()->orderBy(['id' => SORT_DESC])->one();

        if ($activeCycle) {
            $allPersonnel = \common\models\Personnel::find()->all();
            foreach ($allPersonnel as $p) {
                $mapping = \common\models\CycleTemplateMapping::findOne([
                    'evaluation_cycle_id' => $activeCycle->id,
                    'personnel_type_id' => $p->personnel_type_id,
                ]);
                if ($mapping) {
                    $eval = Evaluation::findOne([
                        'evaluation_cycle_id' => $activeCycle->id,
                        'personnel_id' => $p->id,
                    ]);
                    if (!$eval) {
                        $eval = new Evaluation();
                        $eval->evaluation_cycle_id = $activeCycle->id;
                        $eval->personnel_id = $p->id;
                        $eval->template_version_id = $mapping->template_version_id;
                    }
                    $eval->evaluator_id = $p->supervisor_id ?: $p->division_head_id;
                    $eval->evaluator_l1_id = $p->supervisor_id;
                    $eval->evaluator_l2_id = $p->division_head_id;
                    $eval->status = Evaluation::STATUS_SELF_ASSESSMENT;
                    $eval->self_submitted_at = null;
                    $eval->supervisor_evaluated_at = null;
                    $eval->l1_evaluated_at = null;
                    $eval->l2_evaluated_at = null;
                    $eval->completed_at = null;
                    $eval->returned_at = null;
                    $eval->return_reason = null;
                    $eval->returned_by_role = null;
                    $eval->l1_comment_strength = null;
                    $eval->l1_comment_improvement = null;
                    $eval->l1_comment_suggestion = null;
                    $eval->l2_comment_strength = null;
                    $eval->l2_comment_improvement = null;
                    $eval->l2_comment_suggestion = null;
                    $eval->supervisor_comment_strength = null;
                    $eval->supervisor_comment_improvement = null;
                    $eval->supervisor_comment_suggestion = null;
                    $eval->employment_recommendation = null;
                    $eval->acknowledgement_at = null;
                    $eval->director_approver_id = null;
                    $eval->director_approved_at = null;
                    $eval->save(false);
                }
            }
        }

        $this->stdout("All evaluations have been reset to 'self_assessment' state with 2-tier evaluators assigned!\n", Console::FG_GREEN);
        $this->stdout("You can now login and fill in the self-assessment forms from scratch.\n", Console::FG_CYAN);
    }

    public function actionList()
    {
        $db = Yii::$app->db;
        $rows = (new \yii\db\Query())
            ->select([
                'u.id as user_id',
                'u.username',
                'p.id as p_id',
                'p.employee_code',
                'p.prefix_th',
                'p.first_name_th',
                'p.last_name_th',
                'p.position_level',
                'd.name_th as dept',
                'pos.name_th as position',
                'p.supervisor_id',
                'p.division_head_id'
            ])
            ->from('{{%user}} u')
            ->leftJoin('{{%personnel}} p', 'p.user_id = u.id')
            ->leftJoin('{{%departments}} d', 'd.id = p.department_id')
            ->leftJoin('{{%positions}} pos', 'pos.id = p.position_id')
            ->orderBy(['u.id' => SORT_ASC])
            ->all();

        $this->stdout(sprintf("%-3s | %-12s | %-10s | %-32s | %-14s | %-25s | %-25s\n", "UID", "Username", "Code", "Name", "Level", "Position", "Department"), Console::FG_YELLOW);
        $this->stdout(str_repeat("-", 125) . "\n");

        foreach ($rows as $r) {
            $name = trim(($r['prefix_th'] ?? '') . ' ' . ($r['first_name_th'] ?? '') . ' ' . ($r['last_name_th'] ?? ''));
            $this->stdout(sprintf(
                "%-3d | %-12s | %-10s | %-32s | %-14s | %-25s | %-25s\n",
                $r['user_id'],
                $r['username'],
                $r['employee_code'] ?? '-',
                $name,
                $r['position_level'] ?? '-',
                $r['position'] ?? '-',
                $r['dept'] ?? '-'
            ));
        }
    }

    public function actionDepts()
    {
        $db = Yii::$app->db;
        $depts = (new \yii\db\Query())->from('{{%departments}}')->orderBy(['id' => SORT_ASC])->all();
        $this->stdout(sprintf("%-4s | %-12s | %-10s | %-40s\n", "ID", "Code", "ParentID", "Name TH"), Console::FG_YELLOW);
        $this->stdout(str_repeat("-", 80) . "\n");
        foreach ($depts as $d) {
            $this->stdout(sprintf("%-4d | %-12s | %-10s | %-40s\n", $d['id'], $d['code'] ?? '-', $d['parent_id'] ?? 'null', $d['name_th']));
        }

        $this->stdout("\n" . sprintf("%-4s | %-15s | %-30s | %-20s\n", "ID", "Code", "Name TH", "Level Label"), Console::FG_YELLOW);
        $this->stdout(str_repeat("-", 80) . "\n");
        $positions = (new \yii\db\Query())->from('{{%positions}}')->orderBy(['id' => SORT_ASC])->all();
        foreach ($positions as $pos) {
            $this->stdout(sprintf("%-4d | %-15s | %-30s | %-20s\n", $pos['id'], $pos['code'] ?? '-', $pos['name_th'], $pos['level_label'] ?? '-'));
        }
    }

    public function actionRecalculateAll()
    {
        $this->stdout("Recalculating all evaluations with updated weights (Civil, Univ & Govt: 70/30)...\n", Console::FG_YELLOW);
        $evaluations = Evaluation::find()->with(['personnel.personnelType'])->all();
        $count = 0;
        foreach ($evaluations as $eval) {
            $pType = $eval->personnel?->personnelType?->code ?: 'UNKNOWN';
            $res = \common\services\EvaluationCalculatorService::calculate($eval, 1, true);
            $count++;
            $this->stdout("   ✔ [{$count}] Eval #{$eval->id} ({$eval->personnel->fullName} - {$pType}): Score = {$res->final_percentage}% (Perf: {$res->supervisor_performance_score}, Comp: {$res->supervisor_competency_score}, Grade: {$res->performance_level})\n", Console::FG_GREEN);
        }
        $this->stdout("\nSuccessfully recalculated {$count} evaluations!\n", Console::FG_GREEN, Console::BOLD);
    }

    public function actionInspectSpec16()
    {
        $items = \common\models\EvaluationItem::find()->where(['item_code' => 'SPEC_1_6_SECONDARY'])->all();
        $this->stdout("Found " . count($items) . " items for SPEC_1_6_SECONDARY:\n", Console::FG_YELLOW);
        foreach ($items as $it) {
            $sec = $it->section;
            $ver = $sec ? $sec->templateVersion : null;
            $tpl = $ver ? $ver->evaluationTemplate : null;
            $evalCount = $ver ? Evaluation::find()->where(['template_version_id' => $ver->id])->count() : 0;
            $this->stdout("ID: {$it->id} | Name: {$it->name_th} | Template: {$tpl?->name_th} (ID: {$tpl?->id}) | Version: {$ver?->id} | Evals: {$evalCount}\n", Console::FG_CYAN);
            $opts = is_string($it->options_data) ? json_decode($it->options_data, true) : $it->options_data;
            if (is_array($opts)) {
                foreach ($opts as $o) {
                    $this->stdout("   - " . ($o['key'] ?? '') . ": " . ($o['text'] ?? '') . "\n");
                }
            } else {
                $this->stdout("   - options_data is NULL or empty\n", Console::FG_RED);
            }
        }
    }

    public function actionFixSpec16()
    {
        $specSecondaryOptions = [
            ['key' => '1', 'text' => 'เข้าร่วมกิจกรรม/โครงการ/งานของสำนักฯ และมหาวิทยาลัย (ระบุ พร้อมแนบหลักฐาน)'],
            ['key' => '2', 'text' => 'ดำเนินการขับเคลื่อนผลสัมฤทธิ์ที่สำคัญ (Key Results - KR) ตามประเด็นยุทธศาสตร์ของสำนักฯ (เช่น ผลงานที่แสดงให้เห็นชัดถึงการขับเคลื่อนการดำเนินแผนของสำนักฯ / มหาลัยฯ พร้อมแนบหลักฐาน)'],
            ['key' => '3', 'text' => 'คณะทำงานหรือมีส่วนร่วมในการดำเนินงาน เช่น งานความเสี่ยง / KM / งาน EdPEx (เช่น คำสั่งที่/ หนังสือมอบหมายหน้าที่/ หลักฐานที่เป็นลายลักษณ์อักษร พร้อมแนบหลักฐาน)'],
            ['key' => '4', 'text' => 'มีนวัตกรรมหรือการพัฒนากระบวนการทำงาน /การทำ LEAN Management /การทำ Kaizen (ระบุ พร้อมแนบหลักฐาน)'],
            ['key' => '5', 'text' => 'เข้าร่วมฝึกทักษะ หรือพัฒนาสมรรถนะวิชาชีพ และมีการรายงานการนำไปใช้ประโยชน์ (ระบุ พร้อมแนบหลักฐาน)'],
            ['key' => '6', 'text' => 'ได้รับการพัฒนาตนเองผ่านมาตรฐาน Certified จากหน่วยงานภายนอก ที่เกี่ยวข้องกับตำแหน่งหน้าที่ (ภายในปีงบประมาณ หรือ ย้อนหลัง 1 ปี **1 Certificate ใช้ได้ 2 รอบประเมิน พร้อมแนบหลักฐาน)'],
            ['key' => '7', 'text' => 'พัฒนาศักยภาพด้านการใช้ภาษาอังกฤษของสายสนับสนุน (มีใบรับรองเป็นหลักฐานแสดงผลการเข้าร่วม หรือ การทดสอบ พร้อมแนบหลักฐาน)'],
            ['key' => '8', 'text' => 'งานส่งเสริมความเป็นนานาชาติ /งานบริการวิชาการ / งานทำนุบำรุงศิลปวัฒนธรรม อย่างใดอย่างหนึ่ง (ระบุ พร้อมแนบหลักฐาน)'],
            ['key' => '9', 'text' => 'การหารายได้เข้าสำนักฯ (ระบุ พร้อมแนบหลักฐาน)'],
            ['key' => '10', 'text' => 'อื่น ๆ (ระบุ พร้อมแนบหลักฐาน)'],
        ];

        $jsonStr = json_encode($specSecondaryOptions, JSON_UNESCAPED_UNICODE);
        $count = \common\models\EvaluationItem::updateAll(
            ['options_data' => $jsonStr],
            ['item_code' => 'SPEC_1_6_SECONDARY']
        );
        $this->stdout("Updated {$count} records of SPEC_1_6_SECONDARY with official 10 options.\n", Console::FG_GREEN);
    }
}
