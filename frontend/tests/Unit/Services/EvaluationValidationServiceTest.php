<?php
declare(strict_types=1);
namespace frontend\tests\Unit\Services;

use Codeception\Test\Unit;
use common\services\EvaluationValidationService;
use yii\base\InvalidArgumentException;

final class EvaluationValidationServiceTest extends Unit
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

    public function testScoreRange(): void
    {
        verify(EvaluationValidationService::validateLevel(1))->equals(1);
        verify(EvaluationValidationService::validateLevel(5))->equals(5);
        $this->expectThrowable(InvalidArgumentException::class, fn() => EvaluationValidationService::validateLevel(0));
        $this->expectThrowable(InvalidArgumentException::class, fn() => EvaluationValidationService::validateLevel(6));
    }

    public function testSupervisorScoreRequiredForFinalRows(): void
    {
        $rows = [['title' => 'A', 'weight' => 80, 'self_score' => 5]];
        $this->expectThrowable(InvalidArgumentException::class, fn() => EvaluationValidationService::validateMainWork($rows, 80, true));
    }

    public function testMainWorkMustTotalEightyAndMaxTenRows(): void
    {
        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = ['title' => 'Task ' . $i, 'weight' => 8, 'self_score' => 5];
        }
        EvaluationValidationService::validateMainWork($rows);
        $this->expectThrowable(InvalidArgumentException::class, fn() => EvaluationValidationService::validateMainWork(array_merge($rows, [['weight' => 1, 'self_score' => 5]])));
        $bad = $rows;
        $bad[0]['weight'] = 7;
        $this->expectThrowable(InvalidArgumentException::class, fn() => EvaluationValidationService::validateMainWork($bad));
    }
}
