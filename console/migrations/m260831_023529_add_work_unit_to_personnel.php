<?php

use yii\db\Migration;

class m260831_023529_add_work_unit_to_personnel extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%personnel}}', 'work_unit', $this->string(150)->null()->defaultValue(null)->after('department_id'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%personnel}}', 'work_unit');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260831_023529_add_work_unit_to_personnel cannot be reverted.\n";

        return false;
    }
    */
}
