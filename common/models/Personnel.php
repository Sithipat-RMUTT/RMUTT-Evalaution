<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Personnel Model
 *
 * @property int $id
 * @property int $user_id
 * @property int $personnel_type_id
 * @property int $department_id
 * @property string|null $work_unit
 * @property int $position_id
 * @property int|null $supervisor_id
 * @property int|null $division_head_id
 * @property string $position_level staff, section_head, division_head, director
 * @property string|null $employee_code
 * @property string|null $citizen_id
 * @property string|null $prefix_th
 * @property string $first_name_th
 * @property string $last_name_th
 * @property string|null $prefix_en
 * @property string|null $first_name_en
 * @property string|null $last_name_en
 * @property string|null $phone
 * @property string|null $email
 * @property float|null $salary
 * @property string|null $hire_date
 * @property string|null $contract_start_date
 * @property string|null $contract_end_date
 * @property int $is_supervisor
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class Personnel extends ActiveRecord
{
    const STATUS_ACTIVE = 10;
    const STATUS_INACTIVE = 0;

    public static function tableName()
    {
        return '{{%personnel}}';
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
            [['user_id', 'personnel_type_id', 'department_id', 'position_id', 'first_name_th', 'last_name_th'], 'required'],
            [['user_id', 'personnel_type_id', 'department_id', 'position_id', 'supervisor_id', 'division_head_id', 'is_supervisor', 'status'], 'integer'],
            [['salary'], 'number'],
            [['hire_date', 'contract_start_date', 'contract_end_date'], 'safe'],
            [['position_level'], 'string', 'max' => 30],
            [['position_level'], 'default', 'value' => 'staff'],
            [['employee_code'], 'string', 'max' => 50],
            [['work_unit'], 'string', 'max' => 150],
            [['citizen_id', 'phone'], 'string', 'max' => 20],
            [['citizen_id_encrypted'], 'string', 'max' => 500],
            [['citizen_id_blind_index'], 'string', 'max' => 64],
            [['prefix_th', 'prefix_en'], 'string', 'max' => 30],
            [['first_name_th', 'last_name_th', 'first_name_en', 'last_name_en'], 'string', 'max' => 100],
            [['email'], 'string', 'max' => 150],
            [['email'], 'email'],
            [['user_id'], 'unique'],
            [['employee_code'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['personnel_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => PersonnelType::class, 'targetAttribute' => ['personnel_type_id' => 'id']],
            [['department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department::class, 'targetAttribute' => ['department_id' => 'id']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::class, 'targetAttribute' => ['position_id' => 'id']],
            [['supervisor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Personnel::class, 'targetAttribute' => ['supervisor_id' => 'id']],
            [['division_head_id'], 'exist', 'skipOnError' => true, 'targetClass' => Personnel::class, 'targetAttribute' => ['division_head_id' => 'id']],
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($this->citizen_id !== null && $this->citizen_id !== '') {
            $this->citizen_id_blind_index = \common\services\DataProtectionService::blindIndex($this->citizen_id);
            $this->citizen_id_encrypted = \common\services\DataProtectionService::encrypt($this->citizen_id);
            $this->citizen_id = null;
        }
        return true;
    }

    public function afterFind()
    {
        parent::afterFind();
        if (!empty($this->citizen_id_encrypted)) {
            $this->citizen_id = \common\services\DataProtectionService::decrypt($this->citizen_id_encrypted);
        }
    }

    public static function findByCitizenId(string $citizenId): ?self
    {
        $index = \common\services\DataProtectionService::blindIndex($citizenId);
        return self::findOne(['citizen_id_blind_index' => $index]);
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'บัญชีผู้ใช้งาน',
            'personnel_type_id' => 'ประเภทบุคลากร',
            'department_id' => 'หน่วยงาน/ฝ่าย',
            'work_unit' => 'งาน/กลุ่มงาน',
            'position_id' => 'ตำแหน่ง',
            'supervisor_id' => 'หัวหน้างาน (ผู้ประเมินชั้นต้น L1)',
            'division_head_id' => 'หัวหน้าฝ่าย (ผู้ประเมินชั้นที่ 2 L2)',
            'position_level' => 'ระดับสายการบังคับบัญชา',
            'employee_code' => 'รหัสบุคลากร',
            'citizen_id' => 'เลขประจำตัวประชาชน',
            'prefix_th' => 'คำนำหน้า',
            'first_name_th' => 'ชื่อ (ภาษาไทย)',
            'last_name_th' => 'นามสกุล (ภาษาไทย)',
            'phone' => 'เบอร์โทรศัพท์',
            'email' => 'อีเมล',
            'salary' => 'อัตราเงินเดือน/ค่าจ้าง',
            'hire_date' => 'วันที่เริ่มบรรจุ/ทำงาน',
            'contract_start_date' => 'วันเริ่มสัญญาจ้าง',
            'contract_end_date' => 'วันสิ้นสุดสัญญาจ้าง',
            'is_supervisor' => 'มีบทบาทเป็นผู้ประเมิน',
            'status' => 'สถานะ',
        ];
    }

    public function getFullName()
    {
        return trim(($this->prefix_th ?: '') . ' ' . $this->first_name_th . ' ' . $this->last_name_th);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getPersonnelType()
    {
        return $this->hasOne(PersonnelType::class, ['id' => 'personnel_type_id']);
    }

    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['id' => 'department_id']);
    }

    public function getPosition()
    {
        return $this->hasOne(Position::class, ['id' => 'position_id']);
    }

    public function getSupervisor()
    {
        return $this->hasOne(Personnel::class, ['id' => 'supervisor_id']);
    }

    public function getDivisionHead()
    {
        return $this->hasOne(Personnel::class, ['id' => 'division_head_id']);
    }

    public function getSubordinates()
    {
        return $this->hasMany(Personnel::class, ['supervisor_id' => 'id']);
    }

    public function getDivisionSubordinates()
    {
        return $this->hasMany(Personnel::class, ['division_head_id' => 'id']);
    }

    public function getEvaluations()
    {
        return $this->hasMany(Evaluation::class, ['personnel_id' => 'id']);
    }

    public function getEvaluationsToEvaluate()
    {
        return $this->hasMany(Evaluation::class, ['evaluator_id' => 'id']);
    }

    public function getPosition_level()
    {
        return $this->getPositionLevel();
    }

    public function setPosition_level($value)
    {
        $this->setPositionLevel($value);
    }

    public function getPositionLevel()
    {
        if ($this->hasAttribute('position_level')) {
            $val = $this->getAttribute('position_level');
            if (!empty($val)) {
                return $val;
            }
        }
        return 'staff';
    }

    public function setPositionLevel($value)
    {
        if ($this->hasAttribute('position_level')) {
            $this->setAttribute('position_level', $value);
        }
    }

    public function isSectionHead()
    {
        return ($this->getPositionLevel() === 'section_head') || Personnel::find()->where(['supervisor_id' => $this->id])->exists();
    }

    public function isDivisionHead()
    {
        return ($this->getPositionLevel() === 'division_head') || Personnel::find()->where(['division_head_id' => $this->id])->exists();
    }

    public function getWork_unit()
    {
        return $this->getWorkUnit();
    }

    public function setWork_unit($value)
    {
        $this->setWorkUnit($value);
    }

    public function getWorkUnit()
    {
        if ($this->hasAttribute('work_unit')) {
            return $this->getAttribute('work_unit');
        }
        return null;
    }

    public function setWorkUnit($value)
    {
        if ($this->hasAttribute('work_unit')) {
            $this->setAttribute('work_unit', $value);
        }
    }
}
