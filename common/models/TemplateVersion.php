<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * TemplateVersion Model
 *
 * @property int $id
 * @property int $evaluation_template_id
 * @property int $version_number
 * @property string $version_label
 * @property int $is_active
 * @property string $effective_from
 * @property string|null $effective_to
 * @property float $total_weight
 * @property array|null $score_formula_config
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 */
class TemplateVersion extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%template_versions}}';
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
            [['evaluation_template_id', 'version_number', 'version_label', 'effective_from'], 'required'],
            [['evaluation_template_id', 'version_number', 'is_active', 'created_by'], 'integer'],
            [['effective_from', 'effective_to', 'score_formula_config'], 'safe'],
            [['total_weight'], 'number'],
            [['version_label'], 'string', 'max' => 100],
            [['evaluation_template_id', 'version_number'], 'unique', 'targetAttribute' => ['evaluation_template_id', 'version_number']],
            [['evaluation_template_id'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluationTemplate::class, 'targetAttribute' => ['evaluation_template_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'evaluation_template_id' => 'แบบฟอร์มหลัก',
            'version_number' => 'หมายเลขเวอร์ชัน',
            'version_label' => 'ชื่อเวอร์ชัน/ปีงบประมาณ',
            'is_active' => 'สถานะการใช้งาน',
            'effective_from' => 'วันที่มีผลบังคับใช้',
            'effective_to' => 'สิ้นสุดผลบังคับใช้',
            'total_weight' => 'น้ำหนักรวม (100%)',
            'score_formula_config' => 'การตั้งค่าสูตรคำนวณคะแนน',
        ];
    }

    public function getTemplate()
    {
        return $this->hasOne(EvaluationTemplate::class, ['id' => 'evaluation_template_id']);
    }

    public function getSections()
    {
        return $this->hasMany(EvaluationSection::class, ['template_version_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getCompetencyDefinitions()
    {
        return $this->hasMany(CompetencyDefinition::class, ['template_version_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }
}
