<?php
namespace common\services;

use common\models\Evaluation;
use common\models\EvaluationAnswer;
use common\models\EvaluationCompetencyAnswer;
use common\models\EvaluationItem;
use common\models\EvidenceFile;
use common\models\PersonnelType;
use yii\base\InvalidArgumentException;

final class EvaluationValidationService
{
    public static function validateAnswerOwnership(Evaluation $e, int $itemId): EvaluationItem
    {
        $item = EvaluationItem::find()->joinWith('section')->where([
            'evaluation_items.id' => $itemId,
            'evaluation_sections.template_version_id' => $e->template_version_id,
        ])->one();
        if (!$item) {
            throw new InvalidArgumentException('รายการประเมินไม่ถูกต้องสำหรับแบบประเมินนี้');
        }
        return $item;
    }

    public static function validateNumeric($value, float $min = 0, float $max = 100): float
    {
        if ($value === null || $value === '' || !is_numeric($value) || !is_finite((float)$value)) {
            throw new InvalidArgumentException('คะแนนต้องเป็นตัวเลข');
        }
        $v = (float)$value;
        if ($v < $min || $v > $max) {
            throw new InvalidArgumentException("คะแนนต้องอยู่ระหว่าง {$min} ถึง {$max}");
        }
        return $v;
    }

    public static function validateLevel($value): int
    {
        return (int)self::validateNumeric($value, 1, 5);
    }

    public static function validateText($value, int $max = 5000): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = (string)$value;
        if (mb_strlen($v) > $max) {
            throw new InvalidArgumentException("ข้อความยาวเกิน {$max} ตัวอักษร");
        }
        return $v;
    }

    public static function validateMainWork(array $rows, float $requiredWeight = 80, bool $requireSupervisorScore = false): void
    {
        if (count($rows) < 1 || count($rows) > 10) {
            throw new InvalidArgumentException('ภาระงานหลักต้องมี 1-10 รายการ');
        }
        $sum = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('รูปแบบภาระงานไม่ถูกต้อง');
            }
            $title = trim((string)($row['title'] ?? ''));
            if ($title === '') {
                throw new InvalidArgumentException('กรุณาระบุชื่องานภาระงานหลักให้ครบ');
            }
            self::validateText($title, 500);
            $weight = self::validateWeight($row['weight'] ?? null, 0, 100);
            if ($requireSupervisorScore) {
                self::validateNumeric($row['supervisor_score'] ?? null, 0, 100);
            }
            if (isset($row['self_score']) && $row['self_score'] !== '') {
                self::validateNumeric($row['self_score'], 0, 100);
            }
            if (isset($row['supervisor_score']) && $row['supervisor_score'] !== '') {
                self::validateNumeric($row['supervisor_score'], 0, 100);
            }
            self::validateText($row['kpi'] ?? null, 2000);
            $sum += $weight;
        }
        if (abs($sum - $requiredWeight) > 0.01) {
            throw new InvalidArgumentException("น้ำหนักภาระงานหลักต้องรวม {$requiredWeight}%");
        }
    }

    public static function validateGovtMain(array $rows, float $requiredWeight = 80, bool $requireSupervisorScore = false, bool $enforceTotal = true): void
    {
        if (count($rows) < 1 || count($rows) > 10) {
            throw new InvalidArgumentException('ภาระงานหลักต้องมี 1-10 รายการ');
        }
        $sum = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('รูปแบบภาระงานไม่ถูกต้อง');
            }
            $title = trim((string)($row['title'] ?? ''));
            if ($title === '') {
                throw new InvalidArgumentException('กรุณาระบุชื่องานภาระงานหลักให้ครบ');
            }
            self::validateText($title, 500);
            $sum += self::validateWeight($row['weight'] ?? null, 0, 100);
            foreach (['kpi_volume', 'kpi_quality', 'kpi_timeliness', 'kpi_resource'] as $k) {
                if ($requireSupervisorScore) {
                    self::validateNumeric($row[$k] ?? null, 0, 100);
                } elseif (isset($row[$k]) && $row[$k] !== '') {
                    self::validateNumeric($row[$k], 0, 100);
                }
            }
        }
        if ($enforceTotal && abs($sum - $requiredWeight) > 0.01) {
            throw new InvalidArgumentException("น้ำหนักภาระงานหลักต้องรวม {$requiredWeight}%");
        }
    }

    public static function validateWeight($value, float $min = 0, float $max = 100): float
    {
        if ($value === null || $value === '' || !is_numeric($value) || !is_finite((float)$value)) {
            throw new InvalidArgumentException('น้ำหนักต้องเป็นตัวเลข');
        }
        $v = (float)$value;
        if ($v < $min || $v > $max) {
            throw new InvalidArgumentException("น้ำหนักต้องอยู่ระหว่าง {$min}-{$max}");
        }
        return $v;
    }

    private static function answerHasContent(?EvaluationAnswer $answer): bool
    {
        if (!$answer) {
            return false;
        }
        if ($answer->numeric_value !== null) {
            return true;
        }
        if ($answer->text_value !== null && trim((string)$answer->text_value) !== '') {
            return true;
        }
        $data = $answer->json_value;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        return is_array($data) && count($data) > 0;
    }

    private static function requireItemAnswers(Evaluation $e, string $answeredBy, array $codes): void
    {
        $answers = EvaluationAnswer::find()->joinWith('item')->where([
            'evaluation_id' => $e->id,
            'answered_by' => $answeredBy,
        ])->indexBy('item_code')->all();
        foreach ($codes as $code) {
            if (!isset($answers[$code])) {
                throw new InvalidArgumentException("กรุณากรอกข้อมูล: {$code}");
            }
            self::validateStoredAnswer($answers[$code], $answers[$code]->item);
        }
    }

    public static function validateRequired(Evaluation $e, string $answeredBy): void
    {
        if (!in_array($answeredBy, ['self', 'supervisor', 'l1', 'l2'], true)) {
            throw new InvalidArgumentException('ผู้ตอบแบบประเมินไม่ถูกต้อง');
        }
        $items = EvaluationItem::find()->joinWith('section')->where([
            'evaluation_sections.template_version_id' => $e->template_version_id,
            'evaluation_items.is_required' => 1,
        ])->all();
        $answers = EvaluationAnswer::find()->where(['evaluation_id' => $e->id, 'answered_by' => $answeredBy])->indexBy('evaluation_item_id')->all();
        foreach ($items as $item) {
            if (!isset($answers[$item->id])) {
                continue;
            }
            self::validateStoredAnswer($answers[$item->id], $item);
        }

        $type = $e->personnel && $e->personnel->personnelType ? $e->personnel->personnelType->code : null;
        if ($type) {
            if (in_array($type, [PersonnelType::CODE_CIVIL, PersonnelType::CODE_UNIVERSITY], true)) {
                if (EvaluationAnswer::find()->joinWith('item')->where(['evaluation_id' => $e->id, 'answered_by' => $answeredBy, 'item_code' => 'MAIN_WORK_ITEMS'])->exists()) {
                    self::requireItemAnswers($e, $answeredBy, ['MAIN_WORK_ITEMS']);
                }
            } elseif ($type === PersonnelType::CODE_GOVT) {
                if (EvaluationAnswer::find()->joinWith('item')->where(['evaluation_id' => $e->id, 'answered_by' => $answeredBy, 'item_code' => 'GOVT_MAIN_ITEMS'])->exists()) {
                    self::requireItemAnswers($e, $answeredBy, ['GOVT_MAIN_ITEMS']);
                }
            } elseif ($type === PersonnelType::CODE_SPECIAL) {
                $specCodes = ['SPEC_1_1', 'SPEC_1_2', 'SPEC_1_3', 'SPEC_1_4', 'SPEC_1_5'];
                if (EvaluationAnswer::find()->joinWith('item')->where(['evaluation_id' => $e->id, 'answered_by' => $answeredBy, 'item_code' => 'SPEC_1_1'])->exists()) {
                    self::requireItemAnswers($e, $answeredBy, $specCodes);
                }
            }
        }

        // Evidence is mandatory only for required items or for optional items that were actually used.
        foreach ($items as $item) {
            if ((int)$item->requires_evidence !== 1) {
                continue;
            }
            $answer = $answers[$item->id] ?? null;
            if (!((int)$item->is_required === 1 || self::answerHasContent($answer))) {
                continue;
            }
            if (!EvidenceFile::find()->where(['evaluation_id' => $e->id, 'evaluation_item_id' => $item->id, 'deleted_at' => null])->exists()) {
                throw new InvalidArgumentException("กรุณาแนบหลักฐาน: {$item->name_th}");
            }
        }

        $comps = \common\models\CompetencyDefinition::find()->where(['template_version_id' => $e->template_version_id])->all();
        if ($comps) {
            $compAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $e->id, 'answered_by' => $answeredBy])->indexBy('competency_definition_id')->all();
            if ($compAnswers) {
                foreach ($compAnswers as $ca) {
                    self::validateLevel($ca->level_value);
                }
            }
        }
    }

    public static function validateStoredAnswer(EvaluationAnswer $answer, EvaluationItem $item): void
    {
        $type = (string)$item->input_type;
        if ($item->item_code === 'MAIN_WORK_ITEMS') {
            $rows = is_string($answer->json_value) ? json_decode($answer->json_value, true) : $answer->json_value;
            self::validateMainWork(is_array($rows) ? $rows : [], 80, false);
            return;
        }
        if ($item->item_code === 'GOVT_MAIN_ITEMS') {
            $rows = is_string($answer->json_value) ? json_decode($answer->json_value, true) : $answer->json_value;
            self::validateGovtMain(is_array($rows) ? $rows : [], 80, false, true);
            return;
        }
        if (in_array($type, ['pdca_level', 'radio_scale', 'score_direct'], true)) {
            self::validateNumeric($answer->numeric_value, 0, 100);
        } elseif ($type === 'govt_kpi_row') {
            $rows = is_string($answer->json_value) ? json_decode($answer->json_value, true) : $answer->json_value;
            if (!is_array($rows)) {
                throw new InvalidArgumentException("ข้อมูล {$item->name_th} ไม่ถูกต้อง");
            }
            self::validateGovtMain($rows, 80, false, false);
        } elseif ($type === 'checkbox_list') {
            $data = is_string($answer->json_value) ? json_decode($answer->json_value, true) : $answer->json_value;
            if ($data !== null && !is_array($data)) {
                throw new InvalidArgumentException("ข้อมูล {$item->name_th} ไม่ถูกต้อง");
            }
        } else {
            self::validateText($answer->text_value, 10000);
        }
    }

    public static function validateFinal(Evaluation $e): void
    {
        self::validateRequired($e, 'self');

        // Determine which evaluator tier holds the final evaluation answers
        $evaluatorSide = 'supervisor';
        if (EvaluationAnswer::find()->where(['evaluation_id' => $e->id, 'answered_by' => 'l2'])->exists() ||
            EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $e->id, 'answered_by' => 'l2'])->exists()) {
            $evaluatorSide = 'l2';
        } elseif (EvaluationAnswer::find()->where(['evaluation_id' => $e->id, 'answered_by' => 'l1'])->exists() ||
            EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $e->id, 'answered_by' => 'l1'])->exists()) {
            $evaluatorSide = 'l1';
        }

        self::validateRequired($e, $evaluatorSide);

        $answers = EvaluationAnswer::find()->where(['evaluation_id' => $e->id, 'answered_by' => $evaluatorSide])->all();
        foreach ($answers as $a) {
            if (!$a->item) {
                continue;
            }
            if ($a->item->item_code === 'MAIN_WORK_ITEMS') {
                $rows = is_string($a->json_value) ? json_decode($a->json_value, true) : $a->json_value;
                self::validateMainWork(is_array($rows) ? $rows : [], 80, true);
            } elseif ($a->item->item_code === 'GOVT_MAIN_ITEMS') {
                $rows = is_string($a->json_value) ? json_decode($a->json_value, true) : $a->json_value;
                self::validateGovtMain(is_array($rows) ? $rows : [], 80, true, true);
            }
        }

        // Any evidence-bearing item that has been used by either side must have evidence.
        $allItems = EvaluationItem::find()->joinWith('section')->where(['evaluation_sections.template_version_id' => $e->template_version_id, 'requires_evidence' => 1])->all();
        foreach ($allItems as $item) {
            $used = false;
            foreach (['self', $evaluatorSide] as $side) {
                $a = EvaluationAnswer::findOne(['evaluation_id' => $e->id, 'evaluation_item_id' => $item->id, 'answered_by' => $side]);
                if ((int)$item->is_required === 1 || self::answerHasContent($a)) {
                    $used = true;
                    break;
                }
            }
            if ($used && !EvidenceFile::find()->where(['evaluation_id' => $e->id, 'evaluation_item_id' => $item->id, 'deleted_at' => null])->exists()) {
                throw new InvalidArgumentException("กรุณาแนบหลักฐาน: {$item->name_th}");
            }
        }
    }
}
