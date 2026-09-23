<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use common\models\User;
use common\models\Department;

/**
 * AdminUserForm is the model behind the create/update admin user form.
 */
class AdminUserForm extends Model
{
    public $id;
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
            [['username', 'display_name', 'email', 'role'], 'required'],
            [['username', 'display_name', 'email'], 'trim'],
            ['username', 'string', 'min' => 3, 'max' => 50],
            ['username', 'match', 'pattern' => '/^[a-zA-Z0-9_-]+$/', 'message' => 'ชื่อผู้ใช้งานต้องเป็นตัวอักษรภาษาอังกฤษ ตัวเลข ขีดล่าง หรือขีดกลางเท่านั้น'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['display_name', 'string', 'max' => 255],
            ['role', 'in', 'range' => ['superadmin', 'admin']],
            ['department_id', 'integer'],
            ['department_id', 'exist', 'skipOnError' => true, 'targetClass' => Department::class, 'targetAttribute' => ['department_id' => 'id']],
            ['status', 'in', 'range' => [User::STATUS_ACTIVE, User::STATUS_INACTIVE]],
            
            // Password rules: required on create, optional on update
            ['password', 'string', 'min' => 6],
            ['password', 'required', 'when' => function ($model) {
                return empty($model->id);
            }],

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
            'username' => 'ชื่อผู้ใช้งาน (Username)',
            'display_name' => 'ชื่อ - นามสกุล / คำอธิบาย',
            'email' => 'อีเมล (Email)',
            'password' => 'รหัสผ่าน (Password)',
            'role' => 'ระดับสิทธิ์ (Admin Role)',
            'department_id' => 'หน่วยงานที่สังกัด (Department Scope)',
            'status' => 'สถานะการใช้งาน',
        ];
    }

    public function validateUniqueUsername($attribute, $params)
    {
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

        $isNew = empty($this->id);
        $user = $isNew ? new User() : ($this->_user ?: User::findOne($this->id));
        if (!$user) {
            return null;
        }

        $user->username = $this->username;
        $user->display_name = $this->display_name;
        $user->email = $this->email;
        $user->department_id = $this->role === 'superadmin' ? null : ($this->department_id ?: null);
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

        // Sync RBAC role
        $auth = Yii::$app->authManager;
        $auth->revokeAll($user->id);
        $roleItem = $auth->getRole($this->role);
        if ($roleItem) {
            $auth->assign($roleItem, $user->id);
        }

        return $user;
    }
}
