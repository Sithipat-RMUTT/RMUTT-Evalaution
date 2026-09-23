<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * EvidenceFile Model
 *
 * @property int $id
 * @property int $evaluation_id
 * @property int|null $evaluation_item_id
 * @property string $original_name
 * @property string $stored_name
 * @property string $file_path
 * @property int $file_size
 * @property string $file_type
 * @property string $file_hash
 * @property int $uploaded_by
 * @property string|null $deleted_at
 * @property int $created_at
 * @property int $updated_at
 */
class EvidenceFile extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%evidence_files}}';
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
            [['evaluation_id', 'original_name', 'stored_name', 'file_path', 'file_size', 'file_type', 'file_hash', 'uploaded_by'], 'required'],
            [['evaluation_id', 'evaluation_item_id', 'file_size', 'uploaded_by'], 'integer'],
            [['deleted_at'], 'safe'],
            [['original_name', 'stored_name'], 'string', 'max' => 255],
            [['file_path'], 'string', 'max' => 500],
            [['file_type'], 'string', 'max' => 100],
            [['file_hash'], 'string', 'max' => 64],
            [['evaluation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Evaluation::class, 'targetAttribute' => ['evaluation_id' => 'id']],
            [['evaluation_item_id'], 'exist', 'skipOnError' => true, 'targetClass' => EvaluationItem::class, 'targetAttribute' => ['evaluation_item_id' => 'id']],
            [['uploaded_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['uploaded_by' => 'id']],
        ];
    }

    public function getFormattedSize()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    public function getEvaluation()
    {
        return $this->hasOne(Evaluation::class, ['id' => 'evaluation_id']);
    }

    public function getItem()
    {
        return $this->hasOne(EvaluationItem::class, ['id' => 'evaluation_item_id']);
    }

    public function getUploader()
    {
        return $this->hasOne(User::class, ['id' => 'uploaded_by']);
    }

    public function getFileUrl()
    {
        return Yii::$app->urlManager->createUrl(['evaluation/download-evidence', 'id' => $this->id]);
    }

    public function isImage()
    {
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) || str_starts_with($this->file_type, 'image/');
    }

    public function isPdf()
    {
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
        return $ext === 'pdf' || $this->file_type === 'application/pdf';
    }

    public function getDescription()
    {
        return $this->original_name;
    }

    public function getOriginal_filename()
    {
        return $this->original_name;
    }
}
