<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * EvaluationItem Model
 *
 * @property int $id
 * @property int $evaluation_section_id
 * @property string|null $item_code
 * @property string $name_th
 * @property string|null $description
 * @property string $input_type
 * @property float|null $max_score
 * @property float|null $max_weight
 * @property float|null $default_weight
 * @property int|null $expected_level
 * @property int $sort_order
 * @property int $is_required
 * @property int $requires_evidence
 * @property string|null $evidence_instruction
 * @property array|null $options_data
 * @property int $created_at
 * @property int $updated_at
 */
class EvaluationItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evaluation_items}}';
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
            [['evaluation_section_id', 'name_th'], 'required'],
            [['evaluation_section_id', 'expected_level', 'sort_order', 'is_required', 'requires_evidence'], 'integer'],
            [['description', 'evidence_instruction'], 'string'],
            [['max_score', 'max_weight', 'default_weight'], 'number'],
            [['options_data'], 'safe'],
            [['item_code', 'input_type'], 'string', 'max' => 50],
            [['name_th'], 'string', 'max' => 500],
            [['evaluation_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluationSection::class, 'targetAttribute' => ['evaluation_section_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'evaluation_section_id' => 'หมวดการประเมิน',
            'item_code' => 'รหัสหัวข้อ',
            'name_th' => 'หัวข้อการประเมิน',
            'description' => 'คำอธิบาย/นิยาม',
            'input_type' => 'รูปแบบข้อมูล',
            'max_score' => 'คะแนนเต็ม',
            'max_weight' => 'น้ำหนักสูงสุด',
            'default_weight' => 'น้ำหนักเริ่มต้น',
            'expected_level' => 'ระดับที่คาดหวัง',
            'sort_order' => 'ลำดับ',
            'is_required' => 'จำเป็นต้องกรอก',
            'requires_evidence' => 'ต้องแนบหลักฐาน',
            'evidence_instruction' => 'คำแนะนำการแนบหลักฐาน',
        ];
    }

    public function getSection()
    {
        return $this->hasOne(EvaluationSection::class, ['id' => 'evaluation_section_id']);
    }

    public function getCriteria()
    {
        return $this->hasMany(EvaluationCriteria::class, ['evaluation_item_id' => 'id'])->orderBy(['level_value' => SORT_ASC]);
    }
}
