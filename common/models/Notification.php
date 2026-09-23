<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Notification Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property int|null $related_id
 * @property string|null $related_type
 * @property int $is_read
 * @property string|null $read_at
 * @property int $created_at
 * @property int $updated_at
 */
class Notification extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%notifications}}';
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
            [['user_id', 'type', 'title', 'message'], 'required'],
            [['user_id', 'related_id', 'is_read'], 'integer'],
            [['message'], 'string'],
            [['read_at'], 'safe'],
            [['type', 'related_type'], 'string', 'max' => 50],
            [['title'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public static function send($userId, $type, $title, $message, $relatedId = null, $relatedType = null)
    {
        $n = new self();
        $n->user_id = $userId;
        $n->type = $type;
        $n->title = $title;
        $n->message = $message;
        $n->related_id = $relatedId;
        $n->related_type = $relatedType;
        $n->is_read = 0;
        return $n->save(false);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
