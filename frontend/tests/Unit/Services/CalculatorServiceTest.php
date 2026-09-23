<?php

namespace frontend\tests\Unit\Services;

use Codeception\Test\Unit;
use common\services\EvaluationCalculatorService;

class CalculatorServiceTest extends Unit
{
    public function testGradePerformanceLevels()
    {
        $this->assertEquals('ดีเด่น', EvaluationCalculatorService::gradePerformanceLevel(100.0));
        $this->assertEquals('ดีเด่น', EvaluationCalculatorService::gradePerformanceLevel(95.0));
        $this->assertEquals('ดีมาก', EvaluationCalculatorService::gradePerformanceLevel(94.99));
        $this->assertEquals('ดีมาก', EvaluationCalculatorService::gradePerformanceLevel(85.0));
        $this->assertEquals('ดี', EvaluationCalculatorService::gradePerformanceLevel(84.99));
        $this->assertEquals('ดี', EvaluationCalculatorService::gradePerformanceLevel(75.0));
        $this->assertEquals('พอใช้', EvaluationCalculatorService::gradePerformanceLevel(74.99));
        $this->assertEquals('พอใช้', EvaluationCalculatorService::gradePerformanceLevel(65.0));
        $this->assertEquals('ต้องปรับปรุง', EvaluationCalculatorService::gradePerformanceLevel(64.99));
        $this->assertEquals('ต้องปรับปรุง', EvaluationCalculatorService::gradePerformanceLevel(50.0));
        $this->assertEquals('ต้องปรับปรุง', EvaluationCalculatorService::gradePerformanceLevel(0.0));
    }

    public function testGradeCompetencyGap()
    {
        $this->assertEquals(3, EvaluationCalculatorService::gradeCompetencyGap(5, 3));
        $this->assertEquals(3, EvaluationCalculatorService::gradeCompetencyGap(3, 3));
        $this->assertEquals(2, EvaluationCalculatorService::gradeCompetencyGap(2, 3));
        $this->assertEquals(1, EvaluationCalculatorService::gradeCompetencyGap(1, 3));
        $this->assertEquals(0, EvaluationCalculatorService::gradeCompetencyGap(1, 4));
        $this->assertEquals(0, EvaluationCalculatorService::gradeCompetencyGap(1, 5));
    }

    public function testGradePolicyCount()
    {
        $this->assertEquals(5, EvaluationCalculatorService::gradePolicyCount(5));
        $this->assertEquals(5, EvaluationCalculatorService::gradePolicyCount(3));
        $this->assertEquals(3, EvaluationCalculatorService::gradePolicyCount(2));
        $this->assertEquals(1, EvaluationCalculatorService::gradePolicyCount(1));
        $this->assertEquals(0, EvaluationCalculatorService::gradePolicyCount(0));
    }

    public function testGradeGovtSecondaryCount()
    {
        $this->assertEquals(5, EvaluationCalculatorService::gradeGovtSecondaryCount(6));
        $this->assertEquals(5, EvaluationCalculatorService::gradeGovtSecondaryCount(10));
        $this->assertEquals(4, EvaluationCalculatorService::gradeGovtSecondaryCount(5));
        $this->assertEquals(3, EvaluationCalculatorService::gradeGovtSecondaryCount(4));
        $this->assertEquals(3, EvaluationCalculatorService::gradeGovtSecondaryCount(3));
        $this->assertEquals(2, EvaluationCalculatorService::gradeGovtSecondaryCount(2));
        $this->assertEquals(1, EvaluationCalculatorService::gradeGovtSecondaryCount(1));
        $this->assertEquals(0, EvaluationCalculatorService::gradeGovtSecondaryCount(0));
    }
}