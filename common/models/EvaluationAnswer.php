<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * EvaluationAnswer Model
 *
 * @property int $id
 * @property int $evaluation_id
 * @property int $evaluation_item_id
 * @property string $answered_by
 * @property string|null $text_value
 * @property float|null $numeric_value
 * @property float|null $weight_value
 * @property array|null $json_value
 * @property int $created_at
 * @property int $updated_at
 */
class EvaluationAnswer extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_answers}}';
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
            [['evaluation_id', 'evaluation_item_id'], 'required'],
            [['evaluation_id', 'evaluation_item_id'], 'integer'],
            [['text_value'], 'string'],
            [['numeric_value', 'weight_value'], 'number'],
            [['json_value'], 'safe'],
            [['answered_by'], 'string', 'max' => 20],
            [['evaluation_id', 'evaluation_item_id', 'answered_by'], 'unique', 'targetAttribute' => ['evaluation_id', 'evaluation_item_id', 'answered_by']],
            [['evaluation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Evaluation::class, 'targetAttribute' => ['evaluation_id' => 'id']],
            [['evaluation_item_id'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluationItem::class, 'targetAttribute' => ['evaluation_item_id' => 'id']],
        ];
    }

    public function getEvaluation()
    {
        return $this->hasOne(Evaluation::class, ['id' => 'evaluation_id']);
    }

    public function getItem()
    {
        return $this->hasOne(EvaluationItem::class, ['id' => 'evaluation_item_id']);
    }
}
