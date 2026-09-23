<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Position Model
 *
 * @property int $id
 * @property string|null $code
 * @property string $name_th
 * @property string|null $level_label
 * @property int $sort_order
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class Position extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%positions}}';
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
            [['name_th'], 'required'],
            [['sort_order', 'status'], 'integer'],
            [['code'], 'string', 'max' => 50],
            [['name_th'], 'string', 'max' => 255],
            [['level_label'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'รหัสตำแหน่ง',
            'name_th' => 'ชื่อตำแหน่ง',
            'level_label' => 'ระดับตำแหน่ง (เช่น ชำนาญการ/ปฏิบัติการ)',
            'sort_order' => 'ลำดับ',
            'status' => 'สถานะ',
        ];
    }

    public function getPersonnel()
    {
        return $this->hasMany(Personnel::class, ['position_id' => 'id']);
    }
}
