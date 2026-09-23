<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * CompetencyDefinition Model
 *
 * @property int $id
 * @property int $template_version_id
 * @property string|null $competency_code
 * @property string $competency_type
 * @property string $name_th
 * @property string|null $name_en
 * @property string $definition
 * @property int $expected_level
 * @property int $sort_order
 * @property int $created_at
 * @property int $updated_at
 */
class CompetencyDefinition extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%competency_definitions}}';
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
            [['template_version_id', 'name_th', 'definition'], 'required'],
            [['template_version_id', 'expected_level', 'sort_order'], 'integer'],
            [['definition'], 'string'],
            [['competency_code'], 'string', 'max' => 50],
            [['competency_type'], 'string', 'max' => 30],
            [['name_th', 'name_en'], 'string', 'max' => 300],
            [['template_version_id'], 'exist', 'skipOnError' => true, 'targetClass' => TemplateVersion::class, 'targetAttribute' => ['template_version_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_version_id' => 'เวอร์ชันแบบฟอร์ม',
            'competency_code' => 'รหัสสมรรถนะ',
            'competency_type' => 'ประเภทสมรรถนะ (Core/Functional/Behavior)',
            'name_th' => 'ชื่อสมรรถนะ (ภาษาไทย)',
            'name_en' => 'ชื่อสมรรถนะ (English)',
            'definition' => 'นิยาม/คำจำกัดความ',
            'expected_level' => 'ระดับสมรรถนะที่คาดหวัง',
            'sort_order' => 'ลำดับ',
        ];
    }

    public function getTemplateVersion()
    {
        return $this->hasOne(TemplateVersion::class, ['id' => 'template_version_id']);
    }

    public function getLevels()
    {
        return $this->hasMany(CompetencyLevel::class, ['competency_definition_id' => 'id'])->orderBy(['level_value' => SORT_ASC]);
    }
}
