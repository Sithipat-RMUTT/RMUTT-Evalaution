<?php
namespace console\controllers;

use yii\console\Controller;
use common\models\Personnel;
use common\services\DataProtectionService;

class ProtectPersonnelDataController extends Controller
{
    public function actionIndex(): int
    {
        $this->stdout("Encrypting existing citizen IDs...\n");
        $n = 0;
        foreach (Personnel::find()->where(['and', ['is not', 'citizen_id', null], ['<>', 'citizen_id', '']])->each(100) as $p) {
            $plain = $p->citizen_id;
            $p->citizen_id_blind_index = DataProtectionService::blindIndex($plain);
            $p->citizen_id_encrypted = DataProtectionService::encrypt($plain);
            $p->citizen_id = null;
            if (!$p->save(false, ['citizen_id', 'citizen_id_encrypted', 'citizen_id_blind_index'])) {
                $this->stderr("Failed ID {$p->id}\n");
                return 1;
            }
            $n++;
        }
        $this->stdout("Protected {$n} personnel records.\n");
        return 0;
    }
}
