<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * EvaluationCompetencyAnswer Model
 *
 * @property int $id
 * @property int $evaluation_id
 * @property int $competency_definition_id
 * @property string $answered_by
 * @property int $level_value
 * @property string|null $gap_summary
 * @property string|null $importance
 * @property string|null $idp_plan
 * @property int $created_at
 * @property int $updated_at
 */
class EvaluationCompetencyAnswer extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_competency_answers}}';
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
            [['evaluation_id', 'competency_definition_id', 'answered_by', 'level_value'], 'required'],
            [['evaluation_id', 'competency_definition_id', 'level_value'], 'integer'],
            [['gap_summary', 'idp_plan'], 'string'],
            [['answered_by', 'importance'], 'string', 'max' => 20],
            [['evaluation_id', 'competency_definition_id', 'answered_by'], 'unique', 'targetAttribute' => ['evaluation_id', 'competency_definition_id', 'answered_by']],
            [['evaluation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Evaluation::class, 'targetAttribute' => ['evaluation_id' => 'id']],
            [['competency_definition_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompetencyDefinition::class, 'targetAttribute' => ['competency_definition_id' => 'id']],
        ];
    }

    public function getEvaluation()
    {
        return $this->hasOne(Evaluation::class, ['id' => 'evaluation_id']);
    }

    public function getCompetencyDefinition()
    {
        return $this->hasOne(CompetencyDefinition::class, ['id' => 'competency_definition_id']);
    }
}
