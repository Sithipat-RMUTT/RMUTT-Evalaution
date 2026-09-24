<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use common\models\User;
use common\models\Personnel;
use common\models\Department;
use common\models\AuditLog;

/**
 * AdminUserForm is the model behind the create/update admin user form.
 * Supports appointing an existing Personnel or creating a dedicated standalone admin.
 */
class AdminUserForm extends Model
{
    public const MODE_APPOINT = 'appoint';
    public const MODE_STANDALONE = 'standalone';

    public $id;
    public $create_mode = self::MODE_APPOINT;
    public $personnel_id;
    public $username;
    public $display_name;
    public $email;
    public $password;
    public $role = 'admin';
    public $department_id;
    public $status = User::STATUS_ACTIVE;

    private $_user;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['create_mode', 'in', 'range' => [self::MODE_APPOINT, self::MODE_STANDALONE]],
            ['role', 'required', 'message' => 'กรุณาเลือกระดับสิทธิ์'],
            ['role', 'in', 'range' => ['superadmin', 'admin']],
            ['department_id', 'integer'],
            ['department_id', 'exist', 'skipOnError' => true, 'targetClass' => Department::class, 'targetAttribute' => ['department_id' => 'id']],
            ['status', 'in', 'range' => [User::STATUS_ACTIVE, User::STATUS_INACTIVE]],

            // Appoint mode rules (new record only)
            ['personnel_id', 'required', 'when' => function ($model) {
                return empty($model->id) && $model->create_mode === self::MODE_APPOINT;
            }, 'message' => 'กรุณาเลือกบุคลากรที่ต้องการแต่งตั้งเป็นผู้ดูแลระบบ'],
            ['personnel_id', 'integer'],
            ['personnel_id', 'validatePersonnelForAppoint'],

            // Standalone or Update rules
            [['username', 'display_name', 'email'], 'trim'],
            [['username', 'display_name', 'email'], 'required', 'when' => function ($model) {
                return !empty($model->id) || $model->create_mode === self::MODE_STANDALONE;
            }, 'message' => '{attribute} ต้องไม่เว้นว่าง'],
            ['username', 'string', 'min' => 3, 'max' => 50],
            ['username', 'match', 'pattern' => '/^[a-zA-Z0-9_-]+$/', 'message' => 'ชื่อผู้ใช้งานต้องเป็นตัวอักษรภาษาอังกฤษ ตัวเลข ขีดล่าง หรือขีดกลางเท่านั้น'],
            ['email', 'email', 'message' => 'รูปแบบอีเมลไม่ถูกต้อง'],
            ['email', 'string', 'max' => 255],
            ['display_name', 'string', 'max' => 255],

            // Password rules
            ['password', 'string', 'min' => 6, 'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร'],
            ['password', 'required', 'when' => function ($model) {
                return empty($model->id) && $model->create_mode === self::MODE_STANDALONE;
            }, 'message' => 'กรุณากำหนดรหัสผ่านสำหรับบัญชีใหม่'],

            // Unique validations
            ['username', 'validateUniqueUsername'],
            ['email', 'validateUniqueEmail'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'create_mode' => 'รูปแบบการเพิ่มผู้ดูแลระบบ',
            'personnel_id' => 'เลือกบุคลากรในระบบ',
            'username' => 'ชื่อผู้ใช้งาน (Username)',
            'display_name' => 'ชื่อ - นามสกุล / คำอธิบาย',
            'email' => 'อีเมล (Email)',
            'password' => 'รหัสผ่าน (Password)',
            'role' => 'ระดับสิทธิ์ (Admin Role)',
            'department_id' => 'หน่วยงานที่สังกัด (Department Scope)',
            'status' => 'สถานะการใช้งาน',
        ];
    }

    /**
     * Validates that selected personnel is eligible to be appointed as admin.
     */
    public function validatePersonnelForAppoint($attribute, $params)
    {
        if (!empty($this->id) || $this->create_mode !== self::MODE_APPOINT) {
            return;
        }

        $personnel = Personnel::findOne($this->$attribute);
        if (!$personnel) {
            $this->addError($attribute, 'ไม่พบข้อมูลบุคลากรที่เลือก');
            return;
        }

        if (empty($personnel->user_id)) {
            $this->addError($attribute, 'บุคลากรท่านนี้ยังไม่มีบัญชีผู้ใช้งานระบบ');
            return;
        }

        $auth = Yii::$app->authManager;
        $roles = array_keys($auth->getRolesByUser($personnel->user_id));
        if (in_array('superadmin', $roles, true) || in_array('admin', $roles, true)) {
            $this->addError($attribute, 'บุคลากรท่านนี้มีสิทธิ์เป็นผู้ดูแลระบบอยู่แล้วในระบบ');
        }
    }

    public function validateUniqueUsername($attribute, $params)
    {
        if (empty($this->id) && $this->create_mode === self::MODE_APPOINT) {
            return;
        }

        $query = User::find()->where(['username' => $this->$attribute]);
        if (!empty($this->id)) {
            $query->andWhere(['!=', 'id', $this->id]);
        }
        if ($query->exists()) {
            $this->addError($attribute, 'ชื่อผู้ใช้งานนี้ถูกใช้งานแล้วในระบบ');
        }
    }

    public function validateUniqueEmail($attribute, $params)
    {
        if (empty($this->id) && $this->create_mode === self::MODE_APPOINT) {
            return;
        }

        $query = User::find()->where(['email' => $this->$attribute]);
        if (!empty($this->id)) {
            $query->andWhere(['!=', 'id', $this->id]);
        }
        if ($query->exists()) {
            $this->addError($attribute, 'อีเมลนี้ถูกใช้งานแล้วในระบบ');
        }
    }

    /**
     * Loads existing User model data into the form.
     *
     * @param User $user
     */
    public function loadUser(User $user)
    {
        $this->_user = $user;
        $this->id = $user->id;
        $this->username = $user->username;
        $this->display_name = $user->display_name;
        $this->email = $user->email;
        $this->department_id = $user->department_id;
        $this->status = $user->status;

        if ($user->personnel) {
            $this->personnel_id = $user->personnel->id;
            $this->display_name = $user->personnel->fullName;
            $this->create_mode = self::MODE_APPOINT;
        } else {
            $this->create_mode = self::MODE_STANDALONE;
        }

        $roles = array_keys(Yii::$app->authManager->getRolesByUser($user->id));
        if (in_array('superadmin', $roles, true)) {
            $this->role = 'superadmin';
        } else {
            $this->role = 'admin';
        }
    }

    /**
     * Saves the admin user.
     *
     * @return User|null
     */
    public function save()
    {
        if (!$this->validate()) {
            return null;
        }

        $auth = Yii::$app->authManager;

        // MODE 1: Appoint existing personnel as admin
        if (empty($this->id) && $this->create_mode === self::MODE_APPOINT) {
            $personnel = Personnel::findOne($this->personnel_id);
            if (!$personnel || empty($personnel->user_id)) {
                $this->addError('personnel_id', 'ไม่พบบัญชีผู้ใช้งานของบุคลากร');
                return null;
            }

            $user = User::findOne($personnel->user_id);
            if (!$user) {
                $this->addError('personnel_id', 'ไม่พบบัญชีผู้ใช้งานของบุคลากรในระบบ');
                return null;
            }

            // Department scope
            $targetDeptId = ($this->role === 'superadmin') ? null : ($this->department_id ?: $personnel->department_id);
            $user->department_id = $targetDeptId;
            $user->save(false);

            // Assign admin role
            $roleItem = $auth->getRole($this->role);
            if ($roleItem && !$auth->getAssignment($this->role, $user->id)) {
                $auth->assign($roleItem, $user->id);
            }

            AuditLog::log('appoint_admin', 'User', $user->id, [
                'personnel_id' => $personnel->id,
                'name' => $personnel->fullName,
                'role' => $this->role,
                'department_id' => $user->department_id,
            ]);

            return $user;
        }

        // MODE 2 & UPDATE: Standalone admin or update existing admin
        $isNew = empty($this->id);
        $user = $isNew ? new User() : ($this->_user ?: User::findOne($this->id));
        if (!$user) {
            return null;
        }

        $user->username = $this->username;
        $user->display_name = $this->display_name;
        $user->email = $this->email;
        $user->department_id = ($this->role === 'superadmin') ? null : ($this->department_id ?: null);
        $user->status = (int)$this->status;

        if (!empty($this->password)) {
            $user->setPassword($this->password);
        }
        if ($isNew) {
            $user->generateAuthKey();
        }

        if (!$user->save()) {
            $this->addErrors($user->getErrors());
            return null;
        }

        // Sync admin role (revoke old admin/superadmin roles first to switch cleanly)
        $superRole = $auth->getRole('superadmin');
        $adminRole = $auth->getRole('admin');
        if ($superRole) $auth->revoke($superRole, $user->id);
        if ($adminRole) $auth->revoke($adminRole, $user->id);

        $newRoleItem = $auth->getRole($this->role);
        if ($newRoleItem) {
            $auth->assign($newRoleItem, $user->id);
        }

        // Preserve base role if user is also personnel
        if ($user->personnel) {
            $baseRole = $user->personnel->is_supervisor ? $auth->getRole('supervisor') : $auth->getRole('personnel');
            if ($baseRole && !$auth->getAssignment($baseRole->name, $user->id)) {
                $auth->assign($baseRole, $user->id);
            }
        }

        AuditLog::log($isNew ? 'create_standalone_admin' : 'update_admin', 'User', $user->id, [
            'username' => $user->username,
            'role' => $this->role,
            'department_id' => $user->department_id,
        ]);

        return $user;
    }
}
