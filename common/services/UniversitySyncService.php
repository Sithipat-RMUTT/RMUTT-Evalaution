<?php

namespace common\services;

use Yii;
use common\models\User;
use common\models\Personnel;
use common\models\PersonnelType;
use common\models\Department;
use common\models\Position;
use common\models\AuditLog;

/**
 * UniversitySyncService
 * 
 * Handles syncing personnel data from university central identity / HR databases.
 * Every user is synchronized with equal base role (personnel).
 * Department Admins then assign evaluation hierarchies (L1 / L2).
 */
class UniversitySyncService
{
    /**
     * Sync single personnel record from university central data array
     */
    public static function syncPersonnelRecord(array $data, bool $preserveHierarchy = true): array
    {
        $db = Yii::$app->db;
        $tx = $db->beginTransaction();

        try {
            $code = trim($data['employee_code'] ?? '');
            $username = trim($data['username'] ?? '');
            $email = trim($data['email'] ?? '');

            if (empty($code) || empty($username) || empty($email)) {
                $tx->rollBack();
                return ['success' => false, 'message' => 'ข้อมูลจำเป็นไม่ครบถ้วน (รหัสบุคลากร, Username, Email)'];
            }

            // 1. Resolve User
            $user = User::findByUsername($username);
            if (!$user) {
                $user = new User();
                $isDev = !defined('YII_ENV_PROD') || !YII_ENV_PROD;
                $defaultSeedPass = getenv('SEED_DEFAULT_PASSWORD');
                if ($isDev && !empty($defaultSeedPass)) {
                    $user->setPassword($defaultSeedPass);
                } else {
                    $tempPassword = Yii::$app->security->generateRandomString(16);
                    $user->setPassword($tempPassword);
                    $user->generatePasswordResetToken();
                }
                $user->generateAuthKey();
                $user->status = User::STATUS_ACTIVE;
                if (!$user->save(false)) {
                    $tx->rollBack();
                    return ['success' => false, 'message' => 'ไม่สามารถสร้างบัญชีผู้ใช้งานได้'];
                }

                // Assign universal base role: personnel
                $auth = Yii::$app->authManager;
                $personnelRole = $auth->getRole('personnel');
                if ($personnelRole && !$auth->getAssignment('personnel', $user->id)) {
                    $auth->assign($personnelRole, $user->id);
                }
            } else {
                $user->email = $email;
                $user->save(false);
            }

            // 2. Resolve Personnel Type
            $typeCode = strtoupper(trim($data['personnel_type_code'] ?? 'CIVIL'));
            $pType = PersonnelType::findOne(['code' => $typeCode]) ?: PersonnelType::findOne(['code' => 'CIVIL']);
            if (!$pType) {
                $pType = PersonnelType::find()->one();
            }

            // 3. Resolve Department
            $deptCode = trim($data['department_code'] ?? '');
            $dept = null;
            if ($deptCode) {
                $dept = Department::findOne(['code' => $deptCode]) ?: Department::find()->where(['like', 'name_th', $deptCode])->one();
            }
            if (!$dept && isset($data['department_id'])) {
                $dept = Department::findOne((int)$data['department_id']);
            }
            if (!$dept) {
                $dept = Department::find()->one();
            }

            // 4. Resolve Position
            $posName = trim($data['position_name'] ?? '');
            $pos = null;
            if ($posName) {
                $pos = Position::find()->where(['name_th' => $posName])->one();
                if (!$pos) {
                    $pos = new Position();
                    $pos->code = 'POS_' . strtoupper(substr(md5($posName), 0, 8));
                    $pos->name_th = $posName;
                    $pos->level_label = 'ปฏิบัติการ';
                    $pos->status = 1;
                    $pos->created_at = time();
                    $pos->updated_at = time();
                    $pos->save(false);
                }
            }
            if (!$pos) {
                $pos = Position::find()->one();
            }

            // 5. Create or Update Personnel
            $personnel = Personnel::findOne(['employee_code' => $code]);
            if (!$personnel) {
                $personnel = new Personnel();
                $personnel->position_level = 'staff';
                $personnel->is_supervisor = 0;
            }

            // If another personnel record is holding this user_id, deactivate and unlink to protect historical evaluations
            $stale = Personnel::findOne(['user_id' => $user->id]);
            if ($stale && $stale->employee_code !== $code) {
                $stale->status = 0;
                $stale->user_id = null;
                $stale->save(false);
            }

            $personnel->employee_code = $code;
            $personnel->user_id = $user->id;
            $personnel->prefix_th = $data['prefix_th'] ?? 'นาย';
            $personnel->first_name_th = $data['first_name_th'] ?? '';
            $personnel->last_name_th = $data['last_name_th'] ?? '';
            $personnel->email = $email;
            $personnel->personnel_type_id = $pType ? $pType->id : null;
            $personnel->department_id = $dept ? $dept->id : null;
            $personnel->position_id = $pos ? $pos->id : null;
            $personnel->status = 10;

            if (!empty($data['citizen_id'])) {
                $personnel->citizen_id = preg_replace('/\D/', '', $data['citizen_id']);
            }

            // Only update hierarchy if explicitly provided or not preserving
            if (!$preserveHierarchy || empty($personnel->supervisor_id)) {
                if (isset($data['supervisor_id'])) {
                    $personnel->supervisor_id = $data['supervisor_id'] ?: null;
                }
                if (isset($data['division_head_id'])) {
                    $personnel->division_head_id = $data['division_head_id'] ?: null;
                }
            }

            $personnel->save(false);
            $tx->commit();

            AuditLog::log('sync_university_personnel', 'Personnel', $personnel->id, null, [
                'employee_code' => $code,
                'personnel_type' => $pType ? $pType->code : '',
                'department' => $dept ? $dept->code : '',
            ]);

            return [
                'success' => true,
                'message' => 'ซิงค์ข้อมูลบุคลากรสำเร็จ',
                'personnel' => $personnel,
            ];
        } catch (\Throwable $e) {
            $tx->rollBack();
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Batch import personnel from CSV file
     */
    public static function importFromCsv(string $filePath, ?int $targetDeptId = null): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'errors' => ['ไม่พบไฟล์ที่ระบุ']];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'errors' => ['ไม่สามารถเปิดไฟล์ได้']];
        }

        // Check BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['total' => 0, 'success' => 0, 'failed' => 0, 'errors' => ['ไฟล์ไม่มีข้อมูลหัวคอลัมน์']];
        }

        // Clean headers
        $cleanHeaders = array_map(fn($h) => strtolower(trim((string)$h)), $headers);

        $total = 0;
        $success = 0;
        $failed = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;
            $total++;

            $rowData = [];
            foreach ($cleanHeaders as $idx => $headerName) {
                $rowData[$headerName] = $row[$idx] ?? '';
            }

            $record = [
                'employee_code' => $rowData['employee_code'] ?? $rowData['code'] ?? $rowData['รหัสบุคลากร'] ?? '',
                'username' => $rowData['username'] ?? $rowData['ชื่อบัญชี'] ?? '',
                'email' => $rowData['email'] ?? $rowData['อีเมล'] ?? '',
                'prefix_th' => $rowData['prefix_th'] ?? $rowData['prefix'] ?? $rowData['คำนำหน้า'] ?? 'นาย',
                'first_name_th' => $rowData['first_name_th'] ?? $rowData['first_name'] ?? $rowData['ชื่อ'] ?? '',
                'last_name_th' => $rowData['last_name_th'] ?? $rowData['last_name'] ?? $rowData['นามสกุล'] ?? '',
                'citizen_id' => $rowData['citizen_id'] ?? $rowData['เลขบัตรประชาชน'] ?? '',
                'personnel_type_code' => $rowData['personnel_type_code'] ?? $rowData['type'] ?? $rowData['ประเภทบุคลากร'] ?? 'CIVIL',
                'department_code' => $rowData['department_code'] ?? $rowData['department'] ?? $rowData['ฝ่าย'] ?? '',
                'department_id' => $targetDeptId,
                'position_name' => $rowData['position_name'] ?? $rowData['position'] ?? $rowData['ตำแหน่ง'] ?? '',
            ];

            if (empty($record['username']) && !empty($record['email'])) {
                $record['username'] = explode('@', $record['email'])[0];
            } elseif (empty($record['username'])) {
                $record['username'] = strtolower($record['employee_code']);
            }

            $res = self::syncPersonnelRecord($record, true);
            if ($res['success']) {
                $success++;
            } else {
                $failed++;
                $errors[] = "แถวที่ {$total} ({$record['employee_code']}): {$res['message']}";
            }
        }

        fclose($handle);
        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
