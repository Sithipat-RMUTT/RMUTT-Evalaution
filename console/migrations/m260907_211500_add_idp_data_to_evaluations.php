<?php

use yii\db\Migration;

/**
 * Class m260907_211500_add_idp_data_to_evaluations
 */
class m260907_211500_add_idp_data_to_evaluations extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->getTableSchema('{{%evaluations}}');
        if (!isset($table->columns['idp_data'])) {
            $this->addColumn('{{%evaluations}}', 'idp_data', $this->json()->null()->after('return_reason'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = $this->db->getTableSchema('{{%evaluations}}');
        if (isset($table->columns['idp_data'])) {
            $this->dropColumn('{{%evaluations}}', 'idp_data');
        }
    }
}
