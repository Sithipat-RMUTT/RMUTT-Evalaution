<?php

namespace common\services;

use Yii;
use common\models\Evaluation;
use common\models\EvaluationResult;
use common\models\EvaluationAnswer;
use common\models\EvaluationCompetencyAnswer;
use common\models\PersonnelType;

/**
 * EvaluationCalculatorService
 * 
 * Source of Truth Score Engine based on RMUTT ARIT Official Evaluation Rules.
 */
class EvaluationCalculatorService
{
    /**
     * Calculate score for an evaluation based on personnel type template
     *
     * @param Evaluation $evaluation
     * @param int|null $calculatedBy
     * @return EvaluationResult
     */
    public static function calculate(Evaluation $evaluation, $calculatedBy = null, $forceRecalculate = false)
    {
        // Result Immutability: Once finalized/approved, do not overwrite unless explicitly forced
        $isFinalized = in_array($evaluation->status, [Evaluation::STATUS_COMPLETED, 'director_approval', 'acknowledged'], true);
        if ($isFinalized && !$forceRecalculate && $evaluation->result) {
            return $evaluation->result;
        }

        $personnel = $evaluation->personnel;
        $typeCode = $personnel?->personnelType?->code ?: PersonnelType::CODE_CIVIL;

        $calcUser = $calculatedBy ?: (Yii::$app->user->id ?? null);
        if (!$calcUser || !\common\models\User::find()->where(['id' => $calcUser])->exists()) {
            $adminUser = \common\models\User::findOne(['username' => 'admin']) ?: \common\models\User::find()->one();
            $calcUser = $adminUser ? $adminUser->id : 1;
        }

        switch ($typeCode) {
            case PersonnelType::CODE_CIVIL:
            case PersonnelType::CODE_UNIVERSITY:
                return self::calculateCivilAndUniv($evaluation, $calcUser);

            case PersonnelType::CODE_GOVT:
                return self::calculateGovt($evaluation, $calcUser);

            case PersonnelType::CODE_SPECIAL:
                return self::calculateSpecial($evaluation, $calcUser);

            default:
                return self::calculateCivilAndUniv($evaluation, $calcUser);
        }
    }

    /**
     * Calculation for ข้าราชการ (CIVIL) and พนักงานมหาวิทยาลัย (UNIVERSITY)
     * CIVIL: Form 2 (70%) + Form 3 (30%) = 100%
     * UNIVERSITY: Form 2 (70%) + Form 3 (30%) = 100%
     */
    private static function calculateCivilAndUniv(Evaluation $evaluation, $calcUser)
    {
        $personnel = $evaluation->personnel;
        $typeCode = $personnel?->personnelType?->code ?: PersonnelType::CODE_CIVIL;
        $isUniv = ($typeCode === PersonnelType::CODE_UNIVERSITY);

        $perfWeight = 70.0;
        $compWeight = 30.0;

        $answers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id])->all();
        $compAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id])->all();
        $definedCompsCount = $evaluation->templateVersion ? count($evaluation->templateVersion->competencyDefinitions) : 0;

        // Tracks detection
        $hasL1 = !empty(array_filter($answers, fn($a) => $a->answered_by === 'l1')) || !empty(array_filter($compAnswers, fn($a) => $a->answered_by === 'l1'));
        $hasL2 = !empty(array_filter($answers, fn($a) => $a->answered_by === 'l2')) || !empty(array_filter($compAnswers, fn($a) => $a->answered_by === 'l2'));
        $hasLegacySup = !empty(array_filter($answers, fn($a) => $a->answered_by === 'supervisor')) || !empty(array_filter($compAnswers, fn($a) => $a->answered_by === 'supervisor'));

        // 1. Self Track
        $selfMain = self::calcCivilMainWork($answers, 'self');
        $selfSecPolicy = self::calcCivilSecPolicy($answers, 'self');
        $selfSecAcad = self::calcCivilSecAcad($answers, 'self');
        $selfForm2Points = $selfMain['weighted_sum'] + $selfSecPolicy + $selfSecAcad;
        $selfForm2Weighted = ($selfForm2Points / 100.0) * $perfWeight;
        $selfComp = self::calcCivilComp($compAnswers, $definedCompsCount, 'self', $compWeight);
        $selfFinal = min(100.0, $selfForm2Weighted + $selfComp['score']);

        // 2. L1 Track
        $l1Perf = null; $l1CompScore = null; $l1Total = null;
        if ($hasL1 || $hasLegacySup) {
            $l1Main = self::calcCivilMainWork($answers, 'l1');
            $l1SecPolicy = self::calcCivilSecPolicy($answers, 'l1');
            $l1SecAcad = self::calcCivilSecAcad($answers, 'l1');
            $l1Form2Points = $l1Main['weighted_sum'] + $l1SecPolicy + $l1SecAcad;
            $l1Perf = ($l1Form2Points / 100.0) * $perfWeight;
            $l1Comp = self::calcCivilComp($compAnswers, $definedCompsCount, 'l1', $compWeight);
            $l1CompScore = $l1Comp['score'];
            $l1Total = min(100.0, $l1Perf + $l1CompScore);
        }

        // 3. L2 Track
        $l2Perf = null; $l2CompScore = null; $l2Total = null;
        if ($hasL2) {
            $l2Main = self::calcCivilMainWork($answers, 'l2');
            $l2SecPolicy = self::calcCivilSecPolicy($answers, 'l2');
            $l2SecAcad = self::calcCivilSecAcad($answers, 'l2');
            $l2Form2Points = $l2Main['weighted_sum'] + $l2SecPolicy + $l2SecAcad;
            $l2Perf = ($l2Form2Points / 100.0) * $perfWeight;
            $l2Comp = self::calcCivilComp($compAnswers, $definedCompsCount, 'l2', $compWeight);
            $l2CompScore = $l2Comp['score'];
            $l2Total = min(100.0, $l2Perf + $l2CompScore);
        }

        // 4. Final / Active Supervisor Track
        $activeRole = $hasL2 ? 'l2' : (($hasL1 || $hasLegacySup) ? 'l1' : 'self');
        $supMain = self::calcCivilMainWork($answers, $activeRole);
        $supSecPolicy = self::calcCivilSecPolicy($answers, $activeRole);
        $supSecAcad = self::calcCivilSecAcad($answers, $activeRole);
        $supForm2Points = $supMain['weighted_sum'] + $supSecPolicy + $supSecAcad;
        $supForm2Weighted = ($supForm2Points / 100.0) * $perfWeight;
        $supComp = self::calcCivilComp($compAnswers, $definedCompsCount, $activeRole, $compWeight);
        $supFinal = min(100.0, $supForm2Weighted + $supComp['score']);

        $finalPercentage = round($supFinal, 2);
        $pType = $typeCode;
        $perfLevel = self::gradePerformanceLevel($finalPercentage, $pType);

        $details = [
            'type' => 'CIVIL_AND_UNIV',
            'form2' => [
                'main_work' => [
                    'self' => round($selfMain['weighted_sum'], 2),
                    'supervisor' => round($supMain['weighted_sum'], 2),
                    'items' => $supMain['items_detail'] ?: $selfMain['items_detail'],
                ],
                'secondary_policy' => [
                    'self' => round($selfSecPolicy, 2),
                    'supervisor' => round($supSecPolicy, 2),
                ],
                'secondary_academic' => [
                    'self' => round($selfSecAcad, 2),
                    'supervisor' => round($supSecAcad, 2),
                ],
                'total_form2_points' => [
                    'self' => round($selfForm2Points, 2),
                    'supervisor' => round($supForm2Points, 2),
                ],
                'total_form2_weighted' => [
                    'self' => round($selfForm2Weighted, 2),
                    'supervisor' => round($supForm2Weighted, 2),
                ],
            ],
            'form3_competency' => [
                'self_points' => $selfComp['points'],
                'supervisor_points' => $supComp['points'],
                'self_score' => round($selfComp['score'], 2),
                'supervisor_score' => round($supComp['score'], 2),
                'items' => $supComp['details'] ?: $selfComp['details'],
            ],
            'l1_score' => $l1Total !== null ? round($l1Total, 2) : null,
            'l2_score' => $l2Total !== null ? round($l2Total, 2) : null,
            'final_total' => $finalPercentage,
            'performance_level' => $perfLevel,
        ];

        return self::saveResult($evaluation, [
            'self_performance_score' => $selfForm2Weighted,
            'self_competency_score' => $selfComp['score'],
            'self_total_score' => $selfFinal,
            'l1_performance_score' => $l1Perf,
            'l1_competency_score' => $l1CompScore,
            'l1_total_score' => $l1Total,
            'l2_performance_score' => $l2Perf,
            'l2_competency_score' => $l2CompScore,
            'l2_total_score' => $l2Total,
            'supervisor_performance_score' => $supForm2Weighted,
            'supervisor_competency_score' => $supComp['score'],
            'supervisor_total_score' => $supFinal,
            'final_score' => $supFinal,
            'final_percentage' => $finalPercentage,
            'performance_level' => $perfLevel,
            'calculation_details' => $details,
            'calculated_by' => $calcUser,
        ]);
    }

    private static function calcCivilMainWork($answers, $role)
    {
        $targetAnswers = array_filter($answers, fn($a) => $a->item && $a->item->item_code === 'MAIN_WORK_ITEMS');
        $roleAns = null;
        if ($role === 'self') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'self') { $roleAns = $a; break; }
        } elseif ($role === 'l1') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'l1') { $roleAns = $a; break; }
            if (!$roleAns) { foreach ($targetAnswers as $a) if ($a->answered_by === 'supervisor') { $roleAns = $a; break; } }
        } elseif ($role === 'l2') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'l2') { $roleAns = $a; break; }
            if (!$roleAns) { foreach ($targetAnswers as $a) if ($a->answered_by === 'supervisor') { $roleAns = $a; break; } }
            if (!$roleAns) { foreach ($targetAnswers as $a) if ($a->answered_by === 'l1') { $roleAns = $a; break; } }
        }
        if (!$roleAns && $role !== 'self') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'self') { $roleAns = $a; break; }
        }

        if (!$roleAns || empty($roleAns->json_value)) {
            return ['weighted_sum' => 0.0, 'items_detail' => []];
        }

        $rows = is_string($roleAns->json_value) ? json_decode($roleAns->json_value, true) : $roleAns->json_value;
        if (!is_array($rows)) return ['weighted_sum' => 0.0, 'items_detail' => []];

        $sum = 0.0;
        $details = [];
        foreach ($rows as $r) {
            $weight = floatval($r['weight'] ?? 0);
            $selfPdca = floatval($r['self_score'] ?? 0);
            $supPdca = floatval($r['supervisor_score'] ?? $selfPdca);
            $pdca = ($role === 'self') ? $selfPdca : $supPdca;
            $weighted = ($pdca * $weight) / 5.0;
            $sum += $weighted;
            $details[] = [
                'title' => $r['title'] ?? '',
                'kpi' => $r['kpi'] ?? '',
                'weight' => $weight,
                'self_pdca' => $selfPdca,
                'sup_pdca' => $supPdca,
                'self_weighted' => ($selfPdca * $weight) / 5.0,
                'sup_weighted' => ($supPdca * $weight) / 5.0,
            ];
        }
        return ['weighted_sum' => $sum, 'items_detail' => $details];
    }

    private static function calcCivilSecPolicy($answers, $role)
    {
        $targetAnswers = array_filter($answers, fn($a) => $a->item && $a->item->item_code === 'SEC_POLICY_CHECKLIST');
        $roleAns = null;
        if ($role === 'self') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'self') { $roleAns = $a; break; }
        } elseif ($role === 'l1') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'l1') { $roleAns = $a; break; }
            if (!$roleAns) { foreach ($targetAnswers as $a) if ($a->answered_by === 'supervisor') { $roleAns = $a; break; } }
        } elseif ($role === 'l2') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'l2') { $roleAns = $a; break; }
            if (!$roleAns) { foreach ($targetAnswers as $a) if ($a->answered_by === 'supervisor') { $roleAns = $a; break; } }
            if (!$roleAns) { foreach ($targetAnswers as $a) if ($a->answered_by === 'l1') { $roleAns = $a; break; } }
        }
        if (!$roleAns && $role !== 'self') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'self') { $roleAns = $a; break; }
        }
        if (!$roleAns) return 0.0;
        $selected = is_string($roleAns->json_value) ? json_decode($roleAns->json_value, true) : $roleAns->json_value;
        $count = is_array($selected) ? count($selected) : 0;
        $score = self::gradePolicyCount($count);
        return ($score / 5.0) * 15.0;
    }

    private static function calcCivilSecAcad($answers, $role)
    {
        $targetAnswers = array_filter($answers, fn($a) => $a->item && $a->item->item_code === 'SEC_ACADEMIC_PROGRESS');
        $roleAns = null;
        if ($role === 'self') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'self') { $roleAns = $a; break; }
        } elseif ($role === 'l1') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'l1') { $roleAns = $a; break; }
            if (!$roleAns) { foreach ($targetAnswers as $a) if ($a->answered_by === 'supervisor') { $roleAns = $a; break; } }
        } elseif ($role === 'l2') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'l2') { $roleAns = $a; break; }
            if (!$roleAns) { foreach ($targetAnswers as $a) if ($a->answered_by === 'supervisor') { $roleAns = $a; break; } }
            if (!$roleAns) { foreach ($targetAnswers as $a) if ($a->answered_by === 'l1') { $roleAns = $a; break; } }
        }
        if (!$roleAns && $role !== 'self') {
            foreach ($targetAnswers as $a) if ($a->answered_by === 'self') { $roleAns = $a; break; }
        }
        if (!$roleAns) return 0.0;
        $level = floatval($roleAns->numeric_value ?? 0);
        return ($level / 5.0) * 5.0;
    }

    private static function calcCivilComp($compAnswers, $definedCompsCount, $role, $compWeight = 30.0)
    {
        $roleCompAnswers = array_filter($compAnswers, fn($a) => $a->answered_by === $role);
        if (empty($roleCompAnswers) && $role === 'l1') {
            $roleCompAnswers = array_filter($compAnswers, fn($a) => $a->answered_by === 'supervisor');
        } elseif (empty($roleCompAnswers) && $role === 'l2') {
            $roleCompAnswers = array_filter($compAnswers, fn($a) => $a->answered_by === 'supervisor');
            if (empty($roleCompAnswers)) {
                $roleCompAnswers = array_filter($compAnswers, fn($a) => $a->answered_by === 'l1');
            }
        }
        if (empty($roleCompAnswers) && $role !== 'self') {
            $roleCompAnswers = array_filter($compAnswers, fn($a) => $a->answered_by === 'self');
        }

        $compCount = $definedCompsCount > 0 ? $definedCompsCount : (count($roleCompAnswers) > 0 ? count($roleCompAnswers) : 7);
        $totalPoints = 0.0;
        $details = [];

        foreach ($roleCompAnswers as $ca) {
            $expected = $ca->competencyDefinition ? ($ca->competencyDefinition->expected_level ?? 3) : 3;
            $level = $ca->level_value;
            $points = self::gradeCompetencyGap($level, $expected);
            $totalPoints += $points;
            $details[] = [
                'code' => $ca->competencyDefinition ? $ca->competencyDefinition->competency_code : '',
                'name' => $ca->competencyDefinition ? $ca->competencyDefinition->name_th : '',
                'expected' => $expected,
                'actual' => $level,
                'points' => $points,
            ];
        }

        $maxPossible = $compCount * 3.0;
        $score = $maxPossible > 0 ? ($totalPoints / $maxPossible) * $compWeight : 0.0;

        return [
            'points' => $totalPoints,
            'score' => $score,
            'details' => $details,
        ];
    }

    /**
     * Calculation for พนักงานราชการ (GOVT)
     * Performance (80%) + Behavior (20%) = 100%
     */
    private static function calculateGovt(Evaluation $evaluation, $calcUser)
    {
        $answers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id])->all();
        $compAnswers = EvaluationCompetencyAnswer::find()->where(['evaluation_id' => $evaluation->id])->all();

        $calcTrack = function($role) use ($answers, $compAnswers) {
            // Main work
            $mainSum = 0.0;
            $targetMain = array_filter($answers, fn($a) => $a->item && $a->item->item_code === 'GOVT_MAIN_ITEMS');
            $mainAns = null;
            if ($role === 'self') {
                foreach ($targetMain as $a) if ($a->answered_by === 'self') { $mainAns = $a; break; }
            } elseif ($role === 'l1') {
                foreach ($targetMain as $a) if ($a->answered_by === 'l1') { $mainAns = $a; break; }
                if (!$mainAns) { foreach ($targetMain as $a) if ($a->answered_by === 'supervisor') { $mainAns = $a; break; } }
            } elseif ($role === 'l2') {
                foreach ($targetMain as $a) if ($a->answered_by === 'l2') { $mainAns = $a; break; }
                if (!$mainAns) { foreach ($targetMain as $a) if ($a->answered_by === 'supervisor') { $mainAns = $a; break; } }
                if (!$mainAns) { foreach ($targetMain as $a) if ($a->answered_by === 'l1') { $mainAns = $a; break; } }
            }
            if (!$mainAns && $role !== 'self') {
                foreach ($targetMain as $a) if ($a->answered_by === 'self') { $mainAns = $a; break; }
            }
            if ($mainAns && !empty($mainAns->json_value)) {
                $rows = is_string($mainAns->json_value) ? json_decode($mainAns->json_value, true) : $mainAns->json_value;
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $weight = floatval($r['weight'] ?? 0);
                        $kpi1 = floatval($r['kpi_volume'] ?? 5);
                        $kpi2 = floatval($r['kpi_quality'] ?? 5);
                        $kpi3 = floatval($r['kpi_timeliness'] ?? 5);
                        $kpi4 = floatval($r['kpi_resource'] ?? 5);
                        $itemScore = (($kpi1 * 25) + ($kpi2 * 25) + ($kpi3 * 15) + ($kpi4 * 15)) / 80.0;
                        $mainSum += ($itemScore / 5.0) * $weight;
                    }
                }
            }

            // Secondary work
            $secScore = 0.0;
            $targetSec = array_filter($answers, fn($a) => $a->item && $a->item->item_code === 'GOVT_SECONDARY_CHECKLIST');
            $secAns = null;
            if ($role === 'self') {
                foreach ($targetSec as $a) if ($a->answered_by === 'self') { $secAns = $a; break; }
            } elseif ($role === 'l1') {
                foreach ($targetSec as $a) if ($a->answered_by === 'l1') { $secAns = $a; break; }
                if (!$secAns) { foreach ($targetSec as $a) if ($a->answered_by === 'supervisor') { $secAns = $a; break; } }
            } elseif ($role === 'l2') {
                foreach ($targetSec as $a) if ($a->answered_by === 'l2') { $secAns = $a; break; }
                if (!$secAns) { foreach ($targetSec as $a) if ($a->answered_by === 'supervisor') { $secAns = $a; break; } }
                if (!$secAns) { foreach ($targetSec as $a) if ($a->answered_by === 'l1') { $secAns = $a; break; } }
            }
            if (!$secAns && $role !== 'self') {
                foreach ($targetSec as $a) if ($a->answered_by === 'self') { $secAns = $a; break; }
            }
            if ($secAns && !empty($secAns->json_value)) {
                $selectedData = is_string($secAns->json_value) ? json_decode($secAns->json_value, true) : $secAns->json_value;
                if (isset($selectedData['kpi_volume'])) {
                    $k1 = floatval($selectedData['kpi_volume'] ?? 5);
                    $k2 = floatval($selectedData['kpi_quality'] ?? 5);
                    $k3 = floatval($selectedData['kpi_timeliness'] ?? 5);
                    $k4 = floatval($selectedData['kpi_resource'] ?? 5);
                    $pts = (($k1 * 25) + ($k2 * 25) + ($k3 * 15) + ($k4 * 15)) / 80.0;
                    $secScore = ($pts / 5.0) * 20.0;
                } else {
                    $count = isset($selectedData['selected']) && is_array($selectedData['selected']) ? count($selectedData['selected']) : (is_array($selectedData) ? count($selectedData) : 0);
                    $pts = self::gradeGovtSecondaryCount($count);
                    $secScore = ($pts / 5.0) * 20.0;
                }
            }

            // Competency / Behavior
            $behSum = 0.0;
            $targetComp = array_filter($compAnswers, fn($a) => $a->answered_by === $role);
            if (empty($targetComp) && $role === 'l1') {
                $targetComp = array_filter($compAnswers, fn($a) => $a->answered_by === 'supervisor');
            } elseif (empty($targetComp) && $role === 'l2') {
                $targetComp = array_filter($compAnswers, fn($a) => $a->answered_by === 'supervisor');
                if (empty($targetComp)) $targetComp = array_filter($compAnswers, fn($a) => $a->answered_by === 'l1');
            }
            if (empty($targetComp) && $role !== 'self') {
                $targetComp = array_filter($compAnswers, fn($a) => $a->answered_by === 'self');
            }
            foreach ($targetComp as $ca) {
                $behSum += floatval($ca->level_value);
            }
            // Performance Section (Max 100 points: Main Work max 80 + Secondary Work max 20)
            $perfScore = $mainSum + $secScore; // Max 100.0
            $perfWeighted = ($perfScore / 100.0) * 70.0; // 70% weight in Section 4

            // Behavior / Competency Section (5 competencies rated 1-5, sum max 25)
            $behScore = ($behSum / 25.0) * 100.0; // Max 100.0
            $behWeighted = ($behSum / 25.0) * 30.0; // 30% weight in Section 4

            // Section 4 Total = Performance (70%) + Behavior (30%) = Max 100.00%
            $totalScore = $perfWeighted + $behWeighted;

            return [
                'main_sum' => $mainSum,           // เต็ม 80
                'sec_score' => $secScore,         // เต็ม 20
                'perf_score' => $perfScore,       // เต็ม 100 (ส่วนที่ ๒)
                'perf_weighted' => $perfWeighted, // ถ่วงน้ำหนัก 70% (ส่วนที่ ๔)
                'beh_raw' => $behSum,             // รวมระดับ 5 ด้าน (เต็ม 25)
                'beh_score' => $behScore,         // เต็ม 100 (ส่วนที่ ๓)
                'beh_weighted' => $behWeighted,   // ถ่วงน้ำหนัก 30% (ส่วนที่ ๔)
                'total' => $totalScore,           // รวมคะแนนสุทธิ 100%
            ];
        };

        $selfTrack = $calcTrack('self');

        $hasL1 = !empty(array_filter($answers, fn($a) => $a->answered_by === 'l1')) || !empty(array_filter($compAnswers, fn($a) => $a->answered_by === 'l1'));
        $hasL2 = !empty(array_filter($answers, fn($a) => $a->answered_by === 'l2')) || !empty(array_filter($compAnswers, fn($a) => $a->answered_by === 'l2'));
        $hasLegacySup = !empty(array_filter($answers, fn($a) => $a->answered_by === 'supervisor')) || !empty(array_filter($compAnswers, fn($a) => $a->answered_by === 'supervisor'));

        $l1Track = ($hasL1 || $hasLegacySup) ? $calcTrack('l1') : null;
        $l2Track = $hasL2 ? $calcTrack('l2') : null;

        $activeRole = $hasL2 ? 'l2' : (($hasL1 || $hasLegacySup) ? 'l1' : 'self');
        $supTrack = $calcTrack($activeRole);

        $finalPercentage = round($supTrack['total'], 2);
        $perfLevel = self::gradePerformanceLevel($finalPercentage, PersonnelType::CODE_GOVT);

        $details = [
            'type' => 'GOVT',
            'performance' => [
                'main_work' => ['self' => round($selfTrack['main_sum'], 2), 'supervisor' => round($supTrack['main_sum'], 2)],
                'secondary_work' => ['self' => round($selfTrack['sec_score'], 2), 'supervisor' => round($supTrack['sec_score'], 2)],
                'total_perf' => ['self' => round($selfTrack['perf_score'], 2), 'supervisor' => round($supTrack['perf_score'], 2)],
                'weighted_perf' => ['self' => round($selfTrack['perf_weighted'], 2), 'supervisor' => round($supTrack['perf_weighted'], 2)],
            ],
            'behavior' => [
                'raw_sum' => ['self' => round($selfTrack['beh_raw'], 2), 'supervisor' => round($supTrack['beh_raw'], 2)],
                'score_100' => ['self' => round($selfTrack['beh_score'], 2), 'supervisor' => round($supTrack['beh_score'], 2)],
                'weighted_beh' => ['self' => round($selfTrack['beh_weighted'], 2), 'supervisor' => round($supTrack['beh_weighted'], 2)],
            ],
            'summary' => [
                'perf_weight' => 70.0,
                'beh_weight' => 30.0,
                'self_total' => round($selfTrack['total'], 2),
                'supervisor_total' => round($supTrack['total'], 2),
            ],
            'l1_score' => $l1Track ? round($l1Track['total'], 2) : null,
            'l2_score' => $l2Track ? round($l2Track['total'], 2) : null,
            'final_total' => $finalPercentage,
            'performance_level' => $perfLevel,
        ];

        return self::saveResult($evaluation, [
            'self_performance_score' => $selfTrack['perf_weighted'],
            'self_competency_score' => $selfTrack['beh_weighted'],
            'self_total_score' => $selfTrack['total'],
            'l1_performance_score' => $l1Track ? $l1Track['perf_weighted'] : null,
            'l1_competency_score' => $l1Track ? $l1Track['beh_weighted'] : null,
            'l1_total_score' => $l1Track ? $l1Track['total'] : null,
            'l2_performance_score' => $l2Track ? $l2Track['perf_weighted'] : null,
            'l2_competency_score' => $l2Track ? $l2Track['beh_weighted'] : null,
            'l2_total_score' => $l2Track ? $l2Track['total'] : null,
            'supervisor_performance_score' => $supTrack['perf_weighted'],
            'supervisor_competency_score' => $supTrack['beh_weighted'],
            'supervisor_total_score' => $supTrack['total'],
            'final_score' => $supTrack['total'],
            'final_percentage' => $finalPercentage,
            'performance_level' => $perfLevel,
            'calculation_details' => $details,
            'calculated_by' => $calcUser,
        ]);
    }

    /**
     * Calculation for พนักงานพิเศษเงินรายได้ (SPECIAL)
     * Performance (55 points) + Characteristics (45 points) = 100 points
     */
    private static function calculateSpecial(Evaluation $evaluation, $calcUser)
    {
        $answers = EvaluationAnswer::find()->where(['evaluation_id' => $evaluation->id])->all();

        $calcTrack = function($role) use ($answers) {
            $perfSum = 0.0;
            $charSum = 0.0;
            $roleAnswers = array_filter($answers, fn($a) => $a->answered_by === $role);
            if (empty($roleAnswers) && $role === 'l1') {
                $roleAnswers = array_filter($answers, fn($a) => $a->answered_by === 'supervisor');
            } elseif (empty($roleAnswers) && $role === 'l2') {
                $roleAnswers = array_filter($answers, fn($a) => $a->answered_by === 'supervisor');
                if (empty($roleAnswers)) $roleAnswers = array_filter($answers, fn($a) => $a->answered_by === 'l1');
            }
            if (empty($roleAnswers) && $role !== 'self') {
                $roleAnswers = array_filter($answers, fn($a) => $a->answered_by === 'self');
            }

            foreach ($roleAnswers as $ans) {
                if (!$ans->item) continue;
                $code = $ans->item->item_code;
                $val = floatval($ans->numeric_value ?? 0);

                if ($code === 'SPEC_1_6_SECONDARY') {
                    $selectedData = is_string($ans->json_value) ? json_decode($ans->json_value, true) : $ans->json_value;
                    $count = isset($selectedData['selected']) && is_array($selectedData['selected']) ? count($selectedData['selected']) : (is_array($selectedData) ? count($selectedData) : 0);
                    $val = self::gradeGovtSecondaryCount($count);
                }

                if (strpos($code, 'SPEC_1_') === 0) {
                    $perfSum += $val;
                } elseif (strpos($code, 'SPEC_2_') === 0) {
                    $charSum += $val;
                }
            }

            return [
                'perf_sum' => $perfSum,
                'char_sum' => $charSum,
                'total' => $perfSum + $charSum,
            ];
        };

        $selfTrack = $calcTrack('self');

        $hasL1 = !empty(array_filter($answers, fn($a) => $a->answered_by === 'l1'));
        $hasL2 = !empty(array_filter($answers, fn($a) => $a->answered_by === 'l2'));
        $hasLegacySup = !empty(array_filter($answers, fn($a) => $a->answered_by === 'supervisor'));

        $l1Track = ($hasL1 || $hasLegacySup) ? $calcTrack('l1') : null;
        $l2Track = $hasL2 ? $calcTrack('l2') : null;

        $activeRole = $hasL2 ? 'l2' : (($hasL1 || $hasLegacySup) ? 'l1' : 'self');
        $supTrack = $calcTrack($activeRole);

        $finalPercentage = round($supTrack['total'], 2);
        $perfLevel = self::gradePerformanceLevel($finalPercentage, PersonnelType::CODE_SPECIAL);

        $details = [
            'type' => 'SPECIAL',
            'performance_score' => ['self' => round($selfTrack['perf_sum'], 2), 'supervisor' => round($supTrack['perf_sum'], 2)],
            'characteristics_score' => ['self' => round($selfTrack['char_sum'], 2), 'supervisor' => round($supTrack['char_sum'], 2)],
            'l1_score' => $l1Track ? round($l1Track['total'], 2) : null,
            'l2_score' => $l2Track ? round($l2Track['total'], 2) : null,
            'final_total' => $finalPercentage,
            'performance_level' => $perfLevel,
        ];

        return self::saveResult($evaluation, [
            'self_performance_score' => $selfTrack['perf_sum'],
            'self_competency_score' => $selfTrack['char_sum'],
            'self_total_score' => $selfTrack['total'],
            'l1_performance_score' => $l1Track ? $l1Track['perf_sum'] : null,
            'l1_competency_score' => $l1Track ? $l1Track['char_sum'] : null,
            'l1_total_score' => $l1Track ? $l1Track['total'] : null,
            'l2_performance_score' => $l2Track ? $l2Track['perf_sum'] : null,
            'l2_competency_score' => $l2Track ? $l2Track['char_sum'] : null,
            'l2_total_score' => $l2Track ? $l2Track['total'] : null,
            'supervisor_performance_score' => $supTrack['perf_sum'],
            'supervisor_competency_score' => $supTrack['char_sum'],
            'supervisor_total_score' => $supTrack['total'],
            'final_score' => $supTrack['total'],
            'final_percentage' => $finalPercentage,
            'performance_level' => $perfLevel,
            'calculation_details' => $details,
            'calculated_by' => $calcUser,
        ]);
    }

    /**
     * Helper: Competency GAP score rule
     */
    public static function gradeCompetencyGap($actualLevel, $expectedLevel)
    {
        $diff = $actualLevel - $expectedLevel;
        if ($diff >= 0) {
            return 3.0; // >= expected
        } elseif ($diff == -1) {
            return 2.0; // 1 level below
        } elseif ($diff == -2) {
            return 1.0; // 2 levels below
        } else {
            return 0.0; // 3+ levels below
        }
    }

    /**
     * Helper: Secondary work 5.1 Policy score rule
     * 3-5 items = 5, 2 items = 3, 1 item = 1, 0 = 0
     */
    public static function gradePolicyCount($count)
    {
        if ($count >= 3) return 5.0;
        if ($count == 2) return 3.0;
        if ($count == 1) return 1.0;
        return 0.0;
    }

    /**
     * Helper: Government Employee / Special Employee secondary count rule
     * 6-10 = 5, 5 = 4, 3-4 = 3, 2 = 2, 1 = 1
     */
    public static function gradeGovtSecondaryCount($count)
    {
        if ($count >= 6) return 5.0;
        if ($count == 5) return 4.0;
        if ($count >= 3) return 3.0;
        if ($count == 2) return 2.0;
        if ($count == 1) return 1.0;
        return 0.0;
    }

    /**
     * Helper: Grade Performance Level according to official personnel type regulation
     * - CIVIL / UNIVERSITY: 90-100% ดีเด่น, 80-89.99% ดีมาก, 70-79.99% ดี, 60-69.99% พอใช้, <60% ต้องปรับปรุง
     * - GOVT: 95-100% ดีเด่น, 85-94.99% ดีมาก, 75-84.99% ดี, 65-74.99% พอใช้, <65% ต้องปรับปรุง
     * - SPECIAL: 90-100% ดีมาก, 80-89.99% ดี, 70-79.99% พอใช้, 60-69.99% ปรับปรุง, <60% ไม่ผ่าน
     */
    public static function gradePerformanceLevel($percentage, $typeCode = PersonnelType::CODE_UNIVERSITY)
    {
        if ($typeCode === PersonnelType::CODE_GOVT) {
            if ($percentage >= 95.0) return 'ดีเด่น';
            if ($percentage >= 85.0) return 'ดีมาก';
            if ($percentage >= 75.0) return 'ดี';
            if ($percentage >= 65.0) return 'พอใช้';
            return 'ต้องปรับปรุง';
        }

        if ($typeCode === PersonnelType::CODE_SPECIAL) {
            if ($percentage >= 90.0) return 'ดีมาก';
            if ($percentage >= 80.0) return 'ดี';
            if ($percentage >= 70.0) return 'พอใช้';
            if ($percentage >= 60.0) return 'ปรับปรุง';
            return 'ไม่ผ่าน';
        }

        // CIVIL and UNIVERSITY
        if ($percentage >= 90.0) return 'ดีเด่น';
        if ($percentage >= 80.0) return 'ดีมาก';
        if ($percentage >= 70.0) return 'ดี';
        if ($percentage >= 60.0) return 'พอใช้';
        return 'ต้องปรับปรุง';
    }

    /**
     * Helper: Save or Update Evaluation Result
     */
    private static function saveResult(Evaluation $evaluation, array $data)
    {
        $result = EvaluationResult::findOne(['evaluation_id' => $evaluation->id]);
        if (!$result) {
            $result = new EvaluationResult();
            $result->evaluation_id = $evaluation->id;
            $result->template_version_id = $evaluation->template_version_id;
        }

        $result->self_performance_score = $data['self_performance_score'];
        $result->self_competency_score = $data['self_competency_score'];
        $result->self_total_score = $data['self_total_score'];

        $result->l1_performance_score = $data['l1_performance_score'] ?? null;
        $result->l1_competency_score = $data['l1_competency_score'] ?? null;
        $result->l1_total_score = $data['l1_total_score'] ?? null;

        $result->l2_performance_score = $data['l2_performance_score'] ?? null;
        $result->l2_competency_score = $data['l2_competency_score'] ?? null;
        $result->l2_total_score = $data['l2_total_score'] ?? null;

        $result->supervisor_performance_score = $data['supervisor_performance_score'];
        $result->supervisor_competency_score = $data['supervisor_competency_score'];
        $result->supervisor_total_score = $data['supervisor_total_score'];
        $result->final_score = $data['final_score'];
        $result->final_percentage = $data['final_percentage'];
        $result->performance_level = $data['performance_level'];
        $result->calculation_details = $data['calculation_details'];
        $result->calculated_by = $data['calculated_by'];
        $result->calculated_at = date('Y-m-d H:i:s');
        $result->save(false);

        return $result;
    }
}
