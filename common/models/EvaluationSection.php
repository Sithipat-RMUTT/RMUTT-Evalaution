<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * EvaluationSection Model
 *
 * @property int $id
 * @property int $template_version_id
 * @property int|null $parent_section_id
 * @property string|null $section_code
 * @property string $name_th
 * @property string|null $description
 * @property float|null $weight
 * @property int $sort_order
 * @property int $is_evaluator_fill
 * @property string $section_type
 * @property int $created_at
 * @property int $updated_at
 */
class EvaluationSection extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_sections}}';
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
            [['template_version_id', 'name_th'], 'required'],
            [['template_version_id', 'parent_section_id', 'sort_order', 'is_evaluator_fill'], 'integer'],
            [['description'], 'string'],
            [['weight'], 'number'],
            [['section_code', 'section_type'], 'string', 'max' => 50],
            [['name_th'], 'string', 'max' => 300],
            [['template_version_id'], 'exist', 'skipOnError' => true, 'targetClass' => TemplateVersion::class, 'targetAttribute' => ['template_version_id' => 'id']],
            [['parent_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluationSection::class, 'targetAttribute' => ['parent_section_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_version_id' => 'เวอร์ชันแบบฟอร์ม',
            'parent_section_id' => 'หมวดหลัก',
            'section_code' => 'รหัสหมวด',
            'name_th' => 'ชื่อส่วน/หมวดการประเมิน',
            'description' => 'รายละเอียด/คำชี้แจง',
            'weight' => 'น้ำหนักของส่วนนี้ (%)',
            'sort_order' => 'ลำดับ',
            'is_evaluator_fill' => 'ผู้กรอกคะแนน',
            'section_type' => 'ประเภทหมวด',
        ];
    }

    public function getTemplateVersion()
    {
        return $this->hasOne(TemplateVersion::class, ['id' => 'template_version_id']);
    }

    public function getParent()
    {
        return $this->hasOne(EvaluationSection::class, ['id' => 'parent_section_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(EvaluationSection::class, ['parent_section_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getItems()
    {
        return $this->hasMany(EvaluationItem::class, ['evaluation_section_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }
}
