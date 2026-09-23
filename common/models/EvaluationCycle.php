<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * EvaluationCycle Model
 *
 * @property int $id
 * @property string $name_th
 * @property int $cycle_number
 * @property int $fiscal_year
 * @property string $period_start
 * @property string $period_end
 * @property string $self_assessment_start
 * @property string $self_assessment_end
 * @property string $supervisor_eval_start
 * @property string $supervisor_eval_end
 * @property string $status
 * @property string|null $description
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 */
class EvaluationCycle extends ActiveRecord
{
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_EVALUATION = 'evaluation';
    const STATUS_CLOSED = 'closed';
    const STATUS_ARCHIVED = 'archived';

    public static function tableName()
    {
        return '{{%evaluation_cycles}}';
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
            [['name_th', 'cycle_number', 'fiscal_year', 'period_start', 'period_end', 'self_assessment_start', 'self_assessment_end', 'supervisor_eval_start', 'supervisor_eval_end'], 'required'],
            [['cycle_number', 'fiscal_year', 'created_by'], 'integer'],
            [['period_start', 'period_end', 'self_assessment_start', 'self_assessment_end', 'supervisor_eval_start', 'supervisor_eval_end'], 'safe'],
            [['description'], 'string'],
            [['name_th'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 30],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            ['period_end', 'validateDateOrder', 'params' => ['compareWith' => 'period_start', 'label' => 'วันสิ้นสุดรอบประเมินต้องอยู่หลังวันเริ่มต้นรอบประเมิน']],
            ['self_assessment_end', 'validateDateOrder', 'params' => ['compareWith' => 'self_assessment_start', 'label' => 'วันสิ้นสุดการประเมินตนเองต้องอยู่หลังวันเริ่มต้นประเมินตนเอง']],
            ['self_assessment_start', 'validateTimelineOrder', 'params' => ['compareWith' => 'period_start', 'isGreaterOrEqual' => true, 'label' => 'วันเริ่มเปิดประเมินตนเองต้องไม่ก่อนวันเริ่มต้นรอบประเมิน']],
            ['supervisor_eval_start', 'validateTimelineOrder', 'params' => ['compareWith' => 'self_assessment_start', 'isGreaterOrEqual' => true, 'label' => 'วันเริ่มประเมินโดยหัวหน้าต้องไม่ก่อนวันเริ่มเปิดประเมินตนเอง']],
            ['period_end', 'validateTimelineOrder', 'params' => ['compareWith' => 'self_assessment_end', 'isGreaterOrEqual' => true, 'label' => 'วันสิ้นสุดรอบประเมินต้องไม่ก่อนวันสิ้นสุดการประเมินตนเอง']],
            ['period_end', 'validateTimelineOrder', 'params' => ['compareWith' => 'supervisor_eval_end', 'isGreaterOrEqual' => true, 'label' => 'วันสิ้นสุดรอบประเมินต้องไม่ก่อนวันสิ้นสุดการประเมินโดยหัวหน้า']],
        ];
    }

    public function validateDateOrder($attribute, $params)
    {
        $compareField = $params['compareWith'];
        if (!empty($this->$attribute) && !empty($this->$compareField)) {
            if (strtotime((string)$this->$attribute) <= strtotime((string)$this->$compareField)) {
                $this->addError($attribute, $params['label'] ?? "{$attribute} ต้องอยู่หลัง {$compareField}");
            }
        }
    }

    public function validateTimelineOrder($attribute, $params)
    {
        $compareField = $params['compareWith'];
        if (!empty($this->$attribute) && !empty($this->$compareField)) {
            $tAttr = strtotime((string)$this->$attribute);
            $tComp = strtotime((string)$this->$compareField);
            if (!empty($params['isGreaterOrEqual'])) {
                if ($tAttr < $tComp) {
                    $this->addError($attribute, $params['label'] ?? "{$attribute} ต้องอยู่หลังหรือตรงกับ {$compareField}");
                }
            } else {
                if ($tAttr <= $tComp) {
                    $this->addError($attribute, $params['label'] ?? "{$attribute} ต้องอยู่หลัง {$compareField}");
                }
            }
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name_th' => 'ชื่อรอบการประเมิน',
            'cycle_number' => 'รอบที่ (1 หรือ 2)',
            'fiscal_year' => 'ปีงบประมาณ (พ.ศ.)',
            'period_start' => 'วันเริ่มต้นรอบประเมิน',
            'period_end' => 'วันสิ้นสุดรอบประเมิน',
            'self_assessment_start' => 'เริ่มเปิดประเมินตนเอง',
            'self_assessment_end' => 'สิ้นสุดการประเมินตนเอง',
            'supervisor_eval_start' => 'เริ่มการประเมินโดยหัวหน้า',
            'supervisor_eval_end' => 'สิ้นสุดการประเมินโดยหัวหน้า',
            'status' => 'สถานะรอบการประเมิน',
            'description' => 'หมายเหตุ/รายละเอียด',
        ];
    }

    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_DRAFT => '<span class="badge bg-secondary">ร่าง (Draft)</span>',
            self::STATUS_ACTIVE => '<span class="badge bg-primary">เปิดประเมินตนเอง</span>',
            self::STATUS_EVALUATION => '<span class="badge bg-warning text-dark">หัวหน้าประเมิน</span>',
            self::STATUS_CLOSED => '<span class="badge bg-success">ปิดรอบประเมิน</span>',
            self::STATUS_ARCHIVED => '<span class="badge bg-dark">จัดเก็บถาวร</span>',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getTemplateMappings()
    {
        return $this->hasMany(CycleTemplateMapping::class, ['evaluation_cycle_id' => 'id']);
    }

    public function getEvaluations()
    {
        return $this->hasMany(Evaluation::class, ['evaluation_cycle_id' => 'id']);
    }

    /**
     * Resolve the appropriate TemplateVersion for a given personnel
     * Priority:
     * 1. Specific Cycle Mapping for Personnel's Department + Personnel Type
     * 2. General Cycle Mapping for Personnel Type (any department)
     * 3. Specific Department Template (Active Version)
     * 4. Default Master Template for Personnel Type (Active Version)
     *
     * @param Personnel $personnel
     * @return TemplateVersion|null
     */
    public function getTemplateVersionForPersonnel($personnel)
    {
        if (!$personnel) {
            return null;
        }

        // 1. Check Specific Cycle Mapping for Department + Personnel Type
        if (!empty($personnel->department_id)) {
            $mapping = CycleTemplateMapping::findOne([
                'evaluation_cycle_id' => $this->id,
                'department_id' => $personnel->department_id,
                'personnel_type_id' => $personnel->personnel_type_id,
            ]);
            if ($mapping && $mapping->templateVersion) {
                return $mapping->templateVersion;
            }
        }

        // 2. Check General Cycle Mapping (department_id IS NULL)
        $mapping = CycleTemplateMapping::find()
            ->where([
                'evaluation_cycle_id' => $this->id,
                'personnel_type_id' => $personnel->personnel_type_id,
            ])
            ->andWhere(['or', ['department_id' => null], ['department_id' => 0]])
            ->one();

        if ($mapping && $mapping->templateVersion) {
            return $mapping->templateVersion;
        }

        // 3. Fallback: Search Specific Department Template
        if (!empty($personnel->department_id)) {
            $tmpl = EvaluationTemplate::find()
                ->where([
                    'department_id' => $personnel->department_id,
                    'personnel_type_id' => $personnel->personnel_type_id,
                    'status' => 1,
                ])
                ->one();
            if ($tmpl && $tmpl->activeVersion) {
                return $tmpl->activeVersion;
            }
        }

        // 4. Fallback: Default Master Template
        $defaultTmpl = EvaluationTemplate::find()
            ->where([
                'personnel_type_id' => $personnel->personnel_type_id,
                'status' => 1,
            ])
            ->orderBy(['is_default' => SORT_DESC, 'id' => SORT_ASC])
            ->one();

        return $defaultTmpl ? $defaultTmpl->activeVersion : null;
    }
}
