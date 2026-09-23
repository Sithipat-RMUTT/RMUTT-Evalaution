<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * CycleTemplateMapping Model
 *
 * @property int $id
 * @property int $evaluation_cycle_id
 * @property int|null $department_id
 * @property int $personnel_type_id
 * @property int $template_version_id
 * @property int $created_at
 */
class CycleTemplateMapping extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%cycle_template_mappings}}';
    }

    public function rules()
    {
        return [
            [['evaluation_cycle_id', 'personnel_type_id', 'template_version_id', 'created_at'], 'required'],
            [['evaluation_cycle_id', 'department_id', 'personnel_type_id', 'template_version_id', 'created_at'], 'integer'],
            [['evaluation_cycle_id'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluationCycle::class, 'targetAttribute' => ['evaluation_cycle_id' => 'id']],
            [['department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department::class, 'targetAttribute' => ['department_id' => 'id']],
            [['personnel_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => PersonnelType::class, 'targetAttribute' => ['personnel_type_id' => 'id']],
            [['template_version_id'], 'exist', 'skipOnError' => true, 'targetClass' => TemplateVersion::class, 'targetAttribute' => ['template_version_id' => 'id']],
        ];
    }

    public function getCycle()
    {
        return $this->hasOne(EvaluationCycle::class, ['id' => 'evaluation_cycle_id']);
    }

    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['id' => 'department_id']);
    }

    public function getPersonnelType()
    {
        return $this->hasOne(PersonnelType::class, ['id' => 'personnel_type_id']);
    }

    public function getTemplateVersion()
    {
        return $this->hasOne(TemplateVersion::class, ['id' => 'template_version_id']);
    }
}
