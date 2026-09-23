<?php
namespace common\services;

use Yii;
use yii\web\ForbiddenHttpException;
use common\models\Evaluation;
use common\models\Personnel;

final class EvaluationAccessService
{
    public static function personnel(): Personnel
    {
        $p = Personnel::findOne(['user_id' => Yii::$app->user->id]);
        if (!$p) {
            throw new ForbiddenHttpException('ไม่พบบัญชีบุคลากรที่เชื่อมโยงกับผู้ใช้งานนี้');
        }
        return $p;
    }

    public static function isAdmin(): bool
    {
        return Yii::$app->user->can('admin') || Yii::$app->user->can('superadmin');
    }

    public static function isOwner(Evaluation $e, ?Personnel $p): bool
    {
        return $p !== null && (int)$e->personnel_id === (int)$p->id;
    }

    public static function isEvaluator(Evaluation $e, ?Personnel $p): bool
    {
        if ($p === null) {
            return false;
        }

        // Direct snapshot check (Evaluator, L1, or L2)
        if ($e->evaluator_id !== null && (int)$e->evaluator_id === (int)$p->id) {
            return true;
        }
        if (!empty($e->evaluator_l1_id) && (int)$e->evaluator_l1_id === (int)$p->id) {
            return true;
        }
        if (!empty($e->evaluator_l2_id) && (int)$e->evaluator_l2_id === (int)$p->id) {
            return true;
        }

        // Check if L1 and L2 are the same evaluator or have matching full names
        if ($e->evaluatorL1 && $e->evaluatorL2 && $e->evaluatorL1->fullName === $e->evaluatorL2->fullName) {
            if ((int)$e->evaluator_l1_id === (int)$p->id || (int)$e->evaluator_l2_id === (int)$p->id) {
                return true;
            }
        }

        // Fallback to hierarchy ONLY if snapshot evaluators are completely unassigned
        if ($e->evaluator_id === null && empty($e->evaluator_l1_id) && empty($e->evaluator_l2_id) && $e->personnel) {
            if ($e->personnel->supervisor_id !== null && (int)$e->personnel->supervisor_id === (int)$p->id) {
                return true;
            }
            if (!empty($e->personnel->division_head_id) && (int)$e->personnel->division_head_id === (int)$p->id) {
                return true;
            }
        }

        return false;
    }

    public static function assertOwner(Evaluation $e, ?Personnel $p): void
    {
        if (!self::isOwner($e, $p) && !self::isAdmin()) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์เข้าถึงแบบประเมินนี้');
        }
    }

    public static function assertEvaluator(Evaluation $e, ?Personnel $p): void
    {
        if (!self::isEvaluator($e, $p) && !self::isAdmin()) {
            throw new ForbiddenHttpException('คุณไม่มีสิทธิ์ประเมินบุคลากรท่านนี้');
        }
    }

    public static function assertCanEditSelf(Evaluation $e, ?Personnel $p): void
    {
        self::assertOwner($e, $p);
        if (!in_array($e->status, [Evaluation::STATUS_DRAFT, Evaluation::STATUS_SELF_ASSESSMENT, Evaluation::STATUS_RETURNED], true)) {
            throw new ForbiddenHttpException('แบบประเมินนี้ไม่อยู่ในสถานะที่สามารถแก้ไขได้');
        }
    }

    public static function assertCanEditSupervisor(Evaluation $e, ?Personnel $p): void
    {
        self::assertEvaluator($e, $p);
        $allowed = [
            Evaluation::STATUS_SUBMITTED,
            Evaluation::STATUS_SUBMITTED_L1,
            Evaluation::STATUS_SUBMITTED_L2,
            Evaluation::STATUS_SUPERVISOR_REVIEW,
        ];
        if (!in_array($e->status, $allowed, true)) {
            throw new ForbiddenHttpException('แบบประเมินนี้ไม่อยู่ในสถานะที่สามารถประเมินได้');
        }
    }
}
