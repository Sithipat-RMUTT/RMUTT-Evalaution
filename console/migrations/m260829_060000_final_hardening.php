<?php
use yii\db\Migration;
use common\services\DataProtectionService;

class m260829_060000_final_hardening extends Migration
{
    public function safeUp()
    {
        // Encrypt legacy plaintext citizen IDs before clearing the legacy column.
        $schema=$this->db->getTableSchema('{{%personnel}}',true);
        if($schema && isset($schema->columns['citizen_id'])){
            foreach($this->db->createCommand("SELECT id,citizen_id,citizen_id_encrypted,citizen_id_blind_index FROM {{%personnel}} WHERE citizen_id IS NOT NULL AND citizen_id <> ''")->queryAll() as $row){
                $encrypted=DataProtectionService::encrypt($row['citizen_id']);
                $blind=DataProtectionService::blindIndex($row['citizen_id']);
                $this->update('{{%personnel}}',['citizen_id'=>null,'citizen_id_encrypted'=>$encrypted,'citizen_id_blind_index'=>$blind],['id'=>$row['id']]);
            }
        }
        if ($schema && isset($schema->columns['citizen_id_encrypted']) && isset($schema->columns['citizen_id_blind_index'])) {
            foreach($this->db->createCommand("SELECT id,citizen_id_encrypted FROM {{%personnel}} WHERE citizen_id_encrypted IS NOT NULL AND citizen_id_encrypted <> ''")->queryAll() as $row){
                $plain=DataProtectionService::decrypt($row['citizen_id_encrypted']);
                if($plain===null) throw new \RuntimeException('ไม่สามารถถอดรหัส Citizen ID ระหว่าง migration ได้');
                $this->update('{{%personnel}}',['citizen_id_blind_index'=>DataProtectionService::blindIndex($plain)],['id'=>$row['id']]);
            }
        }
    }
    public function safeDown(){ /* Data migration is intentionally irreversible. */ }
}
