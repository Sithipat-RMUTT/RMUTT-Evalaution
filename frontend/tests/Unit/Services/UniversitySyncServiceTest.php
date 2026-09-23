<?php

namespace frontend\tests\Unit\Services;

use Codeception\Test\Unit;
use common\models\User;
use common\models\Personnel;
use common\models\Department;
use common\models\PersonnelType;
use common\services\UniversitySyncService;

class UniversitySyncServiceTest extends Unit
{
    public function testSyncPersonnelCreatesUserWithPersonnelRoleAndMapsTemplate()
    {
        $testCode = 'TEST_SYNC_' . time();
        $testUsername = 'sync_user_' . time();
        $testEmail = $testUsername . '@rmutt.ac.th';

        $data = [
            'employee_code' => $testCode,
            'username' => $testUsername,
            'email' => $testEmail,
            'prefix_th' => 'อาจารย์ ดร.',
            'first_name_th' => 'ทดสอบซิงค์',
            'last_name_th' => 'ข้อมูลกลาง',
            'citizen_id' => '1234567890123',
            'personnel_type_code' => 'CIVIL',
            'department_code' => 'ARIT-IT',
            'position_name' => 'นักวิชาการคอมพิวเตอร์',
        ];

        $res = UniversitySyncService::syncPersonnelRecord($data, true);

        $this->assertTrue($res['success'], 'Sync should succeed: ' . ($res['message'] ?? ''));
        $personnel = $res['personnel'];
        $this->assertNotNull($personnel);
        $this->assertEquals($testCode, $personnel->employee_code);
        $this->assertEquals('ทดสอบซิงค์', $personnel->first_name_th);

        // Check user creation
        $user = User::findByUsername($testUsername);
        $this->assertNotNull($user);
        $this->assertEquals($testEmail, $user->email);

        // Check RBAC assignment: universal personnel role
        $auth = \Yii::$app->authManager;
        $this->assertNotNull($auth->getAssignment('personnel', $user->id));

        // Clean up test data
        if ($personnel) {
            $personnel->delete();
        }
        if ($user && $user->id > 10) {
            $auth->revokeAll($user->id);
            $user->delete();
        }
    }

    public function testSyncPersonnelRejectsIncompleteData()
    {
        $res = UniversitySyncService::syncPersonnelRecord([
            'employee_code' => '',
            'username' => '',
            'email' => '',
        ]);

        $this->assertFalse($res['success']);
    }

    public function testImportFromCsvBatch()
    {
        $tempCsv = tempnam(sys_get_temp_dir(), 'csv_test_') . '.csv';
        $csvContent = "employee_code,username,email,prefix_th,first_name_th,last_name_th,personnel_type_code\n";
        $csvContent .= "CSV_BATCH_01,batch_user_01,batch01@rmutt.ac.th,นาย,นำเข้าทดสอบ,ชุดที่หนึ่ง,CIVIL\n";
        $csvContent .= "CSV_BATCH_02,batch_user_02,batch02@rmutt.ac.th,นางสาว,นำเข้าทดสอบ,ชุดที่สอง,UNIVERSITY\n";
        file_put_contents($tempCsv, $csvContent);

        $res = UniversitySyncService::importFromCsv($tempCsv, 1);

        $this->assertEquals(2, $res['total']);
        $this->assertEquals(2, $res['success']);
        $this->assertEquals(0, $res['failed']);

        // Clean up
        unlink($tempCsv);
        foreach (['CSV_BATCH_01', 'CSV_BATCH_02'] as $c) {
            $p = Personnel::findOne(['employee_code' => $c]);
            if ($p) {
                $u = $p->user;
                $p->delete();
                if ($u && $u->id > 10) {
                    \Yii::$app->authManager->revokeAll($u->id);
                    $u->delete();
                }
            }
        }
    }
}
