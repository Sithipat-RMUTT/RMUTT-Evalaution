<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * PersonnelType Model
 *
 * @property int $id
 * @property string $code
 * @property string $name_th
 * @property string|null $name_en
 * @property string|null $description
 * @property int $sort_order
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class PersonnelType extends ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    const CODE_CIVIL = 'CIVIL';
    const CODE_UNIVERSITY = 'UNIVERSITY';
    const CODE_GOVT = 'GOVT';
    const CODE_SPECIAL = 'SPECIAL';

    public static function tableName()
    {
        return '{{%personnel_types}}';
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
            [['code', 'name_th'], 'required'],
            [['description'], 'string'],
            [['sort_order', 'status'], 'integer'],
            [['code'], 'string', 'max' => 30],
            [['name_th', 'name_en'], 'string', 'max' => 150],
            [['code'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'รหัสประเภท',
            'name_th' => 'ประเภทบุคลากร (ภาษาไทย)',
            'name_en' => 'ประเภทบุคลากร (English)',
            'description' => 'รายละเอียด',
            'sort_order' => 'ลำดับการแสดงผล',
            'status' => 'สถานะ',
            'created_at' => 'สร้างเมื่อ',
            'updated_at' => 'แก้ไขเมื่อ',
        ];
    }

    public function getPersonnel()
    {
        return $this->hasMany(Personnel::class, ['personnel_type_id' => 'id']);
    }

    public function getEvaluationTemplates()
    {
        return $this->hasMany(EvaluationTemplate::class, ['personnel_type_id' => 'id']);
    }
}
