<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Evaluation Model
 *
 * @property int $id
 * @property int $evaluation_cycle_id
 * @property int $personnel_id
 * @property int $template_version_id
 * @property int|null $evaluator_id
 * @property string $status
 * @property string|null $self_submitted_at
 * @property string|null $supervisor_evaluated_at
 * @property string|null $completed_at
 * @property string|null $returned_at
 * @property string|null $return_reason
 * @property string|null $supervisor_comment_strength
 * @property string|null $supervisor_comment_improvement
 * @property string|null $supervisor_comment_suggestion
 * @property string|null $employment_recommendation
 * @property string|null $acknowledgement_at
 * @property array|string|null $idp_data
 * @property int $created_at
 * @property int $updated_at
 */
class Evaluation extends ActiveRecord
{
    const STATUS_DRAFT = 'draft';
    const STATUS_SELF_ASSESSMENT = 'self_assessment';
    const STATUS_SUBMITTED = 'submitted'; // Legacy / General submitted
    const STATUS_SUBMITTED_L1 = 'submitted_l1'; // ส่งถึงหัวหน้างาน (L1)
    const STATUS_SUBMITTED_L2 = 'submitted_l2'; // ส่งถึงหัวหน้าฝ่าย (L2)
    const STATUS_SUPERVISOR_REVIEW = 'supervisor_review';
    const STATUS_EVALUATED = 'evaluated';
    const STATUS_COMMITTEE_REVIEW = 'committee_review';
    const STATUS_DIRECTOR_APPROVAL = 'director_approval';
    const STATUS_COMPLETED = 'completed'; // หัวหน้าฝ่ายประเมินเสร็จสมบูรณ์
    const STATUS_ACKNOWLEDGED = 'acknowledged'; // บุคลากรรับทราบผลแล้ว
    const STATUS_RETURNED = 'returned'; // ส่งกลับแก้ไข

    public static function tableName()
    {
        return '{{%evaluations}}';
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
            [['evaluation_cycle_id', 'personnel_id', 'template_version_id'], 'required'],
            [['evaluation_cycle_id', 'personnel_id', 'template_version_id', 'evaluator_id', 'evaluator_l1_id', 'evaluator_l2_id', 'committee_reviewer_id', 'director_approver_id'], 'integer'],
            [['self_submitted_at', 'supervisor_evaluated_at', 'l1_evaluated_at', 'l2_evaluated_at', 'committee_reviewed_at', 'director_approved_at', 'completed_at', 'returned_at', 'acknowledgement_at', 'idp_data'], 'safe'],
            [['return_reason', 'returned_by_role', 'supervisor_comment_strength', 'supervisor_comment_improvement', 'supervisor_comment_suggestion'], 'string'],
            [['l1_comment_strength', 'l1_comment_improvement', 'l1_comment_suggestion', 'l2_comment_strength', 'l2_comment_improvement', 'l2_comment_suggestion'], 'string'],
            [['status'], 'string', 'max' => 40],
            [['workflow_version'], 'string', 'max' => 20],
            [['employment_recommendation'], 'string', 'max' => 50],
            [['evaluation_cycle_id', 'personnel_id'], 'unique', 'targetAttribute' => ['evaluation_cycle_id', 'personnel_id']],
            [['evaluation_cycle_id'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluationCycle::class, 'targetAttribute' => ['evaluation_cycle_id' => 'id']],
            [['personnel_id'], 'exist', 'skipOnError' => true, 'targetClass' => Personnel::class, 'targetAttribute' => ['personnel_id' => 'id']],
            [['template_version_id'], 'exist', 'skipOnError' => true, 'targetClass' => TemplateVersion::class, 'targetAttribute' => ['template_version_id' => 'id']],
            [['evaluator_id'], 'exist', 'skipOnError' => true, 'targetClass' => Personnel::class, 'targetAttribute' => ['evaluator_id' => 'id']],
            [['evaluator_l1_id'], 'exist', 'skipOnError' => true, 'targetClass' => Personnel::class, 'targetAttribute' => ['evaluator_l1_id' => 'id']],
            [['evaluator_l2_id'], 'exist', 'skipOnError' => true, 'targetClass' => Personnel::class, 'targetAttribute' => ['evaluator_l2_id' => 'id']],
            [['committee_reviewer_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['committee_reviewer_id' => 'id']],
            [['director_approver_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['director_approver_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'evaluation_cycle_id' => 'รอบการประเมิน',
            'personnel_id' => 'ผู้รับการประเมิน',
            'template_version_id' => 'แบบฟอร์มการประเมิน',
            'evaluator_id' => 'ผู้ประเมินหลัก',
            'evaluator_l1_id' => 'หัวหน้างาน (ผู้ประเมินชั้นต้น L1)',
            'evaluator_l2_id' => 'หัวหน้าฝ่าย (ผู้ประเมินชั้นที่ 2 L2)',
            'status' => 'สถานะ',
            'self_submitted_at' => 'วันที่ส่งประเมินตนเอง',
            'supervisor_evaluated_at' => 'วันที่หัวหน้าประเมิน',
            'l1_evaluated_at' => 'วันที่หัวหน้างานประเมิน (L1)',
            'l2_evaluated_at' => 'วันที่หัวหน้าฝ่ายประเมิน (L2)',
            'completed_at' => 'วันที่เสร็จสมบูรณ์',
            'returned_at' => 'วันที่ส่งกลับแก้ไข',
            'return_reason' => 'เหตุผลการส่งกลับ',
            'returned_by_role' => 'ระดับที่ส่งกลับ (L1/L2)',
            'l1_comment_strength' => 'จุดเด่น/สิ่งที่ทำได้ดี (หัวหน้างาน L1)',
            'l1_comment_improvement' => 'จุดที่ควรปรับปรุง (หัวหน้างาน L1)',
            'l1_comment_suggestion' => 'ข้อเสนอแนะส่งเสริม (หัวหน้างาน L1)',
            'l2_comment_strength' => 'จุดเด่น/สิ่งที่ทำได้ดี (หัวหน้าฝ่าย L2)',
            'l2_comment_improvement' => 'จุดที่ควรปรับปรุง (หัวหน้าฝ่าย L2)',
            'l2_comment_suggestion' => 'ข้อเสนอแนะส่งเสริม (หัวหน้าฝ่าย L2)',
            'employment_recommendation' => 'ความเห็นเกี่ยวกับการจ้าง (พนักงานพิเศษ)',
            'acknowledgement_at' => 'วันที่ผู้รับการประเมินรับทราบผล',
        ];
    }

    public function getStatusLabel(string $extraClass = '')
    {
        $classSuffix = $extraClass !== '' ? ' ' . $extraClass : '';
        $map = [
            self::STATUS_DRAFT => '<span class="badge bg-secondary' . $classSuffix . '">แบบร่าง</span>',
            self::STATUS_SELF_ASSESSMENT => '<span class="badge bg-info text-dark' . $classSuffix . '">กำลังประเมินตนเอง</span>',
            self::STATUS_SUBMITTED => '<span class="badge bg-primary' . $classSuffix . '">ส่งแล้ว รอประเมิน</span>',
            self::STATUS_SUBMITTED_L1 => '<span class="badge bg-warning text-dark' . $classSuffix . '"><i class="bi bi-person-check me-1"></i>รอหัวหน้างานประเมิน (L1)</span>',
            self::STATUS_SUBMITTED_L2 => '<span class="badge bg-primary' . $classSuffix . '"><i class="bi bi-people-fill me-1"></i>รอหัวหน้าฝ่ายประเมิน/รวบรวม (L2)</span>',
            self::STATUS_SUPERVISOR_REVIEW => '<span class="badge bg-warning text-dark' . $classSuffix . '">หัวหน้ากำลังประเมิน</span>',
            self::STATUS_EVALUATED => '<span class="badge bg-info' . $classSuffix . '">ประเมินแล้ว</span>',
            'committee_review' => '<span class="badge bg-info text-dark' . $classSuffix . '"><i class="bi bi-people me-1"></i>รอคณะกรรมการกลั่นกรอง</span>',
            'director_approval' => '<span class="badge bg-warning text-dark' . $classSuffix . '"><i class="bi bi-award me-1"></i>รอผู้อำนวยการอนุมัติ</span>',
            self::STATUS_COMPLETED => '<span class="badge bg-success' . $classSuffix . '"><i class="bi bi-check2-all me-1"></i>เสร็จสมบูรณ์ (รอรับทราบผล)</span>',
            self::STATUS_ACKNOWLEDGED => '<span class="badge bg-success text-white' . $classSuffix . '"><i class="bi bi-shield-check me-1"></i>รับทราบผลแล้ว</span>',
            self::STATUS_RETURNED => '<span class="badge bg-danger' . $classSuffix . '"><i class="bi bi-arrow-counterclockwise me-1"></i>ส่งกลับแก้ไข</span>',
        ];
        return $map[$this->status] ?? $this->status;
    }

    public function getStatusBadge(string $extraClass = 'fs-6 px-3 py-2 shadow-sm')
    {
        return $this->getStatusLabel($extraClass);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_ACKNOWLEDGED], true);
    }

    public function getCycle()
    {
        return $this->hasOne(EvaluationCycle::class, ['id' => 'evaluation_cycle_id']);
    }

    public function getPersonnel()
    {
        return $this->hasOne(Personnel::class, ['id' => 'personnel_id']);
    }

    public function getEvaluator()
    {
        return $this->hasOne(Personnel::class, ['id' => 'evaluator_id']);
    }

    public function getEvaluatorL1()
    {
        return $this->hasOne(Personnel::class, ['id' => 'evaluator_l1_id']);
    }

    public function getEvaluatorL2()
    {
        return $this->hasOne(Personnel::class, ['id' => 'evaluator_l2_id']);
    }

    public function getTemplateVersion()
    {
        return $this->hasOne(TemplateVersion::class, ['id' => 'template_version_id']);
    }

    public function getAnswers()
    {
        return $this->hasMany(EvaluationAnswer::class, ['evaluation_id' => 'id']);
    }

    public function getCompetencyAnswers()
    {
        return $this->hasMany(EvaluationCompetencyAnswer::class, ['evaluation_id' => 'id']);
    }

    public function getResult()
    {
        return $this->hasOne(EvaluationResult::class, ['evaluation_id' => 'id']);
    }

    public function getEvidenceFiles()
    {
        return $this->hasMany(EvidenceFile::class, ['evaluation_id' => 'id'])->where(['deleted_at' => null]);
    }

    public function getDirectorApprover()
    {
        return $this->hasOne(User::class, ['id' => 'director_approver_id']);
    }
}
