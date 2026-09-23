<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use common\models\Evaluation;
use common\models\AnnualEvaluation;
use common\models\AuditLog;
use common\services\EvaluationValidationService;

class WorkflowController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['build-annual'], 'allow' => true, 'roles' => ['admin', 'superadmin']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'build-annual' => ['post'],
                ],
            ],
        ];
    }

    public function actionBuildAnnual()
    {
        $year = (int)Yii::$app->request->post('fiscal_year', 0);
        if ($year < 2500 || $year > 3000) {
            throw new BadRequestHttpException('ปีงบประมาณไม่ถูกต้อง');
        }
        $personnel = \common\models\Personnel::find()->where(['status' => \common\models\Personnel::STATUS_ACTIVE])->all();
        $built = 0;
        foreach ($personnel as $p) {
            $c1 = Evaluation::find()->joinWith('cycle')->where(['personnel_id' => $p->id, 'evaluation_cycles.fiscal_year' => $year, 'evaluation_cycles.cycle_number' => 1])->one();
            $c2 = Evaluation::find()->joinWith('cycle')->where(['personnel_id' => $p->id, 'evaluation_cycles.fiscal_year' => $year, 'evaluation_cycles.cycle_number' => 2])->one();
            $s1 = ($c1 && $c1->result) ? (float)$c1->result->final_percentage : null;
            $s2 = ($c2 && $c2->result) ? (float)$c2->result->final_percentage : null;
            $avg = ($s1 !== null && $s2 !== null) ? round(($s1 + $s2) / 2, 4) : ($s1 ?? $s2);
            $annual = AnnualEvaluation::findOne(['fiscal_year' => $year, 'personnel_id' => $p->id]) ?: new AnnualEvaluation(['fiscal_year' => $year, 'personnel_id' => $p->id]);
            $annual->cycle1_evaluation_id = $c1?->id;
            $annual->cycle2_evaluation_id = $c2?->id;
            $annual->cycle1_score = $s1;
            $annual->cycle2_score = $s2;
            $annual->annual_average = $avg;
            $annual->performance_level = $avg !== null ? \common\services\EvaluationCalculatorService::gradePerformanceLevel($avg) : null;
            $annual->status = ($c1?->status === Evaluation::STATUS_COMPLETED && $c2?->status === Evaluation::STATUS_COMPLETED) ? 'completed' : 'draft';
            if ($annual->save(false)) {
                $built++;
            }
        }
        AuditLog::log('build_annual_evaluations', 'AnnualEvaluation', 0, null, ['fiscal_year' => $year, 'count' => $built]);
        Yii::$app->session->setFlash('success', "ประมวลผลการประเมินประจำปี {$year} สำเร็จ จำนวน {$built} รายการ");
        return $this->redirect(['report/index']);
    }

    protected function find($id): Evaluation
    {
        $e = Evaluation::findOne($id);
        if (!$e) {
            throw new NotFoundHttpException('ไม่พบแบบประเมิน');
        }
        return $e;
    }
}
