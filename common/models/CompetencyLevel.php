<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * CompetencyLevel Model
 *
 * @property int $id
 * @property int $competency_definition_id
 * @property int $level_value
 * @property string $level_label
 * @property string $behavior_description
 * @property int $created_at
 * @property int $updated_at
 */
class CompetencyLevel extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%competency_levels}}';
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
            [['competency_definition_id', 'level_value', 'level_label', 'behavior_description'], 'required'],
            [['competency_definition_id', 'level_value'], 'integer'],
            [['behavior_description'], 'string'],
            [['level_label'], 'string', 'max' => 100],
            [['competency_definition_id', 'level_value'], 'unique', 'targetAttribute' => ['competency_definition_id', 'level_value']],
            [['competency_definition_id'], 'exist', 'skipOnError' => true, 'targetClass' => CompetencyDefinition::class, 'targetAttribute' => ['competency_definition_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'competency_definition_id' => 'สมรรถนะ',
            'level_value' => 'ระดับ (1-5)',
            'level_label' => 'ชื่อระดับ (เช่น Basic, Apply)',
            'behavior_description' => 'พฤติกรรมบ่งชี้',
        ];
    }

    public function getDefinition()
    {
        return $this->hasOne(CompetencyDefinition::class, ['id' => 'competency_definition_id']);
    }
}
