<?php
use yii\db\Migration;

class m260828_180100_protect_citizen_id extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%personnel}}', true);
        if ($schema) {
            if (!isset($schema->columns['citizen_id_encrypted'])) {
                $this->addColumn('{{%personnel}}', 'citizen_id_encrypted', $this->string(500)->null()->after('citizen_id'));
            }
            if (!isset($schema->columns['citizen_id_blind_index'])) {
                $this->addColumn('{{%personnel}}', 'citizen_id_blind_index', $this->string(64)->null()->after('citizen_id_encrypted'));
            }
        }
        $indexes = $this->db->createCommand("SHOW INDEX FROM {{%personnel}} WHERE Key_name = 'uq-personnel-citizen-blind'")->queryAll();
        if (empty($indexes)) {
            $this->createIndex('uq-personnel-citizen-blind', '{{%personnel}}', 'citizen_id_blind_index', true);
        }
    }

    public function safeDown()
    {
    }
}
