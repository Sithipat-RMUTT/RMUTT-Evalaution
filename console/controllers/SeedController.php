<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use common\models\User;
use common\models\Personnel;

/**
 * SeedController populates the database with initial RMUTT roles, users, departments, positions,
 * personnel types, and exact evaluation templates based on reference documents.
 */
class SeedController extends Controller
{
    /**
     * Run all seeders
     */
    public function actionIndex()
    {
        $this->stdout("Starting RMUTT Evaluation System Seeder...\n", Console::FG_YELLOW);

        $this->seedRbac();
        $this->seedUsersAndPersonnel();
        $this->seedTemplates();
        $this->seedEvaluationCycle();

        $this->stdout("\nSeeding completed successfully!\n", Console::FG_GREEN);
    }

    /**
     * Seed active evaluation cycle and map template versions
     */
    public function seedEvaluationCycle()
    {
        $this->stdout("\n--- Seeding Active Evaluation Cycle ---\n", Console::FG_CYAN);
        $adminUserId = \common\models\User::findOne(['username' => 'admin'])->id ?? 1;

        $cycle = \common\models\EvaluationCycle::findOne(['fiscal_year' => 2569, 'cycle_number' => 1]);
        if (!$cycle) {
            $cycle = new \common\models\EvaluationCycle();
            $cycle->name_th = 'การประเมินผลการปฏิบัติราชการ รอบที่ 1/2569 (1 ต.ค. 2568 - 31 มี.ค. 2569)';
            $cycle->cycle_number = 1;
            $cycle->fiscal_year = 2569;
            $cycle->period_start = '2025-10-01';
            $cycle->period_end = '2026-03-31';
            $cycle->self_assessment_start = '2026-03-01 08:30:00';
            $cycle->self_assessment_end = '2026-03-20 16:30:00';
            $cycle->supervisor_eval_start = '2026-03-21 08:30:00';
            $cycle->supervisor_eval_end = '2026-03-31 16:30:00';
            $cycle->status = \common\models\EvaluationCycle::STATUS_ACTIVE;
            $cycle->description = 'รอบประเมินผลการปฏิบัติราชการครั้งที่ 1 ประจำปีงบประมาณ พ.ศ. 2569 สำนักวิทยบริการและเทคโนโลยีสารสนเทศ';
            $cycle->created_by = $adminUserId;
            $cycle->save(false);
            $this->stdout("Created Active Cycle: {$cycle->name_th}\n", Console::FG_GREEN);
        }

        // Map all 4 personnel types to their template versions
        $types = \common\models\PersonnelType::find()->all();
        foreach ($types as $t) {
            $template = \common\models\EvaluationTemplate::findOne(['personnel_type_id' => $t->id]);
            if ($template && $template->activeVersion) {
                $m = \common\models\CycleTemplateMapping::findOne([
                    'evaluation_cycle_id' => $cycle->id,
                    'personnel_type_id' => $t->id,
                ]);
                if (!$m) {
                    $m = new \common\models\CycleTemplateMapping();
                    $m->evaluation_cycle_id = $cycle->id;
                    $m->personnel_type_id = $t->id;
                    $m->template_version_id = $template->activeVersion->id;
                    $m->created_at = time();
                    $m->save(false);
                    $this->stdout("Mapped {$t->code} -> Template {$template->name_th}\n", Console::FG_GREEN);
                }
            }
        }
    }

    /**
     * Seed RBAC roles and permissions
     */
    public function seedRbac()
    {
        $this->stdout("\n--- Seeding RBAC Roles ---\n", Console::FG_CYAN);
        $auth = Yii::$app->authManager;
        $auth->removeAll();

        // Roles
        $superadmin = $auth->createRole('superadmin');
        $superadmin->description = 'ผู้ดูแลระบบสูงสุด (Super Administrator)';
        $auth->add($superadmin);

        $centralHr = $auth->createRole('central_hr');
        $centralHr->description = 'ผู้ดูแลระบบส่วนกลาง กองบริหารงานบุคคล (Central HR Admin)';
        $auth->add($centralHr);

        $admin = $auth->createRole('admin');
        $admin->description = 'เจ้าหน้าที่งานบุคคล / ผู้ดูแลระดับหน่วยงาน (Department Admin)';
        $auth->add($admin);

        $divisionHeadRole = $auth->createRole('division_head');
        $divisionHeadRole->description = 'หัวหน้าฝ่าย / ผู้ประเมินชั้นที่ 2 (Division Head / L2)';
        $auth->add($divisionHeadRole);

        $sectionHeadRole = $auth->createRole('section_head');
        $sectionHeadRole->description = 'หัวหน้างาน / ผู้ประเมินชั้นต้น (Section Head / L1)';
        $auth->add($sectionHeadRole);

        $supervisor = $auth->createRole('supervisor');
        $supervisor->description = 'ผู้บังคับบัญชา / ผู้ประเมิน';
        $auth->add($supervisor);

        $personnel = $auth->createRole('personnel');
        $personnel->description = 'บุคลากรทั่วไป (ผู้รับการประเมิน)';
        $auth->add($personnel);

        // Hierarchy: superadmin -> central_hr -> admin -> division_head -> section_head -> supervisor
        $auth->addChild($sectionHeadRole, $supervisor);
        $auth->addChild($divisionHeadRole, $sectionHeadRole);
        $auth->addChild($admin, $divisionHeadRole);
        $auth->addChild($admin, $personnel);
        $auth->addChild($centralHr, $admin);
        $auth->addChild($superadmin, $centralHr);

        $this->stdout("RBAC Roles created: superadmin, central_hr, admin, division_head, section_head, supervisor, personnel\n", Console::FG_GREEN);
    }

    /**
     * Seed Users, Personnel Types, Departments, Positions, and Personnel
     */
    public function seedUsersAndPersonnel()
    {
        $this->stdout("\n--- Seeding Personnel Types, Departments, Positions, Users ---\n", Console::FG_CYAN);
        $db = Yii::$app->db;
        $now = time();

        // 1. Personnel Types
        $types = [
            ['code' => 'CIVIL', 'name_th' => 'ข้าราชการพลเรือนในสถาบันอุดมศึกษา', 'name_en' => 'Civil Servant', 'sort_order' => 1],
            ['code' => 'UNIVERSITY', 'name_th' => 'พนักงานมหาวิทยาลัย', 'name_en' => 'University Employee', 'sort_order' => 2],
            ['code' => 'GOVT', 'name_th' => 'พนักงานราชการทั่วไป', 'name_en' => 'Government Employee', 'sort_order' => 3],
            ['code' => 'SPECIAL', 'name_th' => 'พนักงานพิเศษเงินรายได้', 'name_en' => 'Special Revenue Employee', 'sort_order' => 4],
        ];

        foreach ($types as $t) {
            $exists = $db->createCommand("SELECT id FROM {{%personnel_types}} WHERE code = :code", [':code' => $t['code']])->queryScalar();
            if (!$exists) {
                $db->createCommand()->insert('{{%personnel_types}}', [
                    'code' => $t['code'],
                    'name_th' => $t['name_th'],
                    'name_en' => $t['name_en'],
                    'sort_order' => $t['sort_order'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            }
        }
        $this->stdout("Personnel Types seeded.\n", Console::FG_GREEN);

        // 2. Departments (8 Main Divisions & Offices + ARIT sub-departments)
        $mainDepts = [
            ['code' => 'CENTRAL', 'name_th' => 'กองกลาง', 'name_en' => 'General Affairs Division', 'sort_order' => 1],
            ['code' => 'FINANCE', 'name_th' => 'กองคลัง', 'name_en' => 'Finance and Property Division', 'sort_order' => 2],
            ['code' => 'PLAN', 'name_th' => 'กองนโยบายและแผน', 'name_en' => 'Policy and Planning Division', 'sort_order' => 3],
            ['code' => 'HR', 'name_th' => 'กองบริหารงานบุคคล', 'name_en' => 'Human Resources Division', 'sort_order' => 4],
            ['code' => 'STUDENT', 'name_th' => 'กองพัฒนานักศึกษา', 'name_en' => 'Student Development Division', 'sort_order' => 5],
            ['code' => 'IRD', 'name_th' => 'สถาบันวิจัยและพัฒนา', 'name_en' => 'Institute of Research and Development', 'sort_order' => 6],
            ['code' => 'ARIT', 'name_th' => 'สำนักวิทยบริการและเทคโนโลยีสารสนเทศ', 'name_en' => 'Academic Resource and Information Technology (ARIT)', 'sort_order' => 7],
            ['code' => 'OREG', 'name_th' => 'สำนักส่งเสริมวิชาการและงานทะเบียน', 'name_en' => 'Office of Academic Promotion and Registration', 'sort_order' => 8],
        ];

        foreach ($mainDepts as $md) {
            $exists = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = :code", [':code' => $md['code']])->queryScalar();
            if (!$exists) {
                $db->createCommand()->insert('{{%departments}}', [
                    'code' => $md['code'],
                    'name_th' => $md['name_th'],
                    'name_en' => $md['name_en'],
                    'parent_id' => null,
                    'sort_order' => $md['sort_order'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            } else {
                $db->createCommand()->update('{{%departments}}', [
                    'name_th' => $md['name_th'],
                    'name_en' => $md['name_en'],
                    'sort_order' => $md['sort_order'],
                    'status' => 1,
                ], ['id' => $exists])->execute();
            }
        }

        $rootDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'ARIT'")->queryScalar();

        $subDepts = [
            ['code' => 'ARIT-IT', 'name_th' => 'ฝ่ายเทคโนโลยีสารสนเทศและนวัตกรรมดิจิทัล', 'sort_order' => 1],
            ['code' => 'ARIT-NET', 'name_th' => 'ฝ่ายเครือข่ายและโครงสร้างพื้นฐานดิจิทัล', 'sort_order' => 2],
            ['code' => 'ARIT-LIB', 'name_th' => 'ฝ่ายบริการวิชาการและสารสนเทศ (หอสมุด)', 'sort_order' => 3],
            ['code' => 'ARIT-ADM', 'name_th' => 'ฝ่ายบริหารงานทั่วไป', 'sort_order' => 4],
            ['code' => 'ARIT-DIR', 'name_th' => 'สำนักงานผู้อำนวยการ', 'sort_order' => 5],
            ['code' => 'ARIT-ACA', 'name_th' => 'ฝ่ายบริการวิชาการและกิจการพิเศษ', 'sort_order' => 6],
            ['code' => 'ARIT-MED', 'name_th' => 'ฝ่ายนวัตกรรมสื่อการศึกษา', 'sort_order' => 7],
            ['code' => 'ARIT-COE', 'name_th' => 'ศูนย์ความเป็นเลิศ (Center of Excellence : COE)', 'sort_order' => 8],
        ];

        foreach ($subDepts as $sd) {
            $exists = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = :code", [':code' => $sd['code']])->queryScalar();
            if (!$exists) {
                $db->createCommand()->insert('{{%departments}}', [
                    'code' => $sd['code'],
                    'name_th' => $sd['name_th'],
                    'parent_id' => $rootDeptId,
                    'sort_order' => $sd['sort_order'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            } else {
                $db->createCommand()->update('{{%departments}}', [
                    'name_th' => $sd['name_th'],
                    'sort_order' => $sd['sort_order'],
                    'status' => 1,
                ], ['id' => $exists])->execute();
            }
        }
        $this->stdout("Departments seeded (8 Main Divisions/Offices + ARIT sub-departments).\n", Console::FG_GREEN);

        // 3. Positions
        $positions = [
            ['code' => 'DIR', 'name_th' => 'ผู้อำนวยการสำนักฯ', 'level_label' => 'ผู้บริหาร'],
            ['code' => 'HEAD_IT', 'name_th' => 'หัวหน้าฝ่ายพัฒนาระบบสารสนเทศ', 'level_label' => 'ชำนาญการพิเศษ'],
            ['code' => 'HEAD_SEC', 'name_th' => 'หัวหน้างานพัฒนาระบบ', 'level_label' => 'ชำนาญการ'],
            ['code' => 'HEAD_DIV', 'name_th' => 'หัวหน้าฝ่าย', 'level_label' => 'ชำนาญการพิเศษ'],
            ['code' => 'CS_COMP', 'name_th' => 'นักวิชาการคอมพิวเตอร์', 'level_label' => 'ชำนาญการ'],
            ['code' => 'UNIV_COMP', 'name_th' => 'นักวิชาการคอมพิวเตอร์', 'level_label' => 'ปฏิบัติการ'],
            ['code' => 'GOVT_OFFICER', 'name_th' => 'เจ้าหน้าที่บริหารงานทั่วไป', 'level_label' => 'พนักงานราชการ'],
            ['code' => 'SPEC_TECH', 'name_th' => 'นายช่างเทคนิค', 'level_label' => 'พนักงานพิเศษเงินรายได้'],
            ['code' => 'CS_EDU', 'name_th' => 'นักวิชาการศึกษา', 'level_label' => 'ปฏิบัติการ/ชำนาญการ'],
            ['code' => 'CS_AUDIO', 'name_th' => 'นักวิชาการโสตทัศนศึกษา', 'level_label' => 'ปฏิบัติการ/ชำนาญการ'],
            ['code' => 'LIBRARIAN', 'name_th' => 'บรรณารักษ์', 'level_label' => 'ปฏิบัติการ/ชำนาญการ'],
            ['code' => 'DATA_OFFICER', 'name_th' => 'เจ้าหน้าที่บันทึกข้อมูล', 'level_label' => 'ปฏิบัติการ'],
            ['code' => 'LECTURER', 'name_th' => 'อาจารย์', 'level_label' => 'วิชาการ'],
            ['code' => 'DRIVER', 'name_th' => 'พนักงานขับรถยนต์', 'level_label' => 'บริการ'],
        ];

        foreach ($positions as $p) {
            $exists = $db->createCommand("SELECT id FROM {{%positions}} WHERE code = :code", [':code' => $p['code']])->queryScalar();
            if (!$exists) {
                $db->createCommand()->insert('{{%positions}}', [
                    'code' => $p['code'],
                    'name_th' => $p['name_th'],
                    'level_label' => $p['level_label'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            } else {
                $db->createCommand()->update('{{%positions}}', [
                    'name_th' => $p['name_th'],
                    'level_label' => $p['level_label'],
                    'status' => 1,
                ], ['id' => $exists])->execute();
            }
        }
        $this->stdout("Positions seeded.\n", Console::FG_GREEN);

        // 4. Users and Personnel Accounts
        $auth = Yii::$app->authManager;
        $civilTypeId = $db->createCommand("SELECT id FROM {{%personnel_types}} WHERE code = 'CIVIL'")->queryScalar();
        $univTypeId = $db->createCommand("SELECT id FROM {{%personnel_types}} WHERE code = 'UNIVERSITY'")->queryScalar();
        $govtTypeId = $db->createCommand("SELECT id FROM {{%personnel_types}} WHERE code = 'GOVT'")->queryScalar();
        $specTypeId = $db->createCommand("SELECT id FROM {{%personnel_types}} WHERE code = 'SPECIAL'")->queryScalar();

        $centralDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'CENTRAL'")->queryScalar();
        $financeDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'FINANCE'")->queryScalar();
        $planDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'PLAN'")->queryScalar();
        $hrDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'HR'")->queryScalar();
        $studentDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'STUDENT'")->queryScalar();
        $irdDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'IRD'")->queryScalar();
        $aritDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'ARIT'")->queryScalar();
        $oregDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'OREG'")->queryScalar();

        $itDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'ARIT-IT'")->queryScalar() ?: $aritDeptId;
        $admDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'ARIT-ADM'")->queryScalar() ?: $aritDeptId;
        $libDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'ARIT-LIB'")->queryScalar() ?: $aritDeptId;
        $dirDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'ARIT-DIR'")->queryScalar() ?: $admDeptId;
        $acaDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'ARIT-ACA'")->queryScalar() ?: $libDeptId;
        $medDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'ARIT-MED'")->queryScalar() ?: $itDeptId;
        $coeDeptId = $db->createCommand("SELECT id FROM {{%departments}} WHERE code = 'ARIT-COE'")->queryScalar() ?: $itDeptId;

        $posHeadDiv = $db->createCommand("SELECT id FROM {{%positions}} WHERE code = 'HEAD_IT'")->queryScalar();
        $posHeadSec = $db->createCommand("SELECT id FROM {{%positions}} WHERE code = 'HEAD_SEC'")->queryScalar() ?: $posHeadDiv;
        $posCsComp = $db->createCommand("SELECT id FROM {{%positions}} WHERE code = 'CS_COMP'")->queryScalar();
        $posUnivComp = $db->createCommand("SELECT id FROM {{%positions}} WHERE code = 'UNIV_COMP'")->queryScalar();
        $posGovt = $db->createCommand("SELECT id FROM {{%positions}} WHERE code = 'GOVT_OFFICER'")->queryScalar();
        $posSpec = $db->createCommand("SELECT id FROM {{%positions}} WHERE code = 'SPEC_TECH'")->queryScalar();

        $accounts = [
            [
                'username' => 'admin',
                'email' => 'admin@rmutt.ac.th',
                'password' => '123456',
                'role' => 'superadmin',
                'prefix' => 'นาย',
                'first' => 'ผู้ดูแลระบบ',
                'last' => 'ส่วนกลาง (Superadmin)',
                'code' => 'ADM001',
                'type_id' => $civilTypeId,
                'dept_id' => $centralDeptId ?: $admDeptId,
                'pos_id' => $posCsComp,
                'position_level' => 'director',
                'is_supervisor' => 1,
            ],
            [
                'username' => 'admin_hr',
                'email' => 'admin_hr@rmutt.ac.th',
                'password' => '123456',
                'role' => 'central_hr',
                'prefix' => 'นางสาว',
                'first' => 'กาญจนา',
                'last' => 'บริหารบุคคล (Central HR)',
                'code' => 'ADMHR001',
                'type_id' => $civilTypeId,
                'dept_id' => $hrDeptId,
                'pos_id' => $posGovt ?: $posCsComp,
                'position_level' => 'staff',
                'is_supervisor' => 0,
            ],
            [
                'username' => 'admin_arit',
                'email' => 'admin_arit@rmutt.ac.th',
                'password' => '123456',
                'role' => 'admin',
                'prefix' => 'นาย',
                'first' => 'วิทยากร',
                'last' => 'สารสนเทศ (ARIT Admin)',
                'code' => 'ADMARIT001',
                'type_id' => $civilTypeId,
                'dept_id' => $aritDeptId,
                'pos_id' => $posCsComp,
                'position_level' => 'staff',
                'is_supervisor' => 0,
            ],

            [
                'username' => 'head2',
                'email' => 'head_div@rmutt.ac.th',
                'password' => '123456',
                'role' => 'division_head',
                'prefix' => 'ผศ.ดร.',
                'first' => 'นภาพร',
                'last' => 'ประเสริฐสุข',
                'code' => 'DIV001',
                'type_id' => $civilTypeId,
                'dept_id' => $itDeptId,
                'pos_id' => $posHeadDiv,
                'position_level' => 'division_head',
                'is_supervisor' => 1,
            ],
            [
                'username' => 'head1',
                'email' => 'supervisor@rmutt.ac.th',
                'password' => '123456',
                'role' => 'section_head',
                'prefix' => 'ดร.',
                'first' => 'สมเกียรติ',
                'last' => 'พัฒนาวิทย์',
                'code' => 'SUP001',
                'type_id' => $civilTypeId,
                'dept_id' => $itDeptId,
                'pos_id' => $posHeadSec,
                'position_level' => 'section_head',
                'is_supervisor' => 1,
            ],
            [
                'username' => 'staff1',
                'email' => 'staff1@rmutt.ac.th',
                'password' => '123456',
                'role' => 'personnel',
                'prefix' => 'นาย',
                'first' => 'ประเสริฐ',
                'last' => 'ราชการดี',
                'code' => 'CIV001',
                'type_id' => $civilTypeId,
                'dept_id' => $itDeptId,
                'pos_id' => $posCsComp,
                'position_level' => 'staff',
                'is_supervisor' => 0,
            ],
            [
                'username' => 'staff2',
                'email' => 'staff2@rmutt.ac.th',
                'password' => '123456',
                'role' => 'personnel',
                'prefix' => 'นางสาว',
                'first' => 'วิภาดา',
                'last' => 'มหาพัฒน์',
                'code' => 'UNV001',
                'type_id' => $univTypeId,
                'dept_id' => $itDeptId,
                'pos_id' => $posUnivComp,
                'position_level' => 'staff',
                'is_supervisor' => 0,
            ],
            [
                'username' => 'staff3',
                'email' => 'staff3@rmutt.ac.th',
                'password' => '123456',
                'role' => 'personnel',
                'prefix' => 'นาย',
                'first' => 'ธวัชชัย',
                'last' => 'ราชการมั่น',
                'code' => 'GOV001',
                'type_id' => $govtTypeId,
                'dept_id' => $admDeptId,
                'pos_id' => $posGovt,
                'position_level' => 'staff',
                'is_supervisor' => 0,
            ],
            [
                'username' => 'staff4',
                'email' => 'staff4@rmutt.ac.th',
                'password' => '123456',
                'role' => 'personnel',
                'prefix' => 'นาย',
                'first' => 'เอกชัย',
                'last' => 'พิเศษสุข',
                'code' => 'SPC001',
                'type_id' => $specTypeId,
                'dept_id' => $itDeptId,
                'pos_id' => $posSpec,
                'position_level' => 'staff',
                'is_supervisor' => 0,
            ],
            [
                'username' => 'head3',
                'email' => 'director@rmutt.ac.th',
                'password' => '123456',
                'role' => 'division_head',
                'prefix' => 'รศ.ดร.',
                'first' => 'ผู้อำนวยการ',
                'last' => 'สำนักวิทยบริการฯ',
                'code' => 'DIR001',
                'type_id' => $civilTypeId,
                'dept_id' => $dirDeptId,
                'pos_id' => $posCsComp,
                'position_level' => 'division_head',
                'is_supervisor' => 1,
            ],
        ];

        $pIds = [];
        $db->createCommand("SET FOREIGN_KEY_CHECKS = 0")->execute();
        $seedCodes = array_map(fn($a) => "'" . $a['code'] . "'", $accounts);
        $codeList = implode(',', $seedCodes);
        $db->createCommand("UPDATE {{%personnel}} SET user_id = -(id + 1000) WHERE employee_code IN ($codeList)")->execute();

        foreach ($accounts as $acc) {
            $user = User::findByUsername($acc['username']);
            if (!$user) {
                $user = new User();
                $user->username = $acc['username'];
                $user->email = $acc['email'];
                $user->setPassword($acc['password']);
                $user->generateAuthKey();
                $user->status = User::STATUS_ACTIVE;
                $user->save(false);
            } else {
                $user->username = $acc['username'];
                $user->email = $acc['email'];
                $user->setPassword($acc['password']);
                $user->save(false);
            }

            // Assign RBAC
            $roleObj = $auth->getRole($acc['role']);
            if ($roleObj && !$auth->getAssignment($acc['role'], $user->id)) {
                $auth->assign($roleObj, $user->id);
            }

            // Personnel record
            $pRecord = Personnel::findOne(['employee_code' => $acc['code']]) ?: Personnel::findOne(['user_id' => $user->id]);
            if (!$pRecord) {
                $pRecord = new Personnel();
                $pRecord->user_id = $user->id;
                $pRecord->personnel_type_id = $acc['type_id'];
                $pRecord->department_id = $acc['dept_id'];
                $pRecord->position_id = $acc['pos_id'];
                $pRecord->employee_code = $acc['code'];
                $pRecord->prefix_th = $acc['prefix'];
                $pRecord->first_name_th = $acc['first'];
                $pRecord->last_name_th = $acc['last'];
                $pRecord->email = $acc['email'];
                $pRecord->is_supervisor = $acc['is_supervisor'];
                $pRecord->position_level = $acc['position_level'];
                $pRecord->citizen_id = sprintf('3%012d', abs(crc32($acc['code'])));
                $pRecord->status = 10;
                $pRecord->save(false);
            } else {
                $pRecord->user_id = $user->id;
                $pRecord->personnel_type_id = $acc['type_id'];
                $pRecord->department_id = $acc['dept_id'];
                $pRecord->position_id = $acc['pos_id'];
                $pRecord->prefix_th = $acc['prefix'];
                $pRecord->first_name_th = $acc['first'];
                $pRecord->last_name_th = $acc['last'];
                $pRecord->email = $acc['email'];
                $pRecord->position_level = $acc['position_level'];
                $pRecord->is_supervisor = $acc['is_supervisor'];
                if (empty($pRecord->citizen_id_encrypted) && empty($pRecord->citizen_id)) {
                    $pRecord->citizen_id = sprintf('3%012d', abs(crc32($acc['code'])));
                }
                $pRecord->save(false);
            }

            $pIds[$acc['username']] = $pRecord->id;
        }

        // Set Supervisor and Division Head hierarchy:
        // Division Head: head2 (ID)
        // Section Head (L1): head1 (ID) -> Supervisor = head2, Division Head = head2
        // Staff 1-4: Supervisor = head1 (L1), Division Head = head2 (L2)
        // admin: Supervisor = head2 (for demo workflow)
        // head2: Supervisor = head3 director (for demo workflow)
        $headDivId = $pIds['head2'] ?? $pIds['head_div'] ?? null;
        $supervisorId = $pIds['head1'] ?? $pIds['supervisor'] ?? null;
        $adminPId = $pIds['admin'] ?? null;
        $directorPId = $pIds['head3'] ?? $pIds['director'] ?? null;

        if ($headDivId && $supervisorId) {
            // Set Supervisor (Section Head L1)'s supervisor to Division Head (L2)
            $db->createCommand("UPDATE {{%personnel}} SET supervisor_id = :div, division_head_id = :div WHERE id = :sup", [
                ':div' => $headDivId,
                ':sup' => $supervisorId,
            ])->execute();

            // Set Staff 1-4: Supervisor = supervisor (L1), Division Head = head_div (L2)
            $staffPIds = [$pIds['staff1'] ?? 0, $pIds['staff2'] ?? 0, $pIds['staff3'] ?? 0, $pIds['staff4'] ?? 0];
            $staffPIds = array_filter($staffPIds);
            if (!empty($staffPIds)) {
                $inList = implode(',', $staffPIds);
                $db->createCommand("UPDATE {{%personnel}} SET supervisor_id = :sup, division_head_id = :div WHERE id IN ($inList)", [
                    ':sup' => $supervisorId,
                    ':div' => $headDivId,
                ])->execute();
            }

            // Set admin, admin_it, admin_lib evaluator to head_div (for demo workflow)
            $deptAdmins = array_filter([$pIds['admin'] ?? 0, $pIds['admin_it'] ?? 0, $pIds['admin_lib'] ?? 0]);
            if (!empty($deptAdmins)) {
                $inAdmins = implode(',', $deptAdmins);
                $db->createCommand("UPDATE {{%personnel}} SET supervisor_id = :div, division_head_id = :div WHERE id IN ($inAdmins)", [
                    ':div' => $headDivId,
                ])->execute();
            }

            // Set head_div's evaluator to director (for demo workflow)
            if ($headDivId && $directorPId) {
                $db->createCommand("UPDATE {{%personnel}} SET supervisor_id = :dir, division_head_id = :dir WHERE id = :hdiv", [
                    ':dir' => $directorPId,
                    ':hdiv' => $headDivId,
                ])->execute();
            }
        }

        // Now fix evaluations so that evaluator_id / evaluator_l1_id / evaluator_l2_id are populated
        // for any evaluation that has NULL evaluators
        $db->createCommand("
            UPDATE {{%evaluations}} e
            JOIN {{%personnel}} p ON p.id = e.personnel_id
            SET e.evaluator_id = COALESCE(p.supervisor_id, p.division_head_id),
                e.evaluator_l1_id = p.supervisor_id,
                e.evaluator_l2_id = p.division_head_id
        ")->execute();

        $db->createCommand("SET FOREIGN_KEY_CHECKS = 1")->execute();

        $this->stdout("Users and 2-Tier Personnel hierarchy seeded (password: 123456 for all accounts).\n", Console::FG_GREEN);
    }

    /**
     * Reset all test user accounts, usernames and set password to 123456
     */
    public function actionResetUsers()
    {
        $this->stdout("Resetting all test users to 2-tier hierarchy and password '123456'...\n", Console::FG_YELLOW);
        $this->seedRbac();
        $this->seedUsersAndPersonnel();
        $this->stdout("\nAll test accounts have been updated successfully!\n", Console::FG_GREEN);
    }

    /**
     * Seed exact evaluation templates based on reference documents
     */
    public function seedTemplates()
    {
        $this->stdout("\n--- Seeding Evaluation Templates from Reference Documents ---\n", Console::FG_CYAN);
        $db = Yii::$app->db;
        $now = time();
        $adminUserId = $db->createCommand("SELECT id FROM {{%user}} WHERE username = 'admin'")->queryScalar() ?: 1;

        // 1. Template ข้าราชการ (CIVIL)
        $civilTypeId = $db->createCommand("SELECT id FROM {{%personnel_types}} WHERE code = 'CIVIL'")->queryScalar();
        $this->seedCivilTemplate($civilTypeId, 'ข้าราชการพลเรือนในสถาบันอุดมศึกษา', 'TEMPLATE_CIVIL_2569', $adminUserId);

        // 2. Template พนักงานมหาวิทยาลัย (UNIVERSITY) - Form เหมือนข้าราชการ
        $univTypeId = $db->createCommand("SELECT id FROM {{%personnel_types}} WHERE code = 'UNIVERSITY'")->queryScalar();
        $this->seedCivilTemplate($univTypeId, 'พนักงานมหาวิทยาลัย', 'TEMPLATE_UNIV_2569', $adminUserId);

        // 3. Template พนักงานราชการ (GOVT)
        $govtTypeId = $db->createCommand("SELECT id FROM {{%personnel_types}} WHERE code = 'GOVT'")->queryScalar();
        $this->seedGovtTemplate($govtTypeId, $adminUserId);

        // 4. Template พนักงานพิเศษเงินรายได้ (SPECIAL)
        $specTypeId = $db->createCommand("SELECT id FROM {{%personnel_types}} WHERE code = 'SPECIAL'")->queryScalar();
        $this->seedSpecialTemplate($specTypeId, $adminUserId);

        $this->stdout("All 4 Evaluation Templates seeded.\n", Console::FG_GREEN);
    }

    /**
     * Seed Template สำหรับ ข้าราชการ และ พนักงานมหาวิทยาลัย
     */
    private function seedCivilTemplate($typeId, $typeName, $templateCode, $adminUserId)
    {
        $db = Yii::$app->db;
        $now = time();

        $tId = $db->createCommand("SELECT id FROM {{%evaluation_templates}} WHERE code = :c", [':c' => $templateCode])->queryScalar();
        if (!$tId) {
            $db->createCommand()->insert('{{%evaluation_templates}}', [
                'personnel_type_id' => $typeId,
                'code' => $templateCode,
                'name_th' => "แบบประเมินผลการปฏิบัติราชการของ{$typeName}",
                'description' => "แบบข้อตกลงและแบบประเมินผลสัมฤทธิ์ของงาน (80%) และสมรรถนะ (20%) สำหรับ{$typeName}",
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $tId = $db->getLastInsertID();
        }

        $vId = $db->createCommand("SELECT id FROM {{%template_versions}} WHERE evaluation_template_id = :tid AND version_number = 1", [':tid' => $tId])->queryScalar();
        if (!$vId) {
            $formula = [
                'type' => 'CIVIL_WEIGHTED',
                'performance_weight' => 80.0,
                'competency_weight' => 20.0,
                'main_work_weight' => 80.0,
                'secondary_work_policy_weight' => 15.0,
                'secondary_work_academic_weight' => 5.0,
            ];
            $db->createCommand()->insert('{{%template_versions}}', [
                'evaluation_template_id' => $tId,
                'version_number' => 1,
                'version_label' => 'ฉบับมาตรฐาน ปีงบประมาณ 2569',
                'is_active' => 1,
                'effective_from' => '2025-10-01',
                'total_weight' => 100.00,
                'score_formula_config' => json_encode($formula, JSON_UNESCAPED_UNICODE),
                'created_by' => $adminUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $vId = $db->getLastInsertID();

            // Section 1: Form 2 ภาระงานหลัก (น้ำหนัก 80)
            $db->createCommand()->insert('{{%evaluation_sections}}', [
                'template_version_id' => $vId,
                'section_code' => 'MAIN_WORK',
                'name_th' => 'แบบข้อตกลงการประเมินผลสัมฤทธิ์ของงาน: ภาระงานหลัก (ค่าน้ำหนัก 80)',
                'description' => 'กรอกข้อมูลการดำเนินงานตามภาระงานหลักโดยสรุปไม่เกิน 10 ข้อ และกำหนดค่าน้ำหนัก (ความสำคัญ/ความยากง่ายของงาน) ข้อละไม่เกิน 30',
                'weight' => 80.00,
                'sort_order' => 1,
                'section_type' => 'main_work',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $secMainId = $db->getLastInsertID();

            // Create Item สำหรับภาระงานหลัก (dynamic rows)
            $db->createCommand()->insert('{{%evaluation_items}}', [
                'evaluation_section_id' => $secMainId,
                'item_code' => 'MAIN_WORK_ITEMS',
                'name_th' => 'ภาระงานหลัก (กิจกรรม/โครงการ/งาน และระดับความสำเร็จตามวงจร PDCA)',
                'description' => 'ระดับ 1=Plan, 2=Do, 3=Check, 4=Act, 5=Continuous Improvement + Impact',
                'input_type' => 'pdca_level',
                'max_score' => 5.00,
                'max_weight' => 30.00,
                'sort_order' => 1,
                'is_required' => 1,
                'requires_evidence' => 1,
                'evidence_instruction' => 'แนบเอกสารหรือหลักฐานแสดงผลสัมฤทธิ์การดำเนินงาน',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            // Section 2: Form 2 ภาระงานรอง 5.1 (น้ำหนัก 15)
            $db->createCommand()->insert('{{%evaluation_sections}}', [
                'template_version_id' => $vId,
                'section_code' => 'SECONDARY_POLICY',
                'name_th' => '๕.๑ ภาระงานอื่น/หรืองานที่ได้รับมอบหมาย ส่งเสริมการขับเคลื่อนนโยบายของมหาวิทยาลัยและสำนักฯ (ค่าน้ำหนัก ๑๕)',
                'description' => 'เกณฑ์การประเมิน: 3-5 ข้อ = 5 คะแนน, 2 ข้อ = 3 คะแนน, 1 ข้อ = 1 คะแนน, 0 ข้อ = 0 คะแนน',
                'weight' => 15.00,
                'sort_order' => 2,
                'section_type' => 'secondary_work',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $secSec1Id = $db->getLastInsertID();

            $optionsPolicy = [
                ['key' => '1', 'text' => 'งานบริการวิชาการหารายได้ตั้งแต่ ๑๐,๐๐๐.- บาทขึ้นไป (สะสมใน ๑ ปี)'],
                ['key' => '2', 'text' => 'นวัตกรรม/สร้างสรรค์ โดยเป็นผู้ดำเนินการหลักหรือผู้ร่วมซึ่งมีส่วนร่วม ร้อยละ ๓๐ ขึ้นไป โดยใช้แบบฟอร์มการแสดงการมีส่วนร่วม'],
                ['key' => '3', 'text' => 'การพัฒนาตนเองด้านภาษาต่างประเทศ โดยมีใบรับรองตามมาตรฐาน เช่น RT-TEP ๓.๕/ IELTS ๕.๕ /TOEFL ๔๐๐ หรือกิจกรรมด้านภาษาที่คณะกรรมการรับรอง / วิชาชีพเฉพาะทาง โดยมีใบรับรองตามมาตรฐาน เช่น ใบ Certificate จากระบบ Certiport (ภายในปีงบประมาณ หรือย้อนหลัง ๑ ปี **๑ Certificate ใช้ได้ ๒ รอบประเมิน)'],
                ['key' => '4', 'text' => 'การเข้าร่วมกิจกรรมของสำนักฯ/มหาวิทยาลัยฯ ตั้งแต่ ๔ ครั้งขึ้นไป/รอบการประเมิน (ดังเอกสารแนบ)'],
                ['key' => '5', 'text' => 'คณะกรรมการการดำเนินงานด้านต่าง ๆ ของสำนักฯ/มหาวิทยาลัยฯ ตั้งแต่ ๓ งาน/โครงการขึ้นไป (**สามารถสะสมได้ภายใน ๑ ปี)'],
                ['key' => '6', 'text' => 'ปฏิบัติหน้าที่หัวหน้าฝ่าย (เท่ากับ ๒ ข้อ)'],
                ['key' => '7', 'text' => 'ปฏิบัติหน้าที่หัวหน้างาน (เท่ากับ ๑ ข้อ)'],
            ];

            $db->createCommand()->insert('{{%evaluation_items}}', [
                'evaluation_section_id' => $secSec1Id,
                'item_code' => 'SEC_POLICY_CHECKLIST',
                'name_th' => 'รายการส่งเสริมการขับเคลื่อนนโยบาย (เลือกรายการที่ดำเนินการ)',
                'description' => 'เลือกรายการและแนบเอกสารหลักฐาน',
                'input_type' => 'checkbox_list',
                'max_score' => 5.00,
                'default_weight' => 15.00,
                'sort_order' => 1,
                'is_required' => 0,
                'requires_evidence' => 1,
                'options_data' => json_encode($optionsPolicy, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            // Section 3: Form 2 ภาระงานรอง 5.2 (น้ำหนัก 5)
            $db->createCommand()->insert('{{%evaluation_sections}}', [
                'template_version_id' => $vId,
                'section_code' => 'SECONDARY_ACADEMIC',
                'name_th' => '๕.๒ การจัดทำคู่มือปฏิบัติงาน ผลงานวิจัย ตำรา หนังสือ งานแปล หรือบทความทางวิชาการ (ค่าน้ำหนัก ๕)',
                'description' => '1 คะแนน=ยื่นผู้ทรงคุณวุฒิ, 2 คะแนน=ผ่านตรวจเบื้องต้น, 3 คะแนน=ส่ง กบค., 4 คะแนน=อยู่ระหว่างพิจารณา กบค., 5 คะแนน=เผยแพร่/ได้ตำแหน่งสูงขึ้น',
                'weight' => 5.00,
                'sort_order' => 3,
                'section_type' => 'secondary_work',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $secSec2Id = $db->getLastInsertID();

            $optionsAcademic = [
                ['key' => '1', 'text' => 'ยื่นผลงานให้ผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญพิจารณา (แนบเอกสารขอความอนุเคราะห์/ คำสั่งแต่งตั้ง) (1 คะแนน)'],
                ['key' => '2', 'text' => 'ผ่านการพิจารณาจากผู้ทรงคุณวุฒิภายนอก / ผู้เชี่ยวชาญในงานที่เกี่ยวข้อง ตรวจเบื้องต้น (แนบแบบประเมินผลงาน) (2 คะแนน)'],
                ['key' => '3', 'text' => 'ผ่านการพิจารณาผู้บังคับบัญชาภายในหน่วยงาน ส่งไปยัง กบค. (แนบบันทึกข้อความ) (3 คะแนน)'],
                ['key' => '4', 'text' => 'อยู่ระหว่างการพิจารณาจาก กบค. (ใช้หลักฐานสถานะการดำเนินการจาก กบค.) (4 คะแนน)'],
                ['key' => '5', 'text' => 'เผยแพร่ผลงานทางวิชาการ เป็นตำรา หนังสือบทความ และหรือ ได้ตำแหน่งที่สูงขึ้น (แนบคำสั่งแต่งตั้ง หรือหลักฐาน) (5 คะแนน)'],
            ];

            $db->createCommand()->insert('{{%evaluation_items}}', [
                'evaluation_section_id' => $secSec2Id,
                'item_code' => 'SEC_ACADEMIC_PROGRESS',
                'name_th' => 'สถานะความก้าวหน้าการจัดทำคู่มือ/งานวิชาการเพื่อขอตำแหน่งที่สูงขึ้น',
                'input_type' => 'radio_scale',
                'max_score' => 5.00,
                'default_weight' => 5.00,
                'sort_order' => 1,
                'is_required' => 0,
                'requires_evidence' => 1,
                'options_data' => json_encode($optionsAcademic, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            // Section 4: Form 3 สมรรถนะ (7 ตัว)
            $db->createCommand()->insert('{{%evaluation_sections}}', [
                'template_version_id' => $vId,
                'section_code' => 'COMPETENCY_EVAL',
                'name_th' => 'แบบข้อตกลงการประเมินขีดความสามารถ/สมรรถนะของสายสนับสนุน (ค่าน้ำหนัก ๒๐%)',
                'description' => 'สมรรถนะหลัก 4 ตัว และสมรรถนะทางวิชาชีพ 3 ตัว (ระดับที่คาดหวัง: 3)',
                'weight' => 20.00,
                'sort_order' => 4,
                'section_type' => 'competency',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            // 7 Competencies definitions and 5 behavioral levels
            $competencies = [
                [
                    'code' => 'CORE_1',
                    'type' => 'core',
                    'name_th' => 'การคิดสร้างสรรค์และนวัตกรรม (Innovative and Creative Thinking)',
                    'name_en' => 'Innovative and Creative Thinking',
                    'def' => 'ความพยายามในการปรับปรุง พัฒนา หรือแสวงหาแนวทาง กระบวนการหรือวิธีการใหม่ ๆ ในการปฏิบัติงาน หรือการประยุกต์ใช้เทคโนโลยีหรือนวัตกรรมในด้านต่าง ๆ ที่มีความเหมาะสมเพื่อให้งานที่รับผิดชอบมีประสิทธิภาพดียิ่งขึ้น และเป็นประโยชน์ต่อมหาวิทยาลัยหรือส่วนงาน',
                    'levels' => [
                        1 => 'พื้นฐาน (Basic): แสดงออกถึงการยอมรับและนำวิธีการทำงานใหม่ๆ เปิดรับโอกาสในการค้นพบวิธีการในการพัฒนาการทำงานของตนเอง และใฝ่รู้ กระตือรือร้น และใส่ใจให้ความร่วมมือ',
                        2 => 'ประยุกต์ใช้ (Apply): นำขั้นตอนการทำงานใหม่ๆ มาประยุกต์ใช้ปฏิบัติด้วยแนวความคิดที่สร้างสรรค์เชิงบวก สนับสนุนแนวคิดใหม่ ๆ เพื่อพัฒนางานของตนเอง',
                        3 => 'ความสามารถ (Competence): ส่งเสริม/สนับสนุนให้เกิดการพัฒนาสิ่งใหม่ภายในทีมงาน แนะนำผลักดันให้นำองค์ความรู้มาต่อยอดให้เกิดนวัตกรรม หรือคิดริเริ่มวิธีการทำงานใหม่ให้หน่วยงาน',
                        4 => 'ชำนาญ (Proficiency): ให้คำแนะนำในการคิดริเริ่มโครงการใหม่ๆ วิเคราะห์เปรียบเทียบการดำเนินงานกับภายนอก เพื่อคิดค้นทางเลือกที่ดีกว่าในการปรับปรุงงานและสร้างมูลค่าเพิ่ม',
                        5 => 'เชี่ยวชาญ (Expert): เป็นตัวแทนหน่วยงานในการนำเสนอแนวคิดใหม่ที่ใช้ปรับเปลี่ยนระบบงานทั่วทั้งองค์กร คิดริเริ่มโครงการหรือนวัตกรรมที่มีผลต่อภาพลักษณ์ขององค์กร',
                    ],
                ],
                [
                    'code' => 'CORE_2',
                    'type' => 'core',
                    'name_th' => 'ความรู้ทางดิจิทัลหรือความฉลาดทางดิจิทัล (Digital Literacy / DQ)',
                    'name_en' => 'Digital Literacy or Digital Intelligence Quotient',
                    'def' => 'ความรู้และความสามารถในการเข้าถึง จัดการ เข้าใจ รวบรวม ประเมิน และสร้างสารสนเทศให้ปลอดภัยและเหมาะสม ตลอดจนถึงสามารถใช้เทคโนโลยีดิจิทัลเพื่อประกอบการทำงานได้อย่างมีประสิทธิภาพ',
                    'levels' => [
                        1 => 'พื้นฐาน (Basic): มีความรู้และความสามารถในการใช้เครื่องมือและเทคโนโลยีต่าง ๆ ประกอบการทำงานได้',
                        2 => 'ประยุกต์ใช้ (Apply): ค้นหาข้อมูลออนไลน์ นำมาวิเคราะห์และตัดสินใจได้อย่างมีคุณภาพ ทักษะในการอ้างอิงและเข้าใจลิขสิทธิ์ข้อมูล',
                        3 => 'ความสามารถ (Competence): ใช้เทคโนโลยีได้ถูกต้อง นำเสนอด้วย Presentation Tools ได้เป็นอย่างดี มีการคิดเชิงวิพากษ์ (Critical Thinking)',
                        4 => 'ชำนาญ (Proficiency): สื่อสารและทำงานรูปแบบใหม่ ใช้เครื่องมือและ AI ในการทำงานร่วมกันได้อย่างรวดเร็วและมีประสิทธิภาพ',
                        5 => 'เชี่ยวชาญ (Expert): สนับสนุนการวิจัยและพัฒนาสารสนเทศในองค์กร ส่งเสริมเทคโนโลยีใหม่เพื่อสร้างโครงสร้างพื้นฐานรองรับ Digital Transformation',
                    ],
                ],
                [
                    'code' => 'CORE_3',
                    'type' => 'core',
                    'name_th' => 'การใฝ่รู้และพัฒนาตนเอง (Lifelong Learning and Self Development)',
                    'name_en' => 'Lifelong Learning and Self Development',
                    'def' => 'สนใจ ใฝ่รู้และตระหนักถึงความสำคัญของการเพิ่มศักยภาพการปฏิบัติงานจากการมีความรู้ มีความตั้งใจและมุ่งมั่นในการเป็นบุคคลแห่งการเรียนรู้ มีการนำความรู้มาประยุกต์ปรับใช้ในงานที่รับผิดชอบเพื่อพัฒนาตนเองและองค์กร',
                    'levels' => [
                        1 => 'พื้นฐาน (Basic): มีความรู้และความเข้าใจถึงประโยชน์ของการใฝ่รู้และพัฒนาตนเองอย่างสม่ำเสมอ',
                        2 => 'ประยุกต์ใช้ (Apply): ศึกษา ค้นคว้า และรับข้อมูลที่เป็นประโยชน์มาประยุกต์ใช้ในการปฏิบัติงานอย่างต่อเนื่อง',
                        3 => 'ความสามารถ (Competence): พัฒนานำข้อมูลจากการศึกษาเรียนรู้ อบรม มาใช้ในการทำงานให้มีประสิทธิภาพมากขึ้น',
                        4 => 'ชำนาญ (Proficiency): นำความรู้มาปรับปรุงการทำงานทั้งเชิงลึกและเชิงกว้าง ถ่ายทอดความรู้และทักษะให้กับผู้อื่นทั้งในและนอกหน่วยงาน',
                        5 => 'เชี่ยวชาญ (Expert): สร้างวัฒนธรรมแห่งการเรียนรู้ เป็นบุคคลแห่งการเรียนรู้ นำข้อมูลภายนอกมาปรับใช้เพื่อแก้ปัญหาในระดับหน่วยงาน',
                    ],
                ],
                [
                    'code' => 'CORE_4',
                    'type' => 'core',
                    'name_th' => 'การทำงานเป็นทีม (Teamwork)',
                    'name_en' => 'Teamwork',
                    'def' => 'ทำงานเป็นทีม เปิดใจกว้าง รับฟังความคิดเห็น เรียนรู้และแก้ไขปัญหาร่วมกันอย่างมีประสิทธิภาพ เพื่อบรรลุเป้าหมายเดียวกัน',
                    'levels' => [
                        1 => 'พื้นฐาน (Basic): ปฏิบัติงานร่วมกับทีมได้ตามบทบาท เปิดใจรับฟังความคิดเห็น ปฏิบัติตามกฎระเบียบและแนวทางของทีม มีความสัมพันธ์ที่ดีกับสมาชิกในทีม',
                        2 => 'ประยุกต์ใช้ (Apply): เข้าใจบทบาทหน้าที่และขั้นตอนการทำงานของตนเองและสมาชิกในกลุ่ม',
                        3 => 'ความสามารถ (Competence): พัฒนาศักยภาพสมาชิกในกลุ่มเพื่อพัฒนาทีมงาน ปลูกฝังวัฒนธรรมการทำงานเป็นทีมและสร้างความสามัคคีในองค์กร',
                        4 => 'ชำนาญ (Proficiency): พัฒนาศักยภาพสมาชิกในกลุ่มเพื่อพัฒนาทีมงาน ปลูกฝังวัฒนธรรมการทำงานเป็นทีมและสร้างความสามัคคีในองค์กรอย่างต่อเนื่อง',
                        5 => 'เชี่ยวชาญ (Expert): วางแผนงานกำหนดตัวชี้วัดความสำเร็จ ติดตามผล ให้คำแนะนำและสร้างบรรยากาศการมีส่วนร่วม นำสมาชิกไปสู่เป้าหมายเดียวกัน',
                    ],
                ],
                [
                    'code' => 'FUNC_5',
                    'type' => 'functional',
                    'name_th' => 'ความเชี่ยวชาญในการปฏิบัติงาน (Expertise)',
                    'name_en' => 'Expertise',
                    'def' => 'ความสนใจ ใฝ่รู้ในอันที่จะสั่งสมความรู้ความสามารถของตน ด้วยการศึกษา ค้นคว้า และพัฒนาตนเองอย่างต่อเนื่อง จนสามารถประยุกต์ใช้ความรู้ทางวิชาการและเทคโนโลยีต่าง ๆ เพื่อใช้ในการปฏิบัติหน้าที่ให้เกิดประโยชน์สูงสุดได้',
                    'levels' => [
                        1 => 'พื้นฐาน (Basic): แสดงความสนใจและติดตามความรู้ใหม่ ๆ ในสาขาอาชีพของตน ศึกษาหาความรู้ สนใจเทคโนโลยีใหม่ ๆ',
                        2 => 'ประยุกต์ใช้ (Apply): มีความรู้ในวิชาการและเทคโนโลยีใหม่ ๆ นำมาประยุกต์ใช้ในการปฏิบัติหน้าที่ รับรู้ถึงแนวโน้มวิทยาการที่ทันสมัย',
                        3 => 'ความสามารถ (Competence): นำความรู้ วิทยาการ หรือเทคโนโลยีใหม่ ๆ มาปรับใช้กับการปฏิบัติหน้าที่และแก้ไขปัญหา',
                        4 => 'ชำนาญ (Proficiency): พัฒนาตนเองให้มีความเชี่ยวชาญในงานมากขึ้นทั้งเชิงลึกและเชิงกว้างแบบบูรณาการจากหลายศาสตร์',
                        5 => 'เชี่ยวชาญ (Expert): สนับสนุนให้เกิดบรรยากาศแห่งการแลกเปลี่ยนเรียนรู้ จัดสรรทรัพยากรเครื่องมือที่เอื้อต่อการพัฒนาบริหารจัดการให้หน่วยงาน',
                    ],
                ],
                [
                    'code' => 'FUNC_6',
                    'type' => 'functional',
                    'name_th' => 'จิตบริการ (Service Mind)',
                    'name_en' => 'Service Mind',
                    'def' => 'ความตั้งใจและความพยายามของบุคลากรในการให้บริการต่อผู้รับบริการ ทั้งภายในและภายนอกหน่วยงาน',
                    'levels' => [
                        1 => 'พื้นฐาน (Basic): ให้บริการที่เป็นมิตร สุภาพ ให้ข้อมูลถูกต้องชัดเจน แจ้งความคืบหน้า และประสานงานเพื่อให้ได้รับบริการที่รวดเร็ว',
                        2 => 'ประยุกต์ใช้ (Apply): ช่วยเหลือหรือหาแนวทางแก้ไขปัญหาที่เกิดขึ้นแก่ผู้รับบริการอย่างรวดเร็ว ด้วยความเต็มใจตามขั้นตอนที่ถูกต้อง',
                        3 => 'ความสามารถ (Competence): ให้บริการที่เกินความคาดหวัง ให้ข้อมูลที่เป็นประโยชน์แม้ไม่ได้ถาม และนำเสนอวิธีการที่ผู้รับบริการได้ประโยชน์สูงสุด',
                        4 => 'ชำนาญ (Proficiency): เข้าใจและให้บริการที่ตรงตามความต้องการที่แท้จริงของผู้รับบริการได้',
                        5 => 'เชี่ยวชาญ (Expert): ให้บริการที่เป็นประโยชน์อย่างแท้จริงในระยะยาว พร้อมที่จะเปลี่ยนวิธีหรือขั้นตอนเพื่อประโยชน์สูงสุดของผู้รับบริการ',
                    ],
                ],
                [
                    'code' => 'FUNC_7',
                    'type' => 'functional',
                    'name_th' => 'ความผูกพันในงานและองค์กร (Employee Engagement)',
                    'name_en' => 'Employee Engagement',
                    'def' => 'จิตสำนึกหรือความตั้งใจที่จะแสดงออกซึ่งพฤติกรรมที่สอดคล้องกับความต้องการ และเป้าหมายของหน่วยงาน ยึดถือประโยชน์ของหน่วยงานเป็นที่ตั้งก่อนประโยชน์ส่วนตน',
                    'levels' => [
                        1 => 'พื้นฐาน (Basic): ปฏิบัติตนเป็นส่วนหนึ่งขององค์กร โดยเคารพและถือปฏิบัติตามแบบแผนและธรรมเนียมปฏิบัติของหน่วยงาน',
                        2 => 'ประยุกต์ใช้ (Apply): มีความพึงพอใจและภาคภูมิใจที่เป็นส่วนหนึ่งขององค์กร มีส่วนสร้างภาพลักษณ์และชื่อเสียงที่ดีให้กับหน่วยงาน',
                        3 => 'ความสามารถ (Competence): มีส่วนร่วมในการผลักดันพันธกิจของหน่วยงานจนบรรลุเป้าหมาย จัดลำดับความเร่งด่วนของงานให้บรรลุเป้าหมาย',
                        4 => 'ชำนาญ (Proficiency): ยึดถือประโยชน์ของหน่วยงานเป็นที่ตั้ง ยืนหยัดในการตัดสินใจที่เป็นประโยชน์ต่อหน่วยงาน',
                        5 => 'เชี่ยวชาญ (Expert): เสียสละประโยชน์ของตนเองเพื่อประโยชน์ของหน่วยงานโดยรวม และโน้มน้าวผู้อื่นให้เสียสละประโยชน์ส่วนตนเพื่อหน่วยงาน',
                    ],
                ],
            ];

            foreach ($competencies as $idx => $comp) {
                $db->createCommand()->insert('{{%competency_definitions}}', [
                    'template_version_id' => $vId,
                    'competency_code' => $comp['code'],
                    'competency_type' => $comp['type'],
                    'name_th' => $comp['name_th'],
                    'name_en' => $comp['name_en'],
                    'definition' => $comp['def'],
                    'expected_level' => 3,
                    'sort_order' => $idx + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
                $cDefId = $db->getLastInsertID();

                $labels = [1 => 'Basic', 2 => 'Apply', 3 => 'Competence', 4 => 'Proficiency', 5 => 'Expert'];
                foreach ($comp['levels'] as $lvl => $desc) {
                    $db->createCommand()->insert('{{%competency_levels}}', [
                        'competency_definition_id' => $cDefId,
                        'level_value' => $lvl,
                        'level_label' => $labels[$lvl],
                        'behavior_description' => $desc,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->execute();
                }
            }
        }
    }

    /**
     * Seed Template สำหรับ พนักงานราชการ
     */
    private function seedGovtTemplate($typeId, $adminUserId)
    {
        $db = Yii::$app->db;
        $now = time();

        $tId = $db->createCommand("SELECT id FROM {{%evaluation_templates}} WHERE code = 'TEMPLATE_GOVT_2569'")->queryScalar();
        if (!$tId) {
            $db->createCommand()->insert('{{%evaluation_templates}}', [
                'personnel_type_id' => $typeId,
                'code' => 'TEMPLATE_GOVT_2569',
                'name_th' => 'แบบประเมินผลการปฏิบัติงานพนักงานราชการทั่วไป',
                'description' => 'ผลสัมฤทธิ์ของงาน (80%) และพฤติกรรมการปฏิบัติงาน 5 สมรรถนะ (20%)',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $tId = $db->getLastInsertID();
        }

        $vId = $db->createCommand("SELECT id FROM {{%template_versions}} WHERE evaluation_template_id = :tid AND version_number = 1", [':tid' => $tId])->queryScalar();
        if (!$vId) {
            $formula = [
                'type' => 'GOVT_FORMULA',
                'performance_weight' => 80.0,
                'behavior_weight' => 20.0,
                'main_work_weight' => 80.0,
                'secondary_work_weight' => 20.0,
            ];
            $db->createCommand()->insert('{{%template_versions}}', [
                'evaluation_template_id' => $tId,
                'version_number' => 1,
                'version_label' => 'ฉบับมาตรฐาน ปีงบประมาณ 2569',
                'is_active' => 1,
                'effective_from' => '2025-10-01',
                'total_weight' => 100.00,
                'score_formula_config' => json_encode($formula, JSON_UNESCAPED_UNICODE),
                'created_by' => $adminUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $vId = $db->getLastInsertID();

            // Section 1: ส่วนที่ 2 ภาระงานหลัก (80 คะแนน) - 4 ตัวชี้วัดต่อภาระงาน
            $db->createCommand()->insert('{{%evaluation_sections}}', [
                'template_version_id' => $vId,
                'section_code' => 'GOVT_MAIN_WORK',
                'name_th' => 'ส่วนที่ ๒ การประเมินผลสัมฤทธิ์ของงาน: ภาระงานหลัก (ค่าน้ำหนัก 80)',
                'description' => 'ประเมิน 4 ปัจจัย: ปริมาณผลงาน (25), คุณภาพ (25), ความรวดเร็ว (15), ความคุ้มค่า (15) ระดับ 1(<50%), 2(50-59%), 3(60-69%), 4(70-79%), 5(>=80%)',
                'weight' => 80.00,
                'sort_order' => 1,
                'section_type' => 'main_work',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $secGovtMainId = $db->getLastInsertID();

            $db->createCommand()->insert('{{%evaluation_items}}', [
                'evaluation_section_id' => $secGovtMainId,
                'item_code' => 'GOVT_MAIN_ITEMS',
                'name_th' => 'ภาระงานหลักพนักงานราชการ (ระบุภาระงานและประเมินตาม 4 ปัจจัย)',
                'input_type' => 'govt_kpi_row',
                'max_score' => 5.00,
                'max_weight' => 30.00,
                'sort_order' => 1,
                'is_required' => 1,
                'requires_evidence' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            // Section 2: ส่วนที่ 2 ภาระงานรอง (20 คะแนน) - 10 รายการ
            $db->createCommand()->insert('{{%evaluation_sections}}', [
                'template_version_id' => $vId,
                'section_code' => 'GOVT_SECONDARY_WORK',
                'name_th' => '๖. ภาระงานรองหรืองานที่ได้รับมอบหมาย (ค่าน้ำหนัก ๒๐)',
                'description' => 'เกณฑ์: 6-10 ข้อ = 5 คะแนน, 5 ข้อ = 4 คะแนน, 3-4 ข้อ = 3 คะแนน, 2 ข้อ = 2 คะแนน, 1 ข้อ = 1 คะแนน',
                'weight' => 20.00,
                'sort_order' => 2,
                'section_type' => 'secondary_work',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $secGovtSecId = $db->getLastInsertID();

            $govtSecondaryOptions = [
                ['key' => '1', 'text' => 'เข้าร่วมกิจกรรม/โครงการ/งานของสำนักฯ และมหาวิทยาลัย'],
                ['key' => '2', 'text' => 'ดำเนินงานผลสัมฤทธิ์ที่สำคัญ (Key Results - KR) ตามประเด็นยุทธศาสตร์ของสำนักฯ'],
                ['key' => '3', 'text' => 'เป็นคณะทำงานหรือมีส่วนร่วมในการดำเนินงาน เช่น งานความเสี่ยง / KM / งาน EdPEx'],
                ['key' => '4', 'text' => 'มีนวัตกรรมหรือการพัฒนากระบวนการทำงาน /การทำ LEAN Management /การทำ Kaizen'],
                ['key' => '5', 'text' => 'เข้าร่วมฝึกทักษะ หรือพัฒนาสมรรถนะวิชาชีพ และมีการรายงานการนำไปใช้ประโยชน์'],
                ['key' => '6', 'text' => 'ได้รับการพัฒนาตนเองผ่านมาตรฐาน Certified จากหน่วยงานภายนอก (ย้อนหลัง 1 ปี)'],
                ['key' => '7', 'text' => 'พัฒนาศักยภาพด้านการใช้ภาษาอังกฤษของสายสนับสนุน (เรียน/อบรม/ทดสอบ)'],
                ['key' => '8', 'text' => 'งานวิจัย / งานส่งเสริมความเป็นนานาชาติ / งานบริการวิชาการ / ทำนุบำรุงศิลปวัฒนธรรม'],
                ['key' => '9', 'text' => 'การหารายได้เข้าสำนักฯ'],
                ['key' => '10', 'text' => 'อื่น ๆ ที่ได้รับมอบหมาย'],
            ];

            $db->createCommand()->insert('{{%evaluation_items}}', [
                'evaluation_section_id' => $secGovtSecId,
                'item_code' => 'GOVT_SECONDARY_CHECKLIST',
                'name_th' => 'รายการภาระงานรองพนักงานราชการ (10 รายการ)',
                'input_type' => 'checkbox_list',
                'max_score' => 5.00,
                'default_weight' => 20.00,
                'sort_order' => 1,
                'requires_evidence' => 1,
                'options_data' => json_encode($govtSecondaryOptions, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            // Section 3: ส่วนที่ 3 พฤติกรรมการปฏิบัติงาน (20 คะแนน) - 5 สมรรถนะ
            $db->createCommand()->insert('{{%evaluation_sections}}', [
                'template_version_id' => $vId,
                'section_code' => 'GOVT_BEHAVIOR',
                'name_th' => 'ส่วนที่ ๓ การประเมินพฤติกรรมการปฏิบัติงาน (ค่าน้ำหนัก ๒๐)',
                'description' => 'สมรรถนะ 5 ด้าน ระดับ 1-5 (ต่ำกว่ากำหนดมาก ถึง เกินกว่ากำหนดมาก)',
                'weight' => 20.00,
                'sort_order' => 3,
                'section_type' => 'competency',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            $govtBehaviors = [
                ['code' => 'GOVT_BEH_1', 'name_th' => '๑. การมุ่งผลสัมฤทธิ์', 'def' => 'ทำงานตามเป้าหมาย ละเอียดรอบคอบ ตรวจตราความถูกต้อง ติดตามประเมินผล พัฒนาระบบให้ได้ผลงานโดดเด่น'],
                ['code' => 'GOVT_BEH_2', 'name_th' => '๒. บริการที่ดี', 'def' => 'ให้บริการด้วยความมุ่งมั่น เต็มใจ แก้ไขปัญหาอย่างรวดเร็ว ให้บริการเกินความคาดหวัง และติดตามประเมินความพึงพอใจ'],
                ['code' => 'GOVT_BEH_3', 'name_th' => '๓. การวิเคราะห์', 'def' => 'วางแผนงานกำหนดกิจกรรมขั้นตอนอย่างมีประสิทธิภาพ คาดการณ์ปัญหาอุปสรรค เสนอแนะทางเลือก และจัดลำดับความเร่งด่วน'],
                ['code' => 'GOVT_BEH_4', 'name_th' => '๔. การยึดมั่นในความถูกต้องชอบธรรม และจริยธรรม', 'def' => 'ปฏิบัติหน้าที่ด้วยความซื่อสัตย์สุจริตตามระเบียบกฎหมาย รักษาความลับทางราชการ มีจิตสำนึก และเป็นแบบอย่างที่ดี'],
                ['code' => 'GOVT_BEH_5', 'name_th' => '๕. การดำเนินการเชิงรุก', 'def' => 'วางแผนล่วงหน้ารอบคอบ เปิดใจรับความคิดใหม่ แปลงปัญหาเป็นโอกาส และสร้างบรรยากาศความคิดริเริ่ม'],
            ];

            foreach ($govtBehaviors as $idx => $gb) {
                $db->createCommand()->insert('{{%competency_definitions}}', [
                    'template_version_id' => $vId,
                    'competency_code' => $gb['code'],
                    'competency_type' => 'behavior',
                    'name_th' => $gb['name_th'],
                    'definition' => $gb['def'],
                    'expected_level' => 3,
                    'sort_order' => $idx + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
                $cDefId = $db->getLastInsertID();

                $labels = [1 => 'ต่ำกว่ากำหนดมาก', 2 => 'ต่ำกว่ากำหนด', 3 => 'ตามกำหนด', 4 => 'เกินกว่าที่กำหนด', 5 => 'เกินกว่าที่กำหนดมาก'];
                foreach ($labels as $lvl => $lbl) {
                    $db->createCommand()->insert('{{%competency_levels}}', [
                        'competency_definition_id' => $cDefId,
                        'level_value' => $lvl,
                        'level_label' => $lbl,
                        'behavior_description' => "แสดงพฤติกรรมในระดับ {$lbl}",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->execute();
                }
            }
        }
    }

    /**
     * Seed Template สำหรับ พนักงานพิเศษเงินรายได้
     */
    private function seedSpecialTemplate($typeId, $adminUserId)
    {
        $db = Yii::$app->db;
        $now = time();

        $tId = $db->createCommand("SELECT id FROM {{%evaluation_templates}} WHERE code = 'TEMPLATE_SPECIAL_2569'")->queryScalar();
        if (!$tId) {
            $db->createCommand()->insert('{{%evaluation_templates}}', [
                'personnel_type_id' => $typeId,
                'code' => 'TEMPLATE_SPECIAL_2569',
                'name_th' => 'แบบประเมินผลการปฏิบัติงานของพนักงานพิเศษเงินรายได้',
                'description' => 'ผลงาน 55 คะแนน (ผลงานหลัก 50 + งานรอง 5) และคุณลักษณะการปฏิบัติงาน 45 คะแนน รวม 100 คะแนน',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $tId = $db->getLastInsertID();
        }

        $vId = $db->createCommand("SELECT id FROM {{%template_versions}} WHERE evaluation_template_id = :tid AND version_number = 1", [':tid' => $tId])->queryScalar();
        if (!$vId) {
            $formula = [
                'type' => 'SPECIAL_DIRECT',
                'performance_weight' => 55.0,
                'characteristics_weight' => 45.0,
            ];
            $db->createCommand()->insert('{{%template_versions}}', [
                'evaluation_template_id' => $tId,
                'version_number' => 1,
                'version_label' => 'ฉบับมาตรฐาน ปีงบประมาณ 2569',
                'is_active' => 1,
                'effective_from' => '2025-10-01',
                'total_weight' => 100.00,
                'score_formula_config' => json_encode($formula, JSON_UNESCAPED_UNICODE),
                'created_by' => $adminUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $vId = $db->getLastInsertID();

            // ด้านที่ 1: ผลงาน (55 คะแนน)
            $db->createCommand()->insert('{{%evaluation_sections}}', [
                'template_version_id' => $vId,
                'section_code' => 'SPEC_PERFORMANCE',
                'name_th' => 'ด้านที่ ๑ ผลงาน (คะแนนเต็ม ๕๕ คะแนน)',
                'description' => 'ปริมาณ (10), คุณภาพ (10), ความทันเวลา (10), ความคุ้มค่า (10), ผลสัมฤทธิ์ (10), งานรอง (5)',
                'weight' => 55.00,
                'sort_order' => 1,
                'section_type' => 'main_work',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $secSpec1Id = $db->getLastInsertID();

            $specPerfItems = [
                ['code' => 'SPEC_1_1', 'name' => '๑.๑ ปริมาณผลงาน (เปรียบเทียบกับเป้าหมาย ข้อตกลง หรือมาตรฐานงาน)', 'max' => 10.00],
                ['code' => 'SPEC_1_2', 'name' => '๑.๒ คุณภาพของงาน (ความถูกต้อง ครบถ้วน สมบูรณ์ และประณีต)', 'max' => 10.00],
                ['code' => 'SPEC_1_3', 'name' => '๑.๓ ความทันเวลา (เวลาที่ใช้เทียบกับเวลาที่กำหนดไว้)', 'max' => 10.00],
                ['code' => 'SPEC_1_4', 'name' => '๑.๔ ความคุ้มค่าของการใช้ทรัพยากร (ความสัมพันธ์ระหว่างทรัพยากรกับผลผลิต)', 'max' => 10.00],
                ['code' => 'SPEC_1_5', 'name' => '๑.๕ ผลสัมฤทธิ์ของงานที่ปฏิบัติได้ (ผลผลิต/ผลลัพธ์เทียบกับเป้าหมาย)', 'max' => 10.00],
            ];

            foreach ($specPerfItems as $idx => $item) {
                $db->createCommand()->insert('{{%evaluation_items}}', [
                    'evaluation_section_id' => $secSpec1Id,
                    'item_code' => $item['code'],
                    'name_th' => $item['name'],
                    'input_type' => 'score_direct',
                    'max_score' => $item['max'],
                    'default_weight' => $item['max'],
                    'sort_order' => $idx + 1,
                    'is_required' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            }

            // ภาระงานรองพนักงานพิเศษ (5 คะแนน)
            $specSecondaryOptions = [
                ['key' => '1', 'text' => 'เข้าร่วมกิจกรรม/โครงการ/งานของสำนักฯ และมหาวิทยาลัย'],
                ['key' => '2', 'text' => 'ดำเนินการขับเคลื่อนผลสัมฤทธิ์ที่สำคัญ (Key Results - KR) ตามประเด็นยุทธศาสตร์'],
                ['key' => '3', 'text' => 'คณะทำงานหรือมีส่วนร่วมในการดำเนินงาน เช่น งานความเสี่ยง / KM / EdPEx'],
                ['key' => '4', 'text' => 'มีนวัตกรรมหรือการพัฒนากระบวนการทำงาน / LEAN / Kaizen'],
                ['key' => '5', 'text' => 'เข้าร่วมฝึกทักษะหรือพัฒนาสมรรถนะวิชาชีพ และมีการรายงานการนำไปใช้ประโยชน์'],
                ['key' => '6', 'text' => 'ได้รับการพัฒนาตนเองผ่านมาตรฐาน Certified จากหน่วยงานภายนอก (ย้อนหลัง 1 ปี)'],
                ['key' => '7', 'text' => 'พัฒนาศักยภาพด้านการใช้ภาษาอังกฤษของสายสนับสนุน (มีใบรับรอง)'],
                ['key' => '8', 'text' => 'งานส่งเสริมความเป็นนานาชาติ / บริการวิชาการ / ทำนุบำรุงศิลปวัฒนธรรม'],
                ['key' => '9', 'text' => 'การหารายได้เข้าสำนักฯ'],
                ['key' => '10', 'text' => 'อื่น ๆ ที่ได้รับมอบหมาย'],
            ];

            $db->createCommand()->insert('{{%evaluation_items}}', [
                'evaluation_section_id' => $secSpec1Id,
                'item_code' => 'SPEC_1_6_SECONDARY',
                'name_th' => '๑.๖ องค์ประกอบอื่น ๆ (ภาระงานอื่น หรืองานที่ได้รับมอบหมาย - 5 คะแนน)',
                'description' => 'เกณฑ์: 6-10 ข้อ=5, 5 ข้อ=4, 3-4 ข้อ=3, 2 ข้อ=2, 1 ข้อ=1 คะแนน',
                'input_type' => 'checkbox_list',
                'max_score' => 5.00,
                'default_weight' => 5.00,
                'sort_order' => 6,
                'requires_evidence' => 1,
                'options_data' => json_encode($specSecondaryOptions, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            // ด้านที่ 2: คุณลักษณะการปฏิบัติงาน (45 คะแนน)
            $db->createCommand()->insert('{{%evaluation_sections}}', [
                'template_version_id' => $vId,
                'section_code' => 'SPEC_CHARACTERISTICS',
                'name_th' => 'ด้านที่ ๒ คุณลักษณะการปฏิบัติงาน (คะแนนเต็ม ๔๕ คะแนน)',
                'description' => 'ความสามารถ(10), วินัย(10), ความรับผิดชอบ(5), ความร่วมมือ(5), การมาทำงาน(5), การวางแผน(5), ความคิดริเริ่ม(5)',
                'weight' => 45.00,
                'sort_order' => 2,
                'section_type' => 'general',
                'is_evaluator_fill' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $secSpec2Id = $db->getLastInsertID();

            $specCharItems = [
                ['code' => 'SPEC_2_1', 'name' => '๒.๑ ความสามารถ และความอุตสาหะในการปฏิบัติงาน (ความรอบรู้ ขยันหมั่นเพียร ไม่ย่อท้อ)', 'max' => 10.00],
                ['code' => 'SPEC_2_2', 'name' => '๒.๒ การรักษาวินัย และปฏิบัติตนเหมาะสมกับการเป็นพนักงานพิเศษเงินรายได้', 'max' => 10.00],
                ['code' => 'SPEC_2_3', 'name' => '๒.๓ ความรับผิดชอบ (ปฏิบัติหน้าที่โดยเต็มใจ มุ่งมั่น และยอมรับผลการทำงาน)', 'max' => 5.00],
                ['code' => 'SPEC_2_4', 'name' => '๒.๔ ความร่วมมือ (ทำงานร่วมกับผู้อื่นได้อย่างเหมาะสม)', 'max' => 5.00],
                ['code' => 'SPEC_2_5', 'name' => '๒.๕ สภาพการมาปฏิบัติงาน (ตรงต่อเวลา การลา หยุดงาน การขาดงาน)', 'max' => 5.00],
                ['code' => 'SPEC_2_6', 'name' => '๒.๖ การวางแผน (คาดการณ์ วิเคราะห์ข้อมูล กำหนดเป้าหมายและวิธีปฏิบัติ)', 'max' => 5.00],
                ['code' => 'SPEC_2_7', 'name' => '๒.๗ ความคิดริเริ่ม (คิดริเริ่มปรับปรุงงาน และนำความคิดเห็นมาใช้ประโยชน์)', 'max' => 5.00],
            ];

            foreach ($specCharItems as $idx => $item) {
                $db->createCommand()->insert('{{%evaluation_items}}', [
                    'evaluation_section_id' => $secSpec2Id,
                    'item_code' => $item['code'],
                    'name_th' => $item['name'],
                    'input_type' => 'score_direct',
                    'max_score' => $item['max'],
                    'default_weight' => $item['max'],
                    'sort_order' => $idx + 1,
                    'is_required' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            }
        }
    }
}
