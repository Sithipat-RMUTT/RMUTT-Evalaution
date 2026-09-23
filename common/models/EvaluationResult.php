<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * EvaluationResult Model
 *
 * @property int $id
 * @property int $evaluation_id
 * @property int $template_version_id
 * @property float|null $self_performance_score
 * @property float|null $self_competency_score
 * @property float|null $self_total_score
 * @property float|null $supervisor_performance_score
 * @property float|null $supervisor_competency_score
 * @property float|null $supervisor_total_score
 * @property float $final_score
 * @property float $final_percentage
 * @property string $performance_level
 * @property array $calculation_details
 * @property int $calculated_by
 * @property string $calculated_at
 * @property int $created_at
 * @property int $updated_at
 */
class EvaluationResult extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_results}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['evaluation_id', 'template_version_id', 'final_score', 'final_percentage', 'performance_level', 'calculation_details', 'calculated_by', 'calculated_at'], 'required'],
            [['evaluation_id', 'template_version_id', 'calculated_by'], 'integer'],
            [['self_performance_score', 'self_competency_score', 'self_total_score', 'supervisor_performance_score', 'supervisor_competency_score', 'supervisor_total_score', 'l1_performance_score', 'l1_competency_score', 'l1_total_score', 'l2_performance_score', 'l2_competency_score', 'l2_total_score', 'final_score', 'final_percentage'], 'number'],
            [['calculation_details', 'calculated_at'], 'safe'],
            [['performance_level'], 'string', 'max' => 50],
            [['evaluation_id'], 'unique'],
            [['evaluation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Evaluation::class, 'targetAttribute' => ['evaluation_id' => 'id']],
            [['template_version_id'], 'exist', 'skipOnError' => true, 'targetClass' => TemplateVersion::class, 'targetAttribute' => ['template_version_id' => 'id']],
            [['calculated_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['calculated_by' => 'id']],
        ];
    }

    public function getPerformanceBadge()
    {
        $map = [
            'ดีเด่น' => 'bg-success',
            'ดีมาก' => 'bg-primary',
            'ดี' => 'bg-info text-dark',
            'พอใช้' => 'bg-warning text-dark',
            'ต้องปรับปรุง' => 'bg-danger',
            'ไม่ผ่าน' => 'bg-danger',
        ];
        $class = $map[$this->performance_level] ?? 'bg-secondary';
        return "<span class=\"badge {$class}\">{$this->performance_level}</span>";
    }

    public function getEvaluation()
    {
        return $this->hasOne(Evaluation::class, ['id' => 'evaluation_id']);
    }

    public function getTemplateVersion()
    {
        return $this->hasOne(TemplateVersion::class, ['id' => 'template_version_id']);
    }

    public function getCalculatorUser()
    {
        return $this->hasOne(User::class, ['id' => 'calculated_by']);
    }
}
