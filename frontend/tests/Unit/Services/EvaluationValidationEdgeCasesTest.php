<?php
declare(strict_types=1);
namespace frontend\tests\Unit\Services;

use Codeception\Test\Unit;
use common\services\EvaluationValidationService;
use yii\base\InvalidArgumentException;

final class EvaluationValidationEdgeCasesTest extends Unit
{
    private function expectThrowable(string $class, callable $fn): void
    {
        try {
            $fn();
            $this->fail("Expected {$class} to be thrown");
        } catch (\Throwable $e) {
            $this->assertInstanceOf($class, $e);
        }
    }

    public function testSupervisorScoreIsRequiredForFinalRows(): void
    {
        $rows = [['title' => 'งานหลัก', 'weight' => 80, 'self_score' => 5]];
        $this->expectThrowable(InvalidArgumentException::class, fn() => EvaluationValidationService::validateMainWork($rows, 80, true));
    }

    public function testMainWorkCannotExceedTenRows(): void
    {
        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = ['title' => 'งาน' . $i, 'weight' => 8, 'self_score' => 5, 'supervisor_score' => 5];
        }
        EvaluationValidationService::validateMainWork($rows, 80, true);
        $rows[] = ['title' => 'งาน11', 'weight' => 0, 'self_score' => 5, 'supervisor_score' => 5];
        $this->expectThrowable(InvalidArgumentException::class, fn() => EvaluationValidationService::validateMainWork($rows, 80, true));
    }

    public function testScoresOutsideOneToFiveAreRejected(): void
    {
        foreach ([-1, 101, 'abc', null] as $value) {
            $this->expectThrowable(InvalidArgumentException::class, fn() => EvaluationValidationService::validateNumeric($value, 0, 100));
        }
    }
}
