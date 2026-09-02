<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

// Ensure required tables exist (auto-migration pattern used across the project)
try { db_query("CREATE TABLE IF NOT EXISTS student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    doc_type VARCHAR(100),
    file_path VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB"); } catch (Throwable $ex) {}

// Repair pre-existing student_documents table that may lack the file_path column
try { db_query("ALTER TABLE student_documents ADD COLUMN IF NOT EXISTS file_path VARCHAR(255) DEFAULT NULL"); } catch (Throwable $ex) {}
try { db_query("ALTER TABLE student_documents ADD COLUMN IF NOT EXISTS doc_type VARCHAR(100) DEFAULT NULL"); } catch (Throwable $ex) {}
try { db_query("ALTER TABLE student_documents ADD COLUMN IF NOT EXISTS student_id INT DEFAULT NULL"); } catch (Throwable $ex) {}

try { db_query("CREATE TABLE IF NOT EXISTS boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB"); } catch (Throwable $ex) {}

try { db_query("CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB"); } catch (Throwable $ex) {}

try { db_query("CREATE TABLE IF NOT EXISTS admission_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB"); } catch (Throwable $ex) {}

try { db_query("CREATE TABLE IF NOT EXISTS document_titles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB"); } catch (Throwable $ex) {}

try { db_query("ALTER TABLE students ADD COLUMN IF NOT EXISTS family_code VARCHAR(50) DEFAULT NULL"); } catch (Throwable $ex) {}

// Seed default lookup values (only if empty)
function seed_if_empty($table, $nameCol, $values) {
    $c = db_query("SELECT COUNT(*) c FROM `$table`")->fetch_assoc()['c'];
    if ((int)$c === 0) {
        foreach ($values as $v) {
            $st = db_prepare("INSERT INTO `$table` (`$nameCol`) VALUES (?)");
            $st->bind_param('s', $v);
            $st->execute();
        }
    }
}
seed_if_empty('boards', 'name', ['BISE GRW', 'BISE LHR', 'BISE QTA']);
seed_if_empty('groups', 'name', ['Morning Shift', 'Evening Shift', 'FSC', 'ICS', 'Pre Engineering']);
seed_if_empty('admission_sources', 'name', ['Walk-in', 'Referral', 'Online Ads']);
seed_if_empty('document_titles', 'name', ['Beform', 'Matric Result Card', 'Inter Result Card', 'Father CNIC', 'B-Form / CNIC / Photo', 'Previous School Certificate', 'Fee Challan / DMC']);

$page_title = 'Add New Student';

$message = '';
$error = '';

// Lookups for dropdowns
function lookup_rows($sql) {
    $out = [];
    $r = db_query($sql);
    if ($r) { while ($row = $r->fetch_assoc()) { $out[] = $row; } }
    return $out;
}
$classes   = lookup_rows("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
$localities= lookup_rows("SELECT locality_id, locality_name FROM localities WHERE status=1 ORDER BY locality_name");
$boards    = lookup_rows("SELECT id, name FROM boards ORDER BY name");
$groups    = lookup_rows("SELECT id, name FROM `groups` ORDER BY name");
$admSrcs   = lookup_rows("SELECT id, name FROM admission_sources ORDER BY name");
$occupations = lookup_rows("SELECT id, name FROM occupations ORDER BY name");
$docTitles = lookup_rows("SELECT id, name FROM document_titles ORDER BY name");
$families  = lookup_rows("SELECT DISTINCT family_code FROM students WHERE family_code IS NOT NULL AND family_code <> '' ORDER BY family_code");

// Sessions list (same approach as other modules)
$sessions = [];
for ($y = 2018; $y <= 2030; $y++) { $sessions[] = $y . '-' . substr($y + 1, -2); }
$cur_session = get_setting('session_year', '2026-2027');
if (!in_array($cur_session, $sessions, true)) { array_unshift($sessions, $cur_session); }

// -------- POST: Save Student --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'AddAdmission') {
    function parse_date($d) {
        $d = trim($d);
        if ($d === '') return null;
        $ts = strtotime(str_replace('/', '-', $d));
        return $ts ? date('Y-m-d', $ts) : null;
    }
    function val($key) { return isset($_POST[$key]) ? trim($_POST[$key]) : ''; }
    function valNull($key) { $v = val($key); return $v === '' ? null : $v; }

    $first_name     = val('first_name');
    $father_name    = val('lname');
    $mother_name    = val('mother_name');
    $email          = valNull('email');
    $cellno         = val('cellno');
    $class_id       = (int) val('class');
    $section_id     = (int) val('section') ?: null;
    $dob            = val('dob');
    $date_of_adms   = val('date_of_adms');
    $gender         = strtolower(val('gender')) ?: 'male';
    $religion       = val('religion') ?: 'Islam';
    $session        = valNull('session');
    $board_council  = valNull('board_council');
    $group_shift    = valNull('group_shift');
    $adm_source     = valNull('adm_source');
    $locality_id    = val('Locality') !== '' ? (int) val('Locality') : null;
    $father_cnic    = valNull('cnic');
    $father_qual    = valNull('Fqualification');
    $father_bus     = valNull('Fbusiness_address');
    $father_income  = valNull('Fincome');
    $father_occ     = valNull('father_occupation');
    $father_cell    = valNull('father_cellno');
    $mother_cnic    = valNull('mother_cnic');
    $mother_qual    = valNull('mother_qualification');
    $mother_act     = valNull('mother_activity');
    $mother_desig   = valNull('mother_designation');
    $mother_cell    = valNull('mother_cell');
    $formBNo        = valNull('formBNo');
    $caste          = valNull('cast');
    $gname          = valNull('gname');
    $Gcnic          = valNull('Gcnic');
    $Gcellno        = valNull('Gcellno');
    $Gqual          = valNull('Gqualification');
    $Gocc           = valNull('Goccupation');
    $Gincome        = valNull('Gincome');
    $gemail         = valNull('gardian_email');
    $Gaddress       = valNull('Gaddress');
    $old_class      = valNull('old_class');
    $old_school     = valNull('old_school');
    $old_tmarks     = valNull('old_tmarks');
    $old_obtmarks   = valNull('old_obtmarks');
    $form_no        = valNull('form_no');
    $school_leaving = valNull('school_leaving');
    $whatsapp       = valNull('whatsapp_number');
    $home_number    = valNull('home_number');
    $place_of_birth = valNull('place_of_birth');
    $state          = valNull('state');
    $city           = valNull('city');
    $address        = valNull('address');
    $dob_db         = parse_date($dob);
    $adm_db         = parse_date($date_of_adms) ?? date('Y-m-d');

    // Photo: prefer uploaded file, else webcam/sample data
    $photo = null;
    if (!empty($_FILES['img_file']['name']) && $_FILES['img_file']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/uploads/students';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $ext = strtolower(pathinfo($_FILES['img_file']['name'], PATHINFO_EXTENSION));
        $photo = 's_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        if (!move_uploaded_file($_FILES['img_file']['tmp_name'], $dir . '/' . $photo)) { $photo = null; }
    } elseif (!empty($_POST['captured_image'])) {
        $dir = __DIR__ . '/uploads/students';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $data = $_POST['captured_image'];
        $prefix = 'data:image/jpeg;base64,';
        if (stripos($data, $prefix) === 0) {
            $base64 = substr($data, strlen($prefix));
            $bin = base64_decode($base64, true);
            if ($bin !== false) {
                $photo = 's_' . time() . '_' . rand(1000, 9999) . '.jpg';
                if (file_put_contents($dir . '/' . $photo, $bin) === false) { $photo = null; }
            }
        }
    }

    if ($first_name === '' || $class_id === 0) {
        $error = 'Student Name and Class are required.';
    } else {
        $cols = ['first_name','last_name','father_name','mother_name','email','phone','dob','gender',
                 'religion','session','board_council','group_shift','admission_source','locality_id',
                 'father_cnic','father_qualification','father_business_address','father_income',
                 'father_occupation','father_cellno','mother_cnic','mother_qualification','mother_activity',
                 'mother_designation','mother_cell','form_b_no','caste','guardian_name','guardian_cnic',
                 'guardian_cellno','guardian_qualification','guardian_occupation','guardian_income',
                 'guardian_email','guardian_address','old_class','old_school','old_tmarks','old_obtmarks',
                 'admission_form_no','school_leaving_reason','whatsapp_number','home_number','place_of_birth',
                 'state','city','address','class_id','section_id','admission_date','status','photo'];

        $lname = $father_name;
        $vals  = [$first_name,$lname,$father_name,$mother_name,$email,$cellno,$dob_db,$gender,
                 $religion,$session,$board_council,$group_shift,$adm_source,$locality_id,
                 $father_cnic,$father_qual,$father_bus,$father_income,
                 $father_occ,$father_cell,$mother_cnic,$mother_qual,$mother_act,
                 $mother_desig,$mother_cell,$formBNo,$caste,$gname,$Gcnic,
                 $Gcellno,$Gqual,$Gocc,$Gincome,
                 $gemail,$Gaddress,$old_class,$old_school,$old_tmarks,$old_obtmarks,
                 $form_no,$school_leaving,$whatsapp,$home_number,$place_of_birth,
                 $state,$city,$address,$class_id,$section_id,$adm_db,1,$photo];

        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO students (`' . implode('`,`', $cols) . '`) VALUES (' . $placeholders . ')';
        try {
            $stmt = db_prepare($sql);
            $types = str_repeat('s', count($vals));
            $bindVals = [$types];
            foreach ($vals as $k => $v) { $bindVals[] = &$vals[$k]; }
            call_user_func_array([$stmt, 'bind_param'], $bindVals);
            $stmt->execute();
            $studentId = $stmt->insert_id;

            if ($studentId > 0) {
                // GR Number
                $gr = substr(date('Y'), 2) . '-' . str_pad($studentId, 3, '0', STR_PAD_LEFT);
                $u = db_prepare('UPDATE students SET gr_no = ? WHERE student_id = ?');
                $u->bind_param('si', $gr, $studentId);
                $u->execute();

                // Family code: reuse the family referenced (from select2) or derive from father cell
                $family_code = valNull('family_code');
                if (!$family_code) {
                    $key = $Gcellno !== null ? $Gcellno : ($father_cell !== null ? $father_cell : null);
                    if ($key) {
                        $ex = db_prepare("SELECT family_code FROM students WHERE family_code IS NOT NULL AND family_code <> '' AND (guardian_cellno = ? OR father_cellno = ?) LIMIT 1");
                        $ex->bind_param('ss', $key, $key);
                        $ex->execute();
                        $fr = $ex->get_result()->fetch_assoc();
                        $family_code = $fr ? $fr['family_code'] : ('F-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT));
                    }
                }
                if ($family_code) {
                    $u2 = db_prepare('UPDATE students SET family_code = ? WHERE student_id = ?');
                    $u2->bind_param('si', $family_code, $studentId);
                    $u2->execute();
                }

                // Save uploaded documents
                $docTypes = isset($_POST['doc_types']) ? (array) $_POST['doc_types'] : [];
                if (!empty($_FILES['doc_files'])) {
                    $docFiles = $_FILES['doc_files'];
                    $docDir = __DIR__ . '/uploads/students/documents';
                    if (!is_dir($docDir)) { @mkdir($docDir, 0775, true); }
                    $docInsert = db_prepare('INSERT INTO student_documents (student_id, doc_type, file_path) VALUES (?, ?, ?)');
                    foreach ($docFiles['name'] as $i => $name) {
                        if (empty($name)) continue;
                        if ($docFiles['error'][$i] !== UPLOAD_ERR_OK) continue;
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $fname = 'doc_' . $studentId . '_' . time() . '_' . $i . '_' . rand(1000, 9999) . '.' . $ext;
                        if (!move_uploaded_file($docFiles['tmp_name'][$i], $docDir . '/' . $fname)) continue;
                        $dtype = isset($docTypes[$i]) ? trim($docTypes[$i]) : '';
                        $docInsert->bind_param('iss', $studentId, $dtype, $fname);
                        $docInsert->execute();
                    }
                    $docInsert->close();
                }

                // Redirect as per real site behaviour (to profile/manage students)
                $mode = $_POST['redirect_mode'] ?? 'profile';
                if ($mode === 'manage') {
                    header('Location: ' . BASE_URL . 'manage_students.php?student_id=' . $studentId);
                    exit;
                }
                $message = 'Student added successfully! GR No: ' . $gr;
            }
        } catch (Exception $ex) {
            $error = 'Error: ' . $ex->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<style>
.top-tabs-row { border-bottom: 1px solid #eaecef; padding: 10px 4px 0; }
.top-tabs-row .nav-tabs { border-bottom: none; }
.nav-tabs>li>a { border-radius: 10px 10px 0 0; padding: 10px 18px; font-weight: 600; color: #6B7280; }
.nav-tabs>li.active>a, .nav-tabs>li.active>a:hover, .nav-tabs>li.active>a:focus { border: 1px solid #FF7A1B; border-bottom-color: transparent; color: #FF7A1B; }
.icon-tabs { display: flex; gap: 6px; padding: 14px 4px; flex-wrap: wrap; }
.icon-tab-item {
  display: flex; align-items: center; gap: 8px; padding: 9px 16px; border: 1px solid #E5E7EB;
  border-radius: 999px; font-size: 13px; font-weight: 600; color: #6B7280; cursor: pointer; background: #fff; transition: all .2s;
}
.icon-tab-item.active { background: #FFF3E6; border-color: #FFD9B3; color: #FF7A1B; }
.wizard-pane { display: none; }
.wizard-pane.active { display: block; }
.pane-wrap { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; padding: 20px; margin-bottom: 16px; }
.pane-title { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 14px; }
.page-card { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; padding: 16px 20px; }
.form-group label { font-weight: 600; font-size: 12.5px; color: #374151; }

/* Step wizard */
.step-wizard { display: flex; align-items: center; gap: 0; padding: 10px 4px 0; }
.step-wizard-item { display: flex; align-items: center; gap: 8px; }
.step-circle { width: 26px; height: 26px; border-radius: 50%; background: #E5E7EB; color: #6B7280; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
.step-wizard-item.active .step-circle { background: #FF7A1B; color: #fff; }
.step-label { font-size: 13px; font-weight: 600; color: #6B7280; }
.step-wizard-item.active .step-label { color: #FF7A1B; }
.step-wizard-line { flex: 0 0 36px; height: 2px; background: #E5E7EB; margin: 0 10px; }

/* Field with add-new */
.field-with-add { position: relative; }
.field-with-add .btn-add-new { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #FF7A1B; text-decoration: none; background: #FFF3E6; padding: 3px 8px; border-radius: 999px; font-weight: 600; white-space: nowrap; }
.field-with-add select.form-control { padding-right: 84px; }

/* Section titles */
.wizard-section-title { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: #111827; margin: 14px 0; }
.wizard-section-title:before { content: ''; width: 4px; height: 18px; border-radius: 4px; background: #9B59D0; }

/* Photo box */
.photo-box { border: 1px solid #FFD9B3; border-radius: 8px; background: linear-gradient(180deg, #FFF9F4, #ffffff); box-shadow: 0 2px 8px rgba(255,124,27,0.08); min-height: 220px; padding: 12px; }
.photo-stage { position: relative; height: 210px; overflow: hidden; border: 3px solid #FF7A1B; border-radius: 12px; background: #fff; box-shadow: inset 0 0 0 3px #FFF3E6, 0 0 0 5px #fff inset; }
.photo-stage .photo-placeholder { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; color: #CBD5E1; background: linear-gradient(180deg,#FFFBF5,#fff); }
.photo-stage .photo-placeholder i { font-size: 44px; }
.photo-stage .photo-placeholder small { font-size: 11px; color: #F59E0B; font-weight: 700; }
.photo-stage img { position: relative; max-width: none; }
#image { width: 100%; height: 100%; object-fit: cover; transform-origin: center center; }
.photo-stage img#image { position: absolute; inset: 0; cursor: grab; }
.photo-stage img#image.dragging { cursor: grabbing; }
.upload-btn { display: inline-flex; align-items: center; gap: 7px; background: linear-gradient(90deg,#FF7A1B,#ff9838); color: #fff; border: none; border-radius: 8px; padding: 7px 16px; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 6px rgba(255,122,27,.3); transition: all .2s; width: 100%; justify-content: center; }
.upload-btn:hover { background: linear-gradient(90deg,#e96a0f,#ff8a26); box-shadow: 0 3px 10px rgba(255,122,27,.4); }
.upload-btn i { font-size: 15px; }
.photo-controls { margin-top: 10px; }

/* Document grid */
.doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; }
.doc-card { border: 1px solid #E5E7EB; border-radius: 12px; padding: 12px; text-align: center; transition: all .2s; background: #fff; position: relative; }
.doc-card:hover { border-color: #FFD9B3; box-shadow: 0 4px 12px rgba(255,124,27,.08); }
.doc-card.has-file { border-color: #16A34A; }
.doc-card-thumb { height: 90px; border-radius: 8px; background: #F7F9FC; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 8px; }
.doc-card-thumb i { font-size: 34px; color: #CBD5E1; }
.doc-card-thumb img { width: 100%; height: 100%; object-fit: cover; }
.doc-card-thumb i.fa-file-pdf-o { color: #DC2626; }
.doc-card-title { font-size: 12.5px; font-weight: 600; color: #111827; min-height: 30px; }
.doc-card-status { font-size: 11px; color: #94A3B8; }
.doc-card.has-file .doc-card-status { color: #16A34A; }
.doc-card-upload { display: inline-block; margin-top: 6px; font-size: 12px; font-weight: 600; color: #fff; background: #FF7A1B; padding: 5px 12px; border-radius: 999px; cursor: pointer; }
.doc-card input[type=file] { display: none; }
.doc-card-filename { font-size: 10.5px; color: #64748B; margin-top: 4px; word-break: break-all; }
.doc-pane-intro { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 14px; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; margin-bottom: 14px; }
.doc-pane-intro-text { font-size: 12.5px; color: #166534; display: flex; align-items: center; gap: 8px; }
.doc-pane-manage-btn { font-size: 12px; font-weight: 600; color: #166534; text-decoration: none; border: 1px solid #BBF7D0; background: #fff; padding: 6px 12px; border-radius: 999px; }

/* Bottom action bar */
.wizard-actions-bar { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 4px; border-top: 1px solid #E5E7EB; margin-top: 8px; }
.mandatory-note { font-size: 12px; color: #6B7280; }
.wizard-actions-buttons { display: flex; gap: 10px; }
.wizard-btn { padding: 9px 22px; border-radius: 10px; border: 1px solid #E5E7EB; background: #fff; color: #374151; font-weight: 600; font-size: 13px; cursor: pointer; }
.wizard-btn-primary { background: #FF7A1B; border-color: #FF7A1B; color: #fff; }
.wizard-btn-primary:hover { background: #e96a0c; color: #fff; }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="container mt-4 page-card" style="width:100%;">

            <!-- Breadcrumb -->
            <div style="padding:6px 4px 10px; font-size:12.5px; color:#6B7280;">
                <a href="dashboard.php" style="color:#377dff;">Dashboard</a> <i class="fa fa-angle-double-right"></i>
                <a href="manage_students.php" style="color:#377dff;">Students</a> <i class="fa fa-angle-double-right"></i>
                Add New Student
            </div>

            <!-- Tabs -->
            <div class="top-tabs-row">
                <ul class="nav nav-tabs" id="studentTabs" role="tablist">
                    <li class="active"><a href="add_student.php"><i class="fa fa-user-plus"></i> Add New Student</a></li>
                    <li><a href="bulk_stdns.php"><i class="fa fa-users"></i>&nbsp; Add Multi Students</a></li>
                    <li><a href="import_data.php"><i class="fa fa-upload"></i> &nbsp; Import Students with CSV</a></li>
                    <li><a href="adm_form.php" target="_blank"><i class="fa fa-file-text"></i> &nbsp; Admission Form</a></li>
                </ul>
            </div>

            <!-- Step Wizard -->
            <div class="step-wizard" id="stepWizard">
                <div class="step-wizard-item active" data-step="student-info">
                    <div class="step-circle">1</div>
                    <div class="step-label">Student Info</div>
                </div>
                <div class="step-wizard-line"></div>
                <div class="step-wizard-item" data-step="fee-plan">
                    <div class="step-circle">2</div>
                    <div class="step-label">Fee Plan</div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success" style="margin-top:12px;"><?php echo e($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-top:12px;"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="tab-content" id="studentTabsContent" style="margin-top:8px;">
                <div class="tab-pane active" id="add-single">

                    <!-- Icon Tabs -->
                    <ul class="icon-tabs" id="studentWizardTabs">
                        <li class="icon-tab-item active" data-tab="basic-info"><i class="fa fa-id-card"></i> Basic Information</li>
                        <li class="icon-tab-item" data-tab="parent-details"><i class="fa fa-user-friends"></i> Parent Details</li>
                        <li class="icon-tab-item" data-tab="academic-info"><i class="fa fa-graduation-cap"></i> Academic Information</li>
                        <li class="icon-tab-item" data-tab="contact-info"><i class="fa fa-phone-alt"></i> Contact Information</li>
                        <li class="icon-tab-item" data-tab="documents"><i class="fa fa-file-text"></i> Documents</li>
                    </ul>

                    <form id="studentForm" action="add_student.php" method="post" enctype="multipart/form-data" class="form-horizontal form-label-left">
                        <input type="hidden" name="action" value="AddAdmission">
                        <input type="hidden" name="captured_image" id="captured_image" value="">
                        <input type="hidden" name="redirect_mode" id="redirect_mode" value="">
                        <input type="hidden" name="family_code" id="family_code_value" value="">

                        <!-- ========== PANE 1: Basic Information ========== -->
                        <div class="wizard-pane active" id="pane-basic-info">
                            <div class="form-row">
                                <div class="col-md-8" style="padding-left:0;">
                                    <div class="form-row" style="margin-bottom:14px;">
                                        <div class="form-group col-md-3">
                                            <label>Student Name <span style="color:red;">*</span></label>
                                            <input type="text" class="form-control" name="first_name" required id="fname" placeholder="Student Name" maxlength="35" oninput="if(this.value.length>=35){document.getElementById('fname-limit-msg').style.display='block';}else{document.getElementById('fname-limit-msg').style.display='none';}">
                                            <small id="fname-limit-msg" style="display:none;color:red;">Student Name cannot be more than 35 characters.</small>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Father Name</label>
                                            <input type="text" class="form-control" name="lname" id="last_name" placeholder="Father Name" maxlength="35">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Select Family</label>
                                            <select name="family_search" id="family_search" class="form-control">
                                                <option value="">Select Family</option>
                                                <?php foreach ($families as $fam): ?>
                                                    <option value="<?php echo e($fam['family_code']); ?>"><?php echo e($fam['family_code']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Cell Number <span style="color:red;">*</span></label>
                                            <input type="text" class="form-control" name="cellno" required id="cell_no" placeholder="Number / Reporting SMS">
                                        </div>
                                    </div>
                                    <div class="form-row" style="margin-bottom:14px;">
                                        <div class="form-group col-md-3">
                                            <label>Session</label>
                                            <select name="session" id="session" class="form-control">
                                                <option value="">Select Session</option>
                                                <?php foreach ($sessions as $s): ?>
                                                    <option value="<?php echo e($s); ?>" <?php echo $s === $cur_session ? 'selected' : ''; ?>><?php echo e($s); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Select Class</label>
                                            <select name="class" required id="class" class="form-control" onchange="getSection(this.value)">
                                                <option value="">Select Class</option>
                                                <?php foreach ($classes as $c): ?>
                                                    <option value="<?php echo $c['class_id']; ?>"><?php echo e($c['class_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3" id="sec">
                                            <label>Select Section</label>
                                            <select name="section" id="txt_section" class="form-control"></select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>GR-No</label>
                                            <input type="text" value="Auto" class="form-control" name="com_no" id="com_no" readonly style="background:#f5f6fa; color:#9CA3AF;">
                                        </div>
                                    </div>
                                    <div class="form-row" style="margin-bottom:14px;">
                                        <div class="form-group col-md-3">
                                            <label>Gender <span style="color:red;">*</span></label>
                                            <select id="gender" name="gender" class="form-control" required>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Select Religion <span style="color:red;">*</span></label>
                                            <select id="religion" name="religion" class="form-control" required>
                                                <option value="Islam" selected>Muslim</option>
                                                <option value="Hinduism">Hindu</option>
                                                <option value="Sikhism">Sikh</option>
                                                <option value="Christianity">Christian</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Date Of Birth</label>
                                            <input type="text" class="form-control datepicker" name="dob" id="dob" placeholder="dd/mm/yyyy" value="<?php echo date('d/M/Y'); ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Date Of Admission</label>
                                            <input type="text" class="form-control datepicker" name="date_of_adms" id="date_of_adms" placeholder="dd/mm/yyyy" value="<?php echo date('d/M/Y'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Photo Upload Box -->
                                <div class="col-md-4">
                                    <div class="photo-box">
                                        <div class="photo-stage">
                                            <div class="photo-placeholder" id="photoPlaceholder"><i class="fa fa-user"></i><small>DP Preview</small></div>
                                            <img id="image" src="" alt="Uploaded" style="display:none;">
                                            <img id="sample-image" src="<?php echo BASE_URL; ?>assets/img/logo.jpg" alt="Sample" style="display:none; width:100%; height:100%; object-fit:cover;">
                                        </div>
                                        <div class="photo-controls row">
                                            <div class="col-md-6">
                                                <label style="font-size:11px;">Upload Picture</label>
                                                <label class="upload-btn" for="fileInput">
                                                    <i class="fa fa-image"></i> Choose File
                                                    <input type="file" name="img_file" id="fileInput" accept="image/*" style="display:none;">
                                                </label>
                                                <div id="fileNameLabel" style="font-size:11px; color:#64748B; margin-top:4px; word-break:break-all;">No file selected</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div style="margin-bottom:4px;">
                                                    <label style="font-size:11px;">Zoom</label>
                                                    <input type="range" id="zoom-slider" min="0.5" max="2" step="0.05" value="1" style="width:100%;">
                                                </div>
                                                <div style="margin-bottom:4px;">
                                                    <label style="font-size:11px;">Rotate</label>
                                                    <input type="range" id="rotate-slider" min="-180" max="180" step="1" value="0" style="width:100%;">
                                                </div>
                                                <button type="button" class="btn btn-warning btn-sm" id="btnCapturePhoto" style="width:100%; margin-top:2px;"><i class="fa fa-camera"></i> Capture Photo</button>
                                            </div>
                                        </div>
                                        <canvas id="imageCanvas" style="display:none;"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="row" style="margin-top:0;">
                                <div class="form-group col-md-3">
                                    <label>Board/Council</label>
                                    <div class="field-with-add">
                                        <select id="board_council" name="board_council" class="form-control">
                                            <option value="">Select Board/Council</option>
                                            <?php foreach ($boards as $b): ?><option value="<?php echo e($b['name']); ?>"><?php echo e($b['name']); ?></option><?php endforeach; ?>
                                        </select>
                                        <a href="manage_board.php" target="_blank" class="btn-add-new"><i class="fa fa-plus"></i> Add New</a>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Group/Shift</label>
                                    <div class="field-with-add">
                                        <select id="group_shift" name="group_shift" class="form-control">
                                            <option value="">Select Group/Shift</option>
                                            <?php foreach ($groups as $g): ?><option value="<?php echo e($g['name']); ?>"><?php echo e($g['name']); ?></option><?php endforeach; ?>
                                        </select>
                                        <a href="manage_group.php" target="_blank" class="btn-add-new"><i class="fa fa-plus"></i> Add New</a>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Admission Source</label>
                                    <div class="field-with-add">
                                        <select id="adm_source" name="adm_source" class="form-control">
                                            <option value="">Select Admission Source</option>
                                            <?php foreach ($admSrcs as $a): ?><option value="<?php echo e($a['name']); ?>"><?php echo e($a['name']); ?></option><?php endforeach; ?>
                                        </select>
                                        <a href="manage_admission_sources.php" target="_blank" class="btn-add-new"><i class="fa fa-plus"></i> Add New</a>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Choose Locality</label>
                                    <div class="field-with-add">
                                        <select name="Locality" id="locality" class="form-control">
                                            <option value="">Choose Locality</option>
                                            <?php foreach ($localities as $l): ?><option value="<?php echo $l['locality_id']; ?>"><?php echo e($l['locality_name']); ?></option><?php endforeach; ?>
                                        </select>
                                        <a href="manage_localities.php" target="_blank" class="btn-add-new"><i class="fa fa-plus"></i> Add New</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========== PANE 2: Parent Details ========== -->
                        <div class="wizard-pane" id="pane-parent-details">
                            <!-- Family Information -->
                            <div id="familyInformationSection">
                                <div class="wizard-section-title"><span>Family Information</span></div>
                                <div class="row" style="margin-top:10px;">
                                    <div class="col-md-12">
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Father CNIC</label>
                                                <input type="text" class="form-control" name="cnic" id="cnic" placeholder="CNIC">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Father Qualification</label>
                                                <input type="text" class="form-control" name="Fqualification" id="father_qualification" placeholder="Father Qualification">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Father Business Address</label>
                                                <input type="text" class="form-control" name="Fbusiness_address" id="Fbusiness_address" placeholder="Father Business Address">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Father Income</label>
                                                <input type="text" class="form-control" name="Fincome" id="father_income" placeholder="Father Income">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Mother Name</label>
                                                <input type="text" class="form-control" name="mother_name" id="mother_name" placeholder="Mother Name">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Mother CNIC</label>
                                                <input type="text" class="form-control" name="mother_cnic" id="mother_cnic" placeholder="Mother CNIC">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Mother Qualification</label>
                                                <input type="text" class="form-control" name="mother_qualification" id="mother_qualification" placeholder="Mother Qualification">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Mother Activities</label>
                                                <select id="mother_activity" name="mother_activity" class="form-control">
                                                    <option value="">Mother Activities</option>
                                                    <option value="House Wife">House Wife</option>
                                                    <option value="Working">Working</option>
                                                    <option value="Business">Business</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Mother Designation</label>
                                                <input type="text" class="form-control" name="mother_designation" id="mother_designation" placeholder="Mother Designation">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Home Address</label>
                                                <input type="text" class="form-control" name="address" id="address" placeholder="Family Home Address">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Father Occupation</label>
                                                <div class="field-with-add">
                                                    <select name="father_occupation" id="father_occupation" class="form-control">
                                                        <option value="">Choose Occupation</option>
                                                        <?php foreach ($occupations as $o): ?><option value="<?php echo e($o['name']); ?>"><?php echo e($o['name']); ?></option><?php endforeach; ?>
                                                    </select>
                                                    <a href="manage_occupations.php" target="_blank" class="btn-add-new"><i class="fa fa-plus"></i> Add New</a>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>B-Form No</label>
                                                <input type="text" class="form-control" name="formBNo" id="formBNo" placeholder="Form-B No">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Cast</label>
                                                <input type="text" class="form-control" name="cast" id="cast" placeholder="Cast">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Guardian Information -->
                            <div id="guardianInformationSection">
                                <hr>
                                <div class="wizard-section-title"><span>Guardian Information <small style="font-size:12px;color:gray;">(this part will be filled in case of death of father)</small></span></div>
                                <div class="row" style="margin-top:10px;">
                                    <div class="col-md-12">
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Guardian Name</label>
                                                <input type="text" class="form-control" name="gname" id="gardian_name" placeholder="Guardian Name">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Guardian CNIC</label>
                                                <input type="text" class="form-control" name="Gcnic" id="gardian_cnic" placeholder="Guardian CNIC">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Guardian Cell No</label>
                                                <input type="text" class="form-control" name="Gcellno" id="gardian_no" placeholder="Guardian Cell No">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Guardian Qualification</label>
                                                <input type="text" class="form-control" name="Gqualification" id="gardian_qualification" placeholder="Guardian Qualification">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Guardian Occupation</label>
                                                <input type="text" class="form-control" name="Goccupation" id="gardian_occupation" placeholder="Guardian Occupation">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Guardian Income</label>
                                                <input type="text" class="form-control" name="Gincome" id="gardian_income" placeholder="Guardian Income">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Guardian Email</label>
                                                <input type="text" class="form-control" name="gardian_email" id="gardian_email" placeholder="Guardian Email">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Guardian Address</label>
                                                <input type="text" class="form-control" name="Gaddress" id="gardian_address" placeholder="Guardian Address">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========== PANE 3: Academic Information ========== -->
                        <div class="wizard-pane" id="pane-academic-info">
                            <div id="admissionInformationSection">
                                <div class="wizard-section-title"><span>Admission &amp; Academic Information</span></div>
                                <div class="row" style="margin-top:10px;">
                                    <div class="col-md-12">
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Previous Class</label>
                                                <input type="text" class="form-control" name="old_class" id="old_class" placeholder="Previous Class">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Previous Institute</label>
                                                <input type="text" class="form-control" name="old_school" id="old_school" placeholder="Previous Institute">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Total Marks</label>
                                                <input type="text" class="form-control" name="old_tmarks" id="old_tmarks" placeholder="Total Marks">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Obtained Marks</label>
                                                <input type="text" class="form-control" name="old_obtmarks" id="old_obtmarks" placeholder="Obtained Marks">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Admission Form No</label>
                                                <input type="text" class="form-control" name="form_no" id="adm-no" placeholder="Form Number">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Reason for Previous School Leaving</label>
                                                <input type="text" class="form-control" name="school_leaving" id="school_leaving" placeholder="Reason for Previous School Leaving">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Home PTCL Number</label>
                                                <input type="text" class="form-control" name="home_number" id="home_number" placeholder="Home PTCL Number">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========== PANE 4: Contact Information ========== -->
                        <div class="wizard-pane" id="pane-contact-info">
                            <div id="contactInformationSection">
                                <div class="wizard-section-title"><span>Contact &amp; Address Information</span></div>
                                <div class="row" style="margin-top:10px;">
                                    <div class="col-md-12">
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Whatsapp No</label>
                                                <input type="text" class="form-control" name="whatsapp_number" id="whatsapp_number" placeholder="Whatsapp Number">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Father Cell No</label>
                                                <input type="text" class="form-control" name="father_cellno" id="father_cellno" placeholder="Father Cell No">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Mother Cell No</label>
                                                <input type="text" class="form-control" name="mother_cell" id="mother_cell" placeholder="Mother Cell Number">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Place Of Birth</label>
                                                <input type="text" class="form-control" name="place_of_birth" id="place_of_birth" placeholder="Place Of Birth">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Select State</label>
                                                <select name="state" id="state" class="form-control" onchange="getCity(this.value)">
                                                    <option value="">Select State</option>
                                                    <?php foreach (['Sindh','Punjab','Balochistan','KPK','Gilgit-Baltistan','Kashmir (territory)','FATA (territory)','Federal'] as $st): ?>
                                                        <option value="<?php echo e($st); ?>"><?php echo e($st); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>City</label>
                                                <input type="text" class="form-control" name="city" id="city" placeholder="City">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Email</label>
                                                <input type="text" class="form-control" name="email" id="email" placeholder="Email">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========== PANE 5: Documents ========== -->
                        <div class="wizard-pane" id="pane-documents">
                            <div class="wizard-section-title"><span>Student Documents</span></div>
                            <div class="doc-pane-intro">
                                <div class="doc-pane-intro-text">
                                    <i class="fa fa-info-circle"></i>
                                    <span>Upload the student's documents below. Accepted formats: JPG, JPEG, PNG, PDF.</span>
                                </div>
                                <a href="add_student_documents.php" target="_blank" class="doc-pane-manage-btn"><i class="fa fa-plus-circle"></i> Manage Document Titles</a>
                            </div>
                            <div class="doc-grid">
                                <?php foreach ($docTitles as $i => $dt): ?>
                                    <div class="doc-card" id="docCard_<?php echo $i; ?>">
                                        <div class="doc-card-thumb" id="docThumb_<?php echo $i; ?>"><i class="fa fa-file-text"></i></div>
                                        <div class="doc-card-title" title="<?php echo e($dt['name']); ?>"><?php echo e($dt['name']); ?></div>
                                        <span class="doc-card-status" id="docStatus_<?php echo $i; ?>">Not Uploaded</span><br>
                                        <label class="doc-card-upload" for="docFile_<?php echo $i; ?>"><i class="fa fa-upload"></i> Choose File</label>
                                        <input type="hidden" name="doc_types[]" value="<?php echo e($dt['name']); ?>">
                                        <input type="file" id="docFile_<?php echo $i; ?>" name="doc_files[]" accept=".jpg,.jpeg,.png,.pdf" onchange="previewStudentDoc(this, <?php echo $i; ?>)">
                                        <div class="doc-card-filename" id="docFileName_<?php echo $i; ?>"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Bottom Action Bar -->
                        <div class="wizard-actions-bar">
                            <div class="mandatory-note">* Marked fields are mandatory</div>
                            <div class="wizard-actions-buttons">
                                <button type="button" class="wizard-btn" id="btnCancel">Cancel</button>
                                <button type="button" class="wizard-btn wizard-btn-primary" id="btnSaveStudent">Save Student</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Camera Capture Modal -->
<div class="modal fade" id="cameraModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid #E5E7EB;">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-video-camera"></i> Capture Photo</h4>
            </div>
            <div class="modal-body" style="text-align:center;">
                <div style="position:relative; max-width:330px; margin:0 auto;">
                    <video id="cameraVideo" autoplay playsinline style="width:100%; border-radius:12px; background:#111;"></video>
                    <div style="position:absolute; top:0; left:0; right:0; bottom:0; margin:auto; width:150px; height:150px; border:3px solid #FF7A1B; border-radius:50%; pointer-events:none;"></div>
                </div>
                <canvas id="cameraCanvas" style="display:none; width:100%; border-radius:12px;"></canvas>
                <div id="cameraMessage" style="display:none; margin-top:10px; color:#dc3545; font-weight:600;"></div>
                <img id="cameraPreview" src="" alt="Captured" style="display:none; max-width:200px; border-radius:12px; border:2px solid #FF7A1B; margin:10px auto;">
            </div>
            <div class="modal-footer" style="border-top:1px solid #E5E7EB;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info" id="btnUseCapture" style="display:none;"><i class="fa fa-check"></i> Use This Photo</button>
                <button type="button" class="btn btn-warning" id="btnCapture"><i class="fa fa-camera"></i> Capture</button>
            </div>
        </div>
    </div>
</div>

<script>
// Icon tab navigation
document.querySelectorAll('.icon-tab-item').forEach(function (tab) {
    tab.addEventListener('click', function () {
        var target = this.getAttribute('data-tab');
        document.querySelectorAll('.icon-tab-item').forEach(function (t) { t.classList.remove('active'); });
        this.classList.add('active');
        document.querySelectorAll('.wizard-pane').forEach(function (p) { p.classList.remove('active'); });
        document.getElementById('pane-' + target).classList.add('active');
    });
});

// Section dropdown
function getSection(cid) {
    var sel = document.getElementById('txt_section');
    sel.innerHTML = '<option value="">Loading...</option>';
    eduGet(HIIFI_BASE + 'ajax_get_sections.php?class_id=' + encodeURIComponent(cid), function (data) {
        sel.innerHTML = '<option value="">Select Section</option>';
        (data || []).forEach(function (s) {
            var o = document.createElement('option');
            o.value = s.section_id; o.textContent = s.section_name;
            sel.appendChild(o);
        });
    });
}

// Select2 for family
if (window.jQuery && jQuery.fn.select2) {
    jQuery(function () {
        jQuery('#family_search').select2({ width: '100%', placeholder: 'Select Family', allowClear: true });
    });
}

// Wizard save/cancel
(function () {
    var form = document.getElementById('studentForm');
    var redirectModeInput = document.getElementById('redirect_mode');
    var stepWizardItems = document.querySelectorAll('.step-wizard-item');

    function activateTab(tabName) {
        var paneOrder = ['basic-info', 'parent-details', 'academic-info', 'contact-info', 'documents'];
        var idx = paneOrder.indexOf(tabName);
        if (idx === -1) return;
        document.querySelectorAll('.icon-tab-item').forEach(function (t) { t.classList.toggle('active', t.dataset.tab === tabName); });
        document.querySelectorAll('.wizard-pane').forEach(function (p) { p.classList.toggle('active', p.id === 'pane-' + tabName); });
        stepWizardItems.forEach(function (s) { s.classList.toggle('active', s.dataset.step === (idx === 0 ? 'student-info' : 'student-info')); });
        var tabsTop = document.getElementById('studentWizardTabs');
        if (tabsTop) window.scrollTo({ top: tabsTop.offsetTop - 80, behavior: 'smooth' });
    }

    document.getElementById('btnCancel').addEventListener('click', function () {
        window.location.href = HIIFI_BASE + 'manage_students.php';
    });

    function blockOnFirstInvalid(container) {
        var invalid = container.querySelector(':invalid');
        if (invalid) {
            var pane = invalid.closest('.wizard-pane');
            if (pane) activateTab(pane.id.replace('pane-', ''));
            invalid.reportValidity();
            return true;
        }
        return false;
    }

    function submitWithMode(mode) {
        if (blockOnFirstInvalid(form)) return;
        redirectModeInput.value = mode;
        if (form.requestSubmit) form.requestSubmit(); else form.submit();
    }

    document.getElementById('btnSaveStudent').addEventListener('click', function () { submitWithMode('profile'); });
})();

// Documents preview
function previewStudentDoc(input, index) {
    var card = document.getElementById('docCard_' + index);
    var thumb = document.getElementById('docThumb_' + index);
    var status = document.getElementById('docStatus_' + index);
    var fileName = document.getElementById('docFileName_' + index);
    var file = input.files && input.files[0];
    if (!file) return;
    fileName.textContent = file.name;
    status.textContent = 'Uploaded';
    card.classList.add('has-file');
    if (file.type === 'application/pdf') {
        thumb.innerHTML = '<i class="fa fa-file-pdf-o"></i>';
    } else {
        var reader = new FileReader();
        reader.onload = function (e) { thumb.innerHTML = '<img src="' + e.target.result + '" alt="' + file.name + '">'; };
        reader.readAsDataURL(file);
    }
}

// Lightweight date picker (dd/MM/yyyy)
(function () {
    function formatDate(d) {
        var dd = String(d.getDate()).padStart(2, '0');
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        return dd + '/' + mm + '/' + d.getFullYear();
    }
    document.querySelectorAll('.datepicker').forEach(function (input) {
        input.setAttribute('readonly', 'readonly');
        input.style.cursor = 'pointer';
        input.addEventListener('click', function () {
            var d = new Date();
            if (input.value && input.value.indexOf('/') !== -1) {
                var p = input.value.split('/');
                var parsed = new Date(parseInt(p[2]), parseInt(p[1]) - 1, parseInt(p[0]));
                if (!isNaN(parsed.getTime())) d = parsed;
            }
            // Use native date input (hidden) for reliable picking
            var hidden = document.createElement('input');
            hidden.type = 'date';
            hidden.value = d.toISOString().substring(0, 10);
            hidden.style.cssText = 'position:absolute; opacity:0; width:1px; height:1px;';
            document.body.appendChild(hidden);
            hidden.focus();
            hidden.addEventListener('change', function () {
                if (hidden.value) {
                    var parts = hidden.value.split('-');
                    input.value = parts[2] + '/' + parts[1] + '/' + parts[0];
                }
                hidden.remove();
            });
            hidden.addEventListener('blur', function () { setTimeout(function () { hidden.remove(); }, 200); });
            setTimeout(function () { hidden.click(); }, 50);
        });
    });
})();

// Family auto-fill via select2
jQuery(document).ready(function () {
    jQuery('#family_search').on('change', function () {
        var code = this.value;
        document.getElementById('family_code_value').value = code || '';
        if (!code) return;
        eduGet(HIIFI_BASE + 'ajax_get_family_by_code.php?code=' + encodeURIComponent(code), function (data) {
            if (!data) return;
            var map = {
                'last_name': data.father_name,
                'cnic': data.father_cnic,
                'father_qualification': data.father_qualification,
                'father_occupation': data.father_occupation,
                'Fbusiness_address': data.father_business_address,
                'father_income': data.father_income,
                'mother_name': data.mother_name,
                'mother_cnic': data.mother_cnic,
                'mother_qualification': data.mother_qualification,
                'mother_activity': data.mother_activity,
                'mother_designation': data.mother_designation,
                'address': data.address,
                'gardian_name': data.guardian_name,
                'gardian_cnic': data.guardian_cnic,
                'gardian_no': data.guardian_cellno,
                'gardian_qualification': data.guardian_qualification,
                'gardian_occupation': data.guardian_occupation,
                'gardian_income': data.guardian_income,
                'gardian_email': data.guardian_email,
                'gardian_address': data.guardian_address,
                'locality': data.locality_id
            };
            Object.keys(map).forEach(function (id) {
                var el = document.getElementById(id);
                if (el && map[id] !== null && map[id] !== undefined) {
                    if (el.tagName === 'SELECT') {
                        if (el.querySelector('option[value="' + map[id] + '"]')) el.value = map[id];
                    } else {
                        el.value = map[id];
                    }
                }
            });
            var loc = document.getElementById('locality');
            if (data.locality_id && loc) loc.value = data.locality_id;
        });
    });
});
// ---- Photo DP preview, zoom/rotate & camera capture ----
(function () {
    var fileInput   = document.getElementById('fileInput');
    var image       = document.getElementById('image');
    var sampleImage = document.getElementById('sample-image');
    var placeholder = document.getElementById('photoPlaceholder');
    var zoom        = document.getElementById('zoom-slider');
    var rotate      = document.getElementById('rotate-slider');
    var capturedInp = document.getElementById('captured_image');
    var camModal    = document.getElementById('cameraModal');

    function hidePlaceholder() { if (placeholder) placeholder.style.display = 'none'; }

    function updateTransform() {
        var z = zoom && zoom.value ? parseFloat(zoom.value) : 1;
        var r = rotate && rotate.value ? parseInt(rotate.value, 10) : 0;
        if (image.style.display !== 'none') image.style.transform = 'scale(' + z + ') rotate(' + r + 'deg)';
    }

    function showImage(src) {
        if (placeholder) placeholder.style.display = 'none';
        sampleImage.style.display = 'none';
        image.style.display = 'block';
        image.src = src;
        image.style.left = '0px';
        image.style.top = '0px';
        image.style.width = '100%';
        image.style.height = '100%';
        updateTransform();
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var f = this.files && this.files[0];
            var lbl = document.getElementById('fileNameLabel');
            if (!f) { if (lbl) lbl.textContent = 'No file selected'; return; }
            if (lbl) lbl.textContent = f.name;
            var reader = new FileReader();
            reader.onload = function (e) {
                capturedInp.value = '';  // file upload overrides camera capture
                if (zoom) zoom.value = 1;
                if (rotate) rotate.value = 0;
                showImage(e.target.result);
            };
            reader.readAsDataURL(f);
        });
    }

    if (zoom) zoom.addEventListener('input', updateTransform);
    if (rotate) rotate.addEventListener('input', updateTransform);

    // Drag to position the uploaded image inside the frame
    if (image) {
        var isDragging = false, startX = 0, startY = 0;
        image.addEventListener('mousedown', function (e) {
            isDragging = true;
            startX = e.clientX - (parseInt(image.style.left, 10) || 0);
            startY = e.clientY - (parseInt(image.style.top, 10) || 0);
            image.classList.add('dragging');
            e.preventDefault();
        });
        document.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            image.style.left = (e.clientX - startX) + 'px';
            image.style.top = (e.clientY - startY) + 'px';
        });
        document.addEventListener('mouseup', function () {
            isDragging = false;
            image.classList.remove('dragging');
        });
    }

    // Form submit interceptor: bake zoom/rotate/drag into the saved image via canvas
    var isProcessing = false;
    var form = document.getElementById('studentForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (isProcessing) return true;
            if (image.style.display !== 'block') { return true; }
            var hasNewFile = fileInput && fileInput.files && fileInput.files[0];
            var z = zoom ? parseFloat(zoom.value) : 1;
            var r = rotate ? parseInt(rotate.value, 10) : 0;
            var l = parseInt(image.style.left, 10) || 0;
            var t = parseInt(image.style.top, 10) || 0;
            var hasTransform = z !== 1 || r !== 0 || l !== 0 || t !== 0;
            if (!hasNewFile && !hasTransform) return true;
            if (!hasNewFile) {
                // Webcam capture path (captured_image) already carries final pixels; no transform bake
                return true;
            }
            e.preventDefault();
            processImageTransformation();
        });

        function processImageTransformation() {
            var c = document.getElementById('imageCanvas');
            if (!c) return;
            if (!image.complete || !image.naturalWidth) {
                isProcessing = false;
                form.submit();
                return;
            }
            var z = zoom ? (parseFloat(zoom.value) || 1) : 1;
            var r = rotate ? (parseInt(rotate.value, 10) || 0) : 0;
            var imgLeft = parseInt(image.style.left, 10) || 0;
            var imgTop = parseInt(image.style.top, 10) || 0;
            var cw = 125, ch = 140, scale = 2;
            c.width = cw * scale; c.height = ch * scale;
            var ctx = c.getContext('2d');
            ctx.scale(scale, scale);
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, cw, ch);
            ctx.save();
            ctx.translate(cw / 2, ch / 2);
            if (r !== 0) ctx.rotate((r * Math.PI) / 180);
            var iar = image.naturalWidth / image.naturalHeight;
            var car = cw / ch;
            var bw, bh;
            if (iar > car) { bw = cw; bh = cw / iar; } else { bh = ch; bw = ch * iar; }
            var dw = bw * z, dh = bh * z;
            ctx.drawImage(image, -dw / 2 + imgLeft, -dh / 2 + imgTop, dw, dh);
            ctx.restore();
            c.toBlob(function (blob) {
                if (!blob) { isProcessing = false; form.submit(); return; }
                var tf = new File([blob], 'transformed_student_image.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                try {
                    var dt = new DataTransfer();
                    dt.items.add(tf);
                    fileInput.files = dt.files;
                    form.submit();
                } catch (err) {
                    isProcessing = false;
                    form.submit();
                }
            }, 'image/jpeg', 0.98);
        }
    }

    var video = document.getElementById('cameraVideo');
    var canvas = document.getElementById('cameraCanvas');
    var ctx = canvas ? canvas.getContext('2d') : null;

    function stopCamera() {
        if (video && video.srcObject) {
            var tracks = video.srcObject.getTracks();
            if (tracks) tracks.forEach(function (t) { t.stop(); });
            video.srcObject = null;
        }
    }

    if (camModal && video) {
        camModal.addEventListener('shown.bs.modal', function () {
            var prev = document.getElementById('cameraPreview');
            if (prev) { prev.src = ''; prev.style.display = 'none'; }
            var useBtn = document.getElementById('btnUseCapture');
            if (useBtn) useBtn.style.display = 'none';
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(function (stream) {
                        video.srcObject = stream;
                        video.play();
                        if (document.getElementById('cameraMessage')) document.getElementById('cameraMessage').style.display = 'none';
                    })
                    .catch(function () {
                        if (document.getElementById('cameraMessage')) {
                            var m = document.getElementById('cameraMessage');
                            m.textContent = 'Camera not available. Please upload a photo instead.';
                            m.style.display = 'block';
                        }
                    });
            }
        });
        camModal.addEventListener('hidden.bs.modal', stopCamera);
    }

    var btnCapture = document.getElementById('btnCapture');
    if (btnCapture && canvas && ctx && video) {
        btnCapture.addEventListener('click', function () {
            canvas.width = video.videoWidth || 320;
            canvas.height = video.videoHeight || 240;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            var preview = document.getElementById('cameraPreview');
            if (preview) {
                preview.src = canvas.toDataURL('image/jpeg');
                preview.style.display = 'block';
            }
            var useBtn = document.getElementById('btnUseCapture');
            if (useBtn) useBtn.style.display = 'inline-block';
        });
    }

    var btnUse = document.getElementById('btnUseCapture');
    if (btnUse) {
        btnUse.addEventListener('click', function () {
            var preview = document.getElementById('cameraPreview');
            var src = preview ? preview.src : null;
            if (src) {
                capturedInp.value = src;   // saved via POST hidden input
                showImage(src);
                stopCamera();
                if (typeof jQuery !== 'undefined') jQuery(camModal).modal('hide');
            }
        });
    }
})();

</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
