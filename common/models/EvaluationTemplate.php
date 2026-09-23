<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * EvaluationTemplate Model
 *
 * @property int $id
 * @property int $personnel_type_id
 * @property int|null $department_id
 * @property string $code
 * @property string $name_th
 * @property string|null $description
 * @property int $status
 * @property int $is_default
 * @property int $created_at
 * @property int $updated_at
 */
class EvaluationTemplate extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_templates}}';
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
            [['personnel_type_id', 'code', 'name_th'], 'required'],
            [['personnel_type_id', 'department_id', 'status', 'is_default'], 'integer'],
            [['description'], 'string'],
            [['code'], 'string', 'max' => 50],
            [['name_th'], 'string', 'max' => 255],
            [['code'], 'unique'],
            [['personnel_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => PersonnelType::class, 'targetAttribute' => ['personnel_type_id' => 'id']],
            [['department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department::class, 'targetAttribute' => ['department_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'personnel_type_id' => 'ประเภทบุคลากร',
            'department_id' => 'หน่วยงาน / สังกัด',
            'code' => 'รหัสแบบฟอร์ม',
            'name_th' => 'ชื่อแบบฟอร์มการประเมิน',
            'description' => 'คำอธิบาย',
            'status' => 'สถานะ',
            'is_default' => 'เป็นแม่แบบมาตรฐานกลาง',
        ];
    }

    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['id' => 'department_id']);
    }

    public function getPersonnelType()
    {
        return $this->hasOne(PersonnelType::class, ['id' => 'personnel_type_id']);
    }

    public function getVersions()
    {
        return $this->hasMany(TemplateVersion::class, ['evaluation_template_id' => 'id'])->orderBy(['version_number' => SORT_DESC]);
    }

    public function getActiveVersion()
    {
        return $this->hasOne(TemplateVersion::class, ['evaluation_template_id' => 'id'])
            ->where(['is_active' => 1])
            ->orderBy(['version_number' => SORT_DESC]);
    }
}
