# ระบบประเมินผลการปฏิบัติงานบุคลากร มทร.ธัญบุรี
## (RMUTT Personnel Performance Evaluation System)

ระบบประเมินผลการปฏิบัติงานของบุคลากรมหาวิทยาลัยเทคโนโลยีราชมงคลธัญบุรี พัฒนาด้วย **Yii2 Advanced Framework** บนสถาปัตยกรรม **Docker Container** รองรับการประเมินผลตามระเบียบมหาวิทยาลัยครบทั้ง 4 ประเภทบุคลากร

---

## 🚀 สถาปัตยกรรมระบบ (Architecture & Port Mapping)

| บริการ (Service) | พอร์ต (Port) | คำอธิบาย |
| :--- | :---: | :--- |
| **Frontend Portal** | `8000` | ระบบสำหรับบุคลากรทั่วไป (ประเมินตนเอง, แนบหลักฐาน, รับทราบผล, และหัวหน้างานประเมิน) |
| **Backend Admin Portal** | `8001` | ระบบบริหารจัดการสำหรับผู้ดูแลระบบและ HR (ติดตามการประเมิน, รายงาน, จัดการบุคลากร/แอดมิน) |
| **phpMyAdmin** | `8080` | ระบบจัดการฐานข้อมูล MySQL ผ่านเว็บเบราว์เซอร์ |
| **MySQL Database** | `3306` | MySQL 8.0 Database Server |

---

## 🔑 บัญชีผู้ใช้งานเริ่มต้น (Default Credentials)

> **รหัสผ่านเริ่มต้นสำหรับทุกบัญชีคือ:** `123456`

### 1. ผู้ดูแลระบบ (System & HR Administrators)
- **`admin`** : ผู้ดูแลระบบสูงสุด (Super Administrator) - สิทธิ์เต็มทุกระบบ
- **`admin_hr`** : เจ้าหน้าที่กองบริหารงานบุคคล (Central HR Admin) - ดูแลภาพรวมทั้งมหาวิทยาลัย
- **`admin_arit`** : ผู้ดูแลระดับหน่วยงาน (ARIT Admin) - ดูแลเฉพาะสังกัดสำนักวิทยบริการและเทคโนโลยีสารสนเทศ

### 2. บัญชีตัวอย่างสำหรับการทดสอบ (Sample Test Accounts)
- **`staff_univ`** : นายวิชัย มหาวิทยาลัยวิทย์ (พนักงานมหาวิทยาลัย - แบบประเมินชุดที่ 2)
- **`staff_special`** : นายเอกชัย พิเศษสมบูรณ์ (พนักงานพิเศษเงินรายได้ - แบบประเมินชุดที่ 4)
- **`supervisor`** : ดร.สมเกียรติ พัฒนาวิทย์ (หัวหน้างานพัฒนาระบบ - ผู้ประเมิน L1)

---

## 🛠️ วิธีการติดตั้งและเริ่มใช้งาน (Quick Start)

### 1. การคัดลอกไฟล์ Environment
```bash
cp .env.example .env
```

### 2. การเริ่มต้นระบบผ่าน Docker Compose
```bash
docker compose up -d
```
ระบบจะเริ่มต้น Containers:
- `rmutt_nginx` (Web Server)
- `rmutt_php` (PHP 8.2 Application Server)
- `rmutt_db` (MySQL 8.0 Database Server พร้อม Auto-import `docker/mysql/init.sql`)
- `rmutt_phpmyadmin` (Database GUI)

### 3. ตรวจสอบสถานะการทำงาน
```bash
docker compose ps
```

---

## 📁 โครงสร้างโปรเจกต์ (Directory Structure)

```text
├── backend/            # ระบบบริหารจัดการสำหรับ HR Admin (พอร์ต 8001)
├── common/             # โค้ดและโมเดลที่ใช้ร่วมกัน (User, Personnel, Evaluation, ฯลฯ)
├── console/            # Console commands และ Migrations
├── docker/             # Docker configuration files และฐานข้อมูลเริ่มต้น init.sql
├── environments/       # ค่าคอนฟิกเริ่มต้นตามสภาพแวดล้อม (dev, prod)
├── frontend/           # ระบบพอร์ทัลสำหรับบุคลากรและผู้ประเมิน (พอร์ต 8000)
├── reference/          # เอกสารอ้างอิงและแบบฟอร์มต้นฉบับ
├── .env.example        # ไฟล์ตัวอย่างการตั้งค่าสภาพแวดล้อม
└── docker-compose.yml  # ไฟล์กำหนดคอนฟิก Docker Services
```
