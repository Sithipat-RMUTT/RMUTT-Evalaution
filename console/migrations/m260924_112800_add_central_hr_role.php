<?php

use yii\db\Migration;

/**
 * Adds 'central_hr' role to RBAC auth_item and auth_item_child.
 */
class m260924_112800_add_central_hr_role extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        // Check if central_hr role already exists
        $centralHr = $auth->getRole('central_hr');
        if (!$centralHr) {
            $centralHr = $auth->createRole('central_hr');
            $centralHr->description = 'ผู้ดูแลระบบส่วนกลาง กองบริหารงานบุคคล (Central HR Admin)';
            $auth->add($centralHr);
        }

        $superadmin = $auth->getRole('superadmin');
        $admin = $auth->getRole('admin');

        // Hierarchy: superadmin -> central_hr -> admin
        if ($admin && !$auth->hasChild($centralHr, $admin)) {
            $auth->addChild($centralHr, $admin);
        }
        if ($superadmin && !$auth->hasChild($superadmin, $centralHr)) {
            $auth->addChild($superadmin, $centralHr);
        }

        // Update admin_hr user assignment to central_hr if exists
        $adminHrUser = \common\models\User::findOne(['username' => 'admin_hr']);
        if ($adminHrUser) {
            if (!$auth->getAssignment('central_hr', $adminHrUser->id)) {
                $auth->assign($centralHr, $adminHrUser->id);
            }
        }
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $centralHr = $auth->getRole('central_hr');
        if ($centralHr) {
            $auth->remove($centralHr);
        }
    }
}
