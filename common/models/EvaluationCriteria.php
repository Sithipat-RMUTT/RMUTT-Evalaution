<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * EvaluationCriteria Model
 *
 * @property int $id
 * @property int $evaluation_item_id
 * @property int $level_value
 * @property string $level_label
 * @property float $score_value
 * @property string|null $description
 * @property string|null $condition_rule
 * @property int $sort_order
 * @property int $created_at
 * @property int $updated_at
 */
class EvaluationCriteria extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_criteria}}';
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
            [['evaluation_item_id', 'level_value', 'level_label', 'score_value'], 'required'],
            [['evaluation_item_id', 'level_value', 'sort_order'], 'integer'],
            [['score_value'], 'number'],
            [['description'], 'string'],
            [['level_label'], 'string', 'max' => 200],
            [['condition_rule'], 'string', 'max' => 255],
            [['evaluation_item_id'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluationItem::class, 'targetAttribute' => ['evaluation_item_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'evaluation_item_id' => 'หัวข้อการประเมิน',
            'level_value' => 'ระดับ (1-5)',
            'level_label' => 'ชื่อระดับ',
            'score_value' => 'คะแนนที่ได้',
            'description' => 'เกณฑ์/คำอธิบาย',
            'condition_rule' => 'เงื่อนไข',
        ];
    }

    public function getItem()
    {
        return $this->hasOne(EvaluationItem::class, ['id' => 'evaluation_item_id']);
    }
}
