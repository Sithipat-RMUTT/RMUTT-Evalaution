<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Department Model
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string|null $code
 * @property string $name_th
 * @property string|null $name_en
 * @property int $sort_order
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class Department extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%departments}}';
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
            [['parent_id', 'sort_order', 'status'], 'integer'],
            [['code'], 'string', 'max' => 50],
            [['name_th', 'name_en'], 'string', 'max' => 255],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Department::class, 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'หน่วยงานแม่/สังกัด',
            'code' => 'รหัสหน่วยงาน',
            'name_th' => 'ชื่อหน่วยงาน/ฝ่าย/งาน',
            'name_en' => 'ชื่อภาษาอังกฤษ',
            'sort_order' => 'ลำดับ',
            'status' => 'สถานะ',
        ];
    }

    public function getParent()
    {
        return $this->hasOne(Department::class, ['id' => 'parent_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(Department::class, ['parent_id' => 'id']);
    }

    public function getPersonnel()
    {
        return $this->hasMany(Personnel::class, ['department_id' => 'id']);
    }

    /**
     * Get all department IDs within the same organization (root + all sub-departments)
     *
     * @param int|null $deptId
     * @return int[]
     */
    public static function getAllScopedDeptIds($deptId)
    {
        if (!$deptId) {
            return [];
        }
        $dept = self::findOne($deptId);
        if (!$dept) {
            return [(int)$deptId];
        }

        $rootId = $dept->parent_id ?: $dept->id;
        $childIds = self::find()->select('id')->where(['parent_id' => $rootId, 'status' => 1])->column();
        $allIds = array_merge([(int)$rootId], array_map('intval', $childIds));
        return array_values(array_unique($allIds));
    }

    /**
     * Get list of departments within the organization (for dropdown filter)
     *
     * @param int|null $deptId
     * @return Department[]
     */
    public static function getScopedDepartments($deptId)
    {
        if (!$deptId) {
            return [];
        }
        $dept = self::findOne($deptId);
        if (!$dept) {
            return [];
        }

        $rootId = $dept->parent_id ?: $dept->id;
        return self::find()
            ->where(['parent_id' => $rootId, 'status' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name_th' => SORT_ASC])
            ->all();
    }

    /**
     * Check if the currently logged-in user is Central Admin (Superadmin or Central HR Admin)
     *
     * @return bool
     */
    public static function isCentralAdmin(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        if (Yii::$app->user->can('superadmin') || Yii::$app->user->can('central_hr')) {
            return true;
        }
        $user = Yii::$app->user->identity;
        if ($user && $user->department) {
            $code = strtoupper((string)$user->department->code);
            if (in_array($code, ['HR', 'CENTRAL'], true) && Yii::$app->user->can('admin')) {
                return true;
            }
        }
        $username = $user ? $user->username : '';
        if (in_array($username, ['admin', 'admin_hr'], true) && Yii::$app->user->can('admin')) {
            return true;
        }
        $currPersonnel = Personnel::findOne(['user_id' => Yii::$app->user->id]);
        if ($currPersonnel && $currPersonnel->department) {
            $code = strtoupper((string)$currPersonnel->department->code);
            if (in_array($code, ['HR', 'CENTRAL'], true) && Yii::$app->user->can('admin')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get department ID for the currently logged-in user (admin or personnel)
     *
     * @return int|null
     */
    public static function getCurrentUserDeptId(): ?int
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }
        $user = Yii::$app->user->identity;
        if ($user && !empty($user->department_id)) {
            return (int)$user->department_id;
        }
        $username = $user ? $user->username : '';
        if ($username === 'admin_arit') {
            $arit = self::findOne(['code' => 'ARIT']);
            return $arit ? (int)$arit->id : null;
        }
        $currPersonnel = Personnel::findOne(['user_id' => Yii::$app->user->id]);
        return $currPersonnel ? (int)$currPersonnel->department_id : null;
    }
}
