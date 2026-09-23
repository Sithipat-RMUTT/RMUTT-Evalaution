<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * AuditLog Model
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int $created_at
 */
class AuditLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%audit_logs}}';
    }

    public function rules()
    {
        return [
            [['action', 'created_at'], 'required'],
            [['user_id', 'entity_id', 'created_at'], 'integer'],
            [['old_values', 'new_values'], 'safe'],
            [['action', 'entity_type'], 'string', 'max' => 100],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 500],
        ];
    }

    public static function log($action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null)
    {
        $log = new self();
        $log->user_id = Yii::$app->user->id ?? null;
        $log->action = $action;
        $log->entity_type = $entityType;
        $log->entity_id = $entityId;
        $log->old_values = $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
        $log->new_values = $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;
        $log->ip_address = Yii::$app->request->userIP ?? null;
        $log->user_agent = Yii::$app->request->userAgent ?? null;
        $log->created_at = time();
        $log->save(false);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
