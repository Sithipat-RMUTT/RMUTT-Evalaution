<?php
namespace common\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * AnnualEvaluation model
 * 
 * @property int $id
 * @property int $fiscal_year
 * @property int $personnel_id
 * @property int|null $cycle1_evaluation_id
 * @property int|null $cycle2_evaluation_id
 * @property float|null $cycle1_score
 * @property float|null $cycle2_score
 * @property float|null $annual_average
 * @property string|null $performance_level
 * @property string $status
 * @property string|null $approved_at
 * @property int|null $approved_by
 * @property int $created_at
 * @property int $updated_at
 */
class AnnualEvaluation extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%annual_evaluations}}';
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
            [['fiscal_year', 'personnel_id'], 'required'],
            [['fiscal_year', 'personnel_id', 'cycle1_evaluation_id', 'cycle2_evaluation_id', 'approved_by'], 'integer'],
            [['cycle1_score', 'cycle2_score', 'annual_average'], 'number'],
            [['status', 'performance_level'], 'string', 'max' => 50],
            ['fiscal_year', 'integer', 'min' => 2500, 'max' => 3000],
            [['approved_at'], 'safe'],
        ];
    }

    public function getPersonnel()
    {
        return $this->hasOne(Personnel::class, ['id' => 'personnel_id']);
    }

    public function getCycle1()
    {
        return $this->hasOne(Evaluation::class, ['id' => 'cycle1_evaluation_id']);
    }

    public function getCycle2()
    {
        return $this->hasOne(Evaluation::class, ['id' => 'cycle2_evaluation_id']);
    }

    public function getApprover()
    {
        return $this->hasOne(User::class, ['id' => 'approved_by']);
    }
}
