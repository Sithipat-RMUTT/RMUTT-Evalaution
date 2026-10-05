<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * DepartmentEvaluationCycle Model
 *
 * Tracks the evaluation cycle lifecycle per department/agency (Decentralized Activation & Governance).
 *
 * @property int $id
 * @property int $evaluation_cycle_id
 * @property int $department_id
 * @property string $status 'pending', 'active', 'completed', 'closed'
 * @property int|null $opened_at
 * @property int|null $opened_by
 * @property int|null $closed_at
 * @property int|null $closed_by
 * @property string|null $notes
 * @property int $created_at
 * @property int $updated_at
 *
 * @property EvaluationCycle $cycle
 * @property Department $department
 * @property User|null $opener
 * @property User|null $closer
 */
class DepartmentEvaluationCycle extends ActiveRecord
{
    const STATUS_PENDING = 'pending';     // ยังไม่เปิดรอบ (อยู่ระหว่างจัดเตรียมแบบประเมิน - แก้ไขแบบได้)
    const STATUS_ACTIVE = 'active';       // เปิดรอบแล้ว (แบบประเมินถูกล็อก 100% - บุคลากรเข้าทำได้)
    const STATUS_COMPLETED = 'completed'; // ประเมินครบ 100% แล้ว
    const STATUS_CLOSED = 'closed';       // ปิดรอบการประเมินประจำหน่วยงาน

    public static function tableName()
    {
        return '{{%department_evaluation_cycles}}';
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
            [['evaluation_cycle_id', 'department_id'], 'required'],
            [['evaluation_cycle_id', 'department_id', 'opened_at', 'opened_by', 'closed_at', 'closed_by'], 'integer'],
            [['status'], 'string', 'max' => 30],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['notes'], 'string'],
            [['evaluation_cycle_id', 'department_id'], 'unique', 'targetAttribute' => ['evaluation_cycle_id', 'department_id']],
            [['evaluation_cycle_id'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluationCycle::class, 'targetAttribute' => ['evaluation_cycle_id' => 'id']],
            [['department_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department::class, 'targetAttribute' => ['department_id' => 'id']],
            [['opened_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['opened_by' => 'id']],
            [['closed_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['closed_by' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'evaluation_cycle_id' => 'รอบการประเมิน',
            'department_id' => 'หน่วยงาน',
            'status' => 'สถานะรอบการประเมิน',
            'opened_at' => 'วันเวลาที่เปิดรอบ',
            'opened_by' => 'ผู้เปิดรอบ',
            'closed_at' => 'วันเวลาที่ปิดรอบ',
            'closed_by' => 'ผู้ปิดรอบ',
            'notes' => 'หมายเหตุ',
            'created_at' => 'สร้างเมื่อ',
            'updated_at' => 'แก้ไขล่าสุด',
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

    public function getOpener()
    {
        return $this->hasOne(User::class, ['id' => 'opened_by']);
    }

    public function getCloser()
    {
        return $this->hasOne(User::class, ['id' => 'closed_by']);
    }

    /**
     * Resolve root department ID (if sub-department, return parent)
     */
    public static function getRootDeptId(?int $deptId): ?int
    {
        if (!$deptId) {
            return null;
        }
        $dept = Department::findOne($deptId);
        if (!$dept) {
            return $deptId;
        }
        return (int)($dept->parent_id ?: $dept->id);
    }

    /**
     * Get or initialize record for specific cycle & department
     */
    public static function getOrCreateRecord(int $cycleId, int $deptId): self
    {
        $rootDeptId = self::getRootDeptId($deptId);
        $record = self::findOne([
            'evaluation_cycle_id' => $cycleId,
            'department_id' => $rootDeptId,
        ]);

        if (!$record) {
            $record = new self();
            $record->evaluation_cycle_id = $cycleId;
            $record->department_id = $rootDeptId;
            $record->status = self::STATUS_PENDING;
            $record->save(false);
        }

        return $record;
    }

    /**
     * Check if a department's cycle is currently ACTIVE (opened and accepting evaluations)
     */
    public static function isDepartmentCycleActive(int $cycleId, ?int $deptId): bool
    {
        if (!$deptId) {
            return true; // Central/Global falls back to Master cycle
        }
        $rootDeptId = self::getRootDeptId($deptId);
        $record = self::findOne([
            'evaluation_cycle_id' => $cycleId,
            'department_id' => $rootDeptId,
        ]);

        if (!$record) {
            return false; // Default is pending
        }

        return in_array($record->status, [self::STATUS_ACTIVE, self::STATUS_COMPLETED], true);
    }

    /**
     * Check if a department's cycle is PENDING (not opened yet, templates can still be prepared)
     */
    public static function isDepartmentCyclePending(int $cycleId, ?int $deptId): bool
    {
        if (!$deptId) {
            return false;
        }
        $rootDeptId = self::getRootDeptId($deptId);
        $record = self::findOne([
            'evaluation_cycle_id' => $cycleId,
            'department_id' => $rootDeptId,
        ]);

        return !$record || ($record->status === self::STATUS_PENDING);
    }

    /**
     * Check if a department's cycle is CLOSED
     */
    public static function isDepartmentCycleClosed(int $cycleId, ?int $deptId): bool
    {
        if (!$deptId) {
            return false;
        }
        $rootDeptId = self::getRootDeptId($deptId);
        $record = self::findOne([
            'evaluation_cycle_id' => $cycleId,
            'department_id' => $rootDeptId,
        ]);

        return $record && ($record->status === self::STATUS_CLOSED);
    }

    /**
     * Open cycle for a department (Freezes templates & enables self-assessments)
     */
    public static function openDepartmentCycle(int $cycleId, int $deptId, int $userId): bool
    {
        $record = self::getOrCreateRecord($cycleId, $deptId);
        $record->status = self::STATUS_ACTIVE;
        $record->opened_at = time();
        $record->opened_by = $userId;
        $saved = $record->save(false);

        if ($saved) {
            AuditLog::log('open_department_evaluation_cycle', 'DepartmentEvaluationCycle', $record->id, null, [
                'cycle_id' => $cycleId,
                'department_id' => $record->department_id,
            ]);
        }
        return $saved;
    }

    /**
     * Close cycle for a department
     */
    public static function closeDepartmentCycle(int $cycleId, int $deptId, int $userId): bool
    {
        $record = self::getOrCreateRecord($cycleId, $deptId);
        $record->status = self::STATUS_CLOSED;
        $record->closed_at = time();
        $record->closed_by = $userId;
        $saved = $record->save(false);

        if ($saved) {
            AuditLog::log('close_department_evaluation_cycle', 'DepartmentEvaluationCycle', $record->id, null, [
                'cycle_id' => $cycleId,
                'department_id' => $record->department_id,
            ]);
        }
        return $saved;
    }

    /**
     * HTML Badge for Status
     */
    public function getStatusBadge(): string
    {
        switch ($this->status) {
            case self::STATUS_ACTIVE:
                return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5"><i class="bi bi-play-circle-fill me-1"></i> เปิดรอบแล้ว (แบบประเมินล็อก)</span>';
            case self::STATUS_COMPLETED:
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5"><i class="bi bi-check-circle-fill me-1"></i> ประเมินครบ 100% แล้ว</span>';
            case self::STATUS_CLOSED:
                return '<span class="badge bg-secondary-subtle text-secondary border border-secondary px-2.5 py-1.5"><i class="bi bi-lock-fill me-1"></i> ปิดรอบแล้ว</span>';
            case self::STATUS_PENDING:
            default:
                return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5"><i class="bi bi-hourglass-split me-1"></i> ยังไม่เปิดรอบ (เตรียมแบบฟอร์ม)</span>';
        }
    }

    /**
     * Plain text label
     */
    public function getStatusLabel(): string
    {
        switch ($this->status) {
            case self::STATUS_ACTIVE:
                return 'เปิดรอบแล้ว';
            case self::STATUS_COMPLETED:
                return 'ประเมินครบ 100%';
            case self::STATUS_CLOSED:
                return 'ปิดรอบแล้ว';
            case self::STATUS_PENDING:
            default:
                return 'ยังไม่เปิดรอบ';
        }
    }
}
