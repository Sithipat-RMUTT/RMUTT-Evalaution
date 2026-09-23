<?php

namespace frontend\tests\Unit\Security;

use Codeception\Test\Unit;
use common\models\EvaluationCycle;
use common\models\Evaluation;
use common\models\Personnel;
use common\services\EvaluationAccessService;

class SecurityAccessTest extends Unit
{
    public function testCycleDateValidationRejectsInvalidOrder()
    {
        $cycle = new EvaluationCycle();
        $cycle->name_th = 'รอบทดสอบความปลอดภัย';
        $cycle->cycle_number = 1;
        $cycle->fiscal_year = 2569;
        $cycle->period_start = '2026-10-01';
        $cycle->period_end = '2026-09-01';
        $cycle->self_assessment_start = '2026-10-01 08:30:00';
        $cycle->self_assessment_end = '2026-09-15 16:30:00';
        $cycle->supervisor_eval_start = '2026-11-01 08:30:00';
        $cycle->supervisor_eval_end = '2026-10-15 16:30:00';

        $valid = $cycle->validate();
        $this->assertFalse($valid, 'Cycle with invalid date ordering must fail validation');
        $this->assertTrue($cycle->hasErrors('period_end'));
        $this->assertTrue($cycle->hasErrors('self_assessment_end'));
        $this->assertTrue($cycle->hasErrors('supervisor_eval_end'));
    }

    public function testSnapshotEvaluatorAccessIntegrity()
    {
        $eval = new Evaluation();
        $eval->personnel_id = 10;
        $eval->evaluator_id = 5;
        $eval->evaluator_l1_id = 5;
        $eval->evaluator_l2_id = 8;

        $pEvaluator = new Personnel();
        $pEvaluator->id = 5;

        $pOther = new Personnel();
        $pOther->id = 99;

        $this->assertTrue(EvaluationAccessService::isEvaluator($eval, $pEvaluator));
        $this->assertFalse(EvaluationAccessService::isEvaluator($eval, $pOther));
    }
}