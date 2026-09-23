<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\EvaluationCycle;
use common\models\CycleTemplateMapping;
use common\models\PersonnelType;
use common\models\EvaluationTemplate;
use common\models\AuditLog;

/**
 * CycleController manages evaluation cycles and template mappings.
 */
class CycleController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin', 'superadmin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'set-active' => ['post'],
                    'close' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $cycles = EvaluationCycle::find()->orderBy(['fiscal_year' => SORT_DESC, 'cycle_number' => SORT_DESC])->all();
        return $this->render('index', [
            'cycles' => $cycles,
        ]);
    }

    public function actionCreate()
    {
        $model = new EvaluationCycle();
        $model->fiscal_year = date('Y') + 543;
        $model->cycle_number = 1;
        $model->period_start = date('Y') . '-10-01';
        $model->period_end = (date('Y') + 1) . '-03-31';
        $model->self_assessment_start = date('Y-m-d 08:30:00');
        $model->self_assessment_end = date('Y-m-d 16:30:00', strtotime('+30 days'));
        $model->supervisor_eval_start = date('Y-m-d 08:30:00', strtotime('+15 days'));
        $model->supervisor_eval_end = date('Y-m-d 16:30:00', strtotime('+45 days'));
        $model->status = EvaluationCycle::STATUS_DRAFT;

        if ($model->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->id;
            if ($model->save()) {
                AuditLog::log('create_evaluation_cycle', 'EvaluationCycle', $model->id);

                // Auto-map latest templates for all types and departments
                $templates = EvaluationTemplate::find()->where(['status' => 1])->all();
                foreach ($templates as $tmpl) {
                    if ($tmpl->activeVersion) {
                        $m = new CycleTemplateMapping();
                        $m->evaluation_cycle_id = $model->id;
                        $m->department_id = $tmpl->department_id;
                        $m->personnel_type_id = $tmpl->personnel_type_id;
                        $m->template_version_id = $tmpl->activeVersion->id;
                        $m->created_at = time();
                        $m->save(false);
                    }
                }

                Yii::$app->session->setFlash('success', 'สร้างรอบการประเมินใหม่เรียบร้อยแล้ว');
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            AuditLog::log('update_evaluation_cycle', 'EvaluationCycle', $model->id);
            Yii::$app->session->setFlash('success', 'แก้ไขข้อมูลรอบการประเมินเรียบร้อยแล้ว');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionSetActive($id)
    {
        $model = $this->findModel($id);
        $tx = Yii::$app->db->beginTransaction();
        try {
            // Deactivate all other active cycles
            EvaluationCycle::updateAll(
                ['status' => EvaluationCycle::STATUS_CLOSED],
                ['and', ['status' => EvaluationCycle::STATUS_ACTIVE], ['!=', 'id', $model->id]]
            );

            $model->status = EvaluationCycle::STATUS_ACTIVE;
            $model->save(false);

            AuditLog::log('activate_evaluation_cycle', 'EvaluationCycle', $model->id);
            $tx->commit();
            Yii::$app->session->setFlash('success', "เปิดใช้งานรอบการประเมิน {$model->name_th} เรียบร้อยแล้ว (ปิดรอบ active เดิมโดยอัตโนมัติ)");
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดในการเปิดใช้งานรอบการประเมิน: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    public function actionClose($id)
    {
        $model = $this->findModel($id);
        $model->status = EvaluationCycle::STATUS_CLOSED;
        $model->save(false);

        AuditLog::log('close_evaluation_cycle', 'EvaluationCycle', $model->id);
        Yii::$app->session->setFlash('info', "ปิดรอบการประเมิน {$model->name_th} เรียบร้อยแล้ว");
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = EvaluationCycle::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
