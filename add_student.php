<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

// Ensure the documents table exists
db_query("CREATE TABLE IF NOT EXISTS student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    doc_type VARCHAR(100),
    file_path VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$page_title = 'Add New Student';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

$localities = [];
$lr = db_query("SELECT locality_id, locality_name FROM localities WHERE status=1");
while ($row = $lr->fetch_assoc()) { $localities[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'AddAdmission') {
    // Parse dates (format dd/Mmm/yyyy or dd/mm/yyyy)
    function parse_date($d) {
        $d = trim($d);
        if ($d === '') return null;
        $ts = strtotime(str_replace('/', '-', $d));
        return $ts ? date('Y-m-d', $ts) : null;
    }

    function val($key) {
        return isset($_POST[$key]) ? trim($_POST[$key]) : '';
    }
    function valNull($key) {
        $v = val($key);
        return $v === '' ? null : $v;
    }

    $first_name     = val('first_name');
    $father_name    = val('lname');
    $mother_name    = val('mother_name');
    $email          = valNull('email');
    $cellno         = val('cellno');
    $class_id       = (int) val('class');
    $section_id     = (int) val('section') ?: null;
    $dob            = val('dob');
    $date_of_adms   = val('date_of_adms');
    $gender         = val('gender') ?: 'male';
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

    // Photo upload (prefer uploaded file, else use captured webcam data URL)
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
                $gr = substr(date('Y'), 2) . '-' . str_pad($studentId, 3, '0', STR_PAD_LEFT);
                $u = db_prepare('UPDATE students SET gr_no = ? WHERE student_id = ?');
                $u->bind_param('si', $gr, $studentId);
                $u->execute();

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
            }
            $message = 'Student added successfully! GR No: ' . ($studentId > 0 ? $gr : '');
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
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="container mt-4 page-card" style="width:100%;">
            <div class="top-tabs-row">
                <ul class="nav nav-tabs" id="studentTabs" role="tablist">
                    <li class="active"><a href="#add-single" data-toggle="tab"><i class="fa fa-user-plus"></i> Add New Student</a></li>
                    <li><a href="#multi"><i class="fa fa-users"></i>&nbsp; Add Multi Students</a></li>
                    <li><a href="#import"><i class="fa fa-upload"></i> &nbsp; Import Students with CSV</a></li>
                </ul>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success" style="margin-top:12px;"><?php echo e($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-top:12px;"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="tab-content" id="studentTabsContent" style="margin-top:14px;">
                <div class="tab-pane active" id="add-single">
                    <ul class="icon-tabs" id="studentWizardTabs">
                        <li class="icon-tab-item active" data-tab="basic-info"><i class="fa fa-id-card"></i> Basic Information</li>
                        <li class="icon-tab-item" data-tab="parent-details"><i class="fa fa-user-friends"></i> Parent Details</li>
                        <li class="icon-tab-item" data-tab="guardian-details"><i class="fa fa-user-shield"></i> Guardian Details</li>
                        <li class="icon-tab-item" data-tab="academic-info"><i class="fa fa-graduation-cap"></i> Previous Education</li>
                        <li class="icon-tab-item" data-tab="contact-info"><i class="fa fa-phone-alt"></i> Contact Information</li>
                        <li class="icon-tab-item" data-tab="documents"><i class="fa fa-file-alt"></i> Documents</li>
                    </ul>

                    <form id="studentForm" action="add_student.php" method="post" enctype="multipart/form-data" class="form-horizontal form-label-left">
                        <input type="hidden" name="action" value="AddAdmission">
                        <input type="hidden" name="captured_image" id="captured_image" value="">

                        <div class="wizard-pane active" id="pane-basic-info">
                            <div class="pane-wrap">
                                <div class="pane-title"><i class="fa fa-id-card"></i> Basic Information</div>
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <button type="button" class="btn btn-info btn-sm" id="btnSelectFamily"><i class="fa fa-users"></i> Select Family</button>
                                    <small style="margin-left:8px; color:#6B7280;">Pick an existing family to auto-fill guardian &amp; parent details.</small>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label for="fname">Student Name <span style="color:red;">*</span></label>
                                        <input type="text" value="" class="form-control" name="first_name" required="" id="fname" placeholder="Student Name" maxlength="35">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="last_name">Father Name</label>
                                        <input type="text" value="" class="form-control" name="lname" id="last_name" placeholder="Father Name" maxlength="35">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="cell_no">Cell Number <span style="color:red;">*</span></label>
                                        <input type="text" value="" class="form-control" name="cellno" required="" id="cell_no" placeholder="Number / Reporting SMS">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Gender</label>
                                        <select id="gender" name="gender" class="form-control" required="">
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label>Religion</label>
                                        <select id="religion" name="religion" class="form-control" required="">
                                            <option value="Islam">Islam</option>
                                            <option value="Christianity">Christianity</option>
                                            <option value="Hinduism">Hinduism</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Date of Birth</label>
                                        <input type="text" class="form-control" name="dob" id="dob" placeholder="dd/mm/yyyy" value="<?php echo date('d/M/Y'); ?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Date of Admission</label>
                                        <input type="text" class="form-control" name="date_of_adms" id="date_of_adms" placeholder="dd/mm/yyyy" value="<?php echo date('d/M/Y'); ?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Session</label>
                                        <input type="text" class="form-control" name="session" id="session" placeholder="e.g. <?php echo date('Y') . '-' . substr(date('Y') + 1, 2); ?>" value="<?php echo date('Y') . '-' . substr(date('Y') + 1, 2); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label>Board/Council</label>
                                        <input type="text" class="form-control" name="board_council" id="board_council" placeholder="e.g. Karachi Board">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Group/Shift</label>
                                        <input type="text" class="form-control" name="group_shift" id="group_shift" placeholder="e.g. Morning">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Admission Source</label>
                                        <input type="text" class="form-control" name="adm_source" id="adm_source" placeholder="e.g. Walk-in / Referral">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>GR-No</label>
                                        <input type="text" class="form-control" value="Auto" readonly="" style="background:#f5f6fa; color:#9CA3AF;">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label>Photo</label>
                                        <input type="file" class="form-control" name="img_file" id="fileInput" accept="image/*">
                                        <button type="button" class="btn btn-warning btn-sm" id="btnCapturePhoto" style="margin-top:6px; width:100%;"><i class="fa fa-camera"></i> Capture Photo</button>
                                        <img id="photoPreview" src="" alt="Captured" style="display:none; margin-top:6px; width:100%; border-radius:8px; border:1px solid #E5E7EB;">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Locality</label>
                                        <select name="Locality" id="locality" class="form-control">
                                            <option value="">Select Locality</option>
                                            <?php foreach ($localities as $l): ?>
                                                <option value="<?php echo $l['locality_id']; ?>"><?php echo e($l['locality_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>State</label>
                                        <input type="text" class="form-control" name="state" id="state" placeholder="e.g. Sindh">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>City</label>
                                        <input type="text" class="form-control" name="city" id="city" placeholder="e.g. Karachi">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-pane" id="pane-parent-details">
                            <div class="pane-wrap">
                                <div class="pane-title"><i class="fa fa-user-friends"></i> Parent Details</div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Father CNIC</label>
                                        <input type="text" value="" class="form-control" name="cnic" id="cnic" placeholder="CNIC">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Father Qualification</label>
                                        <input type="text" value="" class="form-control" name="Fqualification" id="father_qualification" placeholder="Father Qualification">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Father Occupation</label>
                                        <select name="father_occupation" id="father_occupation" class="form-control">
                                            <option value="">Select Occupation</option>
                                            <option value="Business">Business</option>
                                            <option value="Government Service">Government Service</option>
                                            <option value="Private Job">Private Job</option>
                                            <option value="Farmer">Farmer</option>
                                            <option value="Labour">Labour</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Father Business Address</label>
                                        <input type="text" value="" class="form-control" name="Fbusiness_address" id="Fbusiness_address" placeholder="Father Business Address">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Father Income</label>
                                        <input type="text" value="" class="form-control" name="Fincome" id="father_income" placeholder="Father Income">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Father Cell No</label>
                                        <input type="text" value="" class="form-control" name="father_cellno" id="father_cellno" placeholder="Father Cell No">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Mother Name</label>
                                        <input type="text" value="" class="form-control" name="mother_name" id="mother_name" placeholder="Mother Name">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Mother CNIC</label>
                                        <input type="text" value="" class="form-control" name="mother_cnic" id="mother_cnic" placeholder="Mother CNIC">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Mother Qualification</label>
                                        <input type="text" value="" class="form-control" name="mother_qualification" id="mother_qualification" placeholder="Mother Qualification">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Mother Activity</label>
                                        <select id="mother_activity" name="mother_activity" class="form-control">
                                            <option value="House Wife">House Wife</option>
                                            <option value="Working">Working</option>
                                            <option value="Business">Business</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Mother Designation</label>
                                        <input type="text" value="" class="form-control" name="mother_designation" id="mother_designation" placeholder="Mother Designation">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Mother Cell Number</label>
                                        <input type="text" value="" class="form-control" name="mother_cell" id="mother_cell" placeholder="Mother Cell Number">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Home Address</label>
                                        <input type="text" value="" class="form-control" name="address" id="address" placeholder="Family Home Address">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Cast</label>
                                        <input type="text" value="" class="form-control" name="cast" id="cast" placeholder="Cast">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-pane" id="pane-guardian-details">
                            <div class="pane-wrap">
                                <div class="pane-title"><i class="fa fa-user-shield"></i> Guardian Details</div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Guardian Name</label>
                                        <input type="text" value="" class="form-control" name="gname" id="gardian_name" placeholder="Guardian Name">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Guardian CNIC</label>
                                        <input type="text" value="" class="form-control" name="Gcnic" id="gardian_cnic" placeholder="Guardian CNIC">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Guardian Cell No</label>
                                        <input type="text" value="" class="form-control" name="Gcellno" id="gardian_no" placeholder="Guardian Cell No">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Guardian Qualification</label>
                                        <input type="text" value="" class="form-control" name="Gqualification" id="gardian_qualification" placeholder="Guardian Qualification">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Guardian Occupation</label>
                                        <input type="text" value="" class="form-control" name="Goccupation" id="gardian_occupation" placeholder="Guardian Occupation">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Guardian Income</label>
                                        <input type="text" value="" class="form-control" name="Gincome" id="gardian_income" placeholder="Guardian Income">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Guardian Email</label>
                                        <input type="text" value="" class="form-control" name="gardian_email" id="gardian_email" placeholder="Guardian Email">
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label>Guardian Address</label>
                                        <input type="text" value="" class="form-control" name="Gaddress" id="gardian_address" placeholder="Guardian Address">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-pane" id="pane-academic-info">
                            <div class="pane-wrap">
                                <div class="pane-title"><i class="fa fa-graduation-cap"></i> Academic Information</div>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label>Class <span style="color:red;">*</span></label>
                                        <select name="class" required="" id="class" class="form-control">
                                            <option value="">Select Class</option>
                                            <?php foreach ($classes as $c): ?>
                                                <option value="<?php echo $c['class_id']; ?>"><?php echo e($c['class_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Section</label>
                                        <select name="section" id="txt_section" class="form-control">
                                            <option value="">Select Section</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Admission Form No</label>
                                        <input type="text" value="" class="form-control" name="form_no" id="adm-no" placeholder="Form Number">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>B-Form No</label>
                                        <input type="text" value="" class="form-control" name="formBNo" id="formBNo" placeholder="Form-B No">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label>Previous Class</label>
                                        <input type="text" value="" class="form-control" name="old_class" id="old_class" placeholder="Previous Class">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Previous Institute</label>
                                        <input type="text" value="" class="form-control" name="old_school" id="old_school" placeholder="Previous Institute">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Total Marks</label>
                                        <input type="text" value="" class="form-control" name="old_tmarks" id="old_tmarks" placeholder="Total Marks">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Obtained Marks</label>
                                        <input type="text" value="" class="form-control" name="old_obtmarks" id="old_obtmarks" placeholder="Obtained Marks">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Reason for Previous School Leaving</label>
                                        <input type="text" value="" class="form-control" name="school_leaving" id="school_leaving" placeholder="Reason for Previous School Leaving">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Place Of Birth</label>
                                        <input type="text" value="" class="form-control" name="place_of_birth" id="place_of_birth" placeholder="Place Of Birth">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Home PTCL Number</label>
                                        <input type="text" value="" class="form-control" name="home_number" id="home_number" placeholder="Home Phone">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-pane" id="pane-contact-info">
                            <div class="pane-wrap">
                                <div class="pane-title"><i class="fa fa-phone-alt"></i> Contact Information</div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Email</label>
                                        <input type="text" value="" class="form-control" name="email" id="email" placeholder="Email">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Whatsapp Number</label>
                                        <input type="text" value="" class="form-control" name="whatsapp_number" id="whatsapp_number" placeholder="Whatsapp Number">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-pane" id="pane-documents">
                            <div class="pane-wrap">
                                <div class="pane-title"><i class="fa fa-file-alt"></i> Documents</div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>B-Form / CNIC / Photo</label>
                                        <input type="hidden" name="doc_types[]" value="B-Form / CNIC / Photo">
                                        <input type="file" id="docFile_0" name="doc_files[]" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Previous School Certificate</label>
                                        <input type="hidden" name="doc_types[]" value="Previous School Certificate">
                                        <input type="file" id="docFile_1" name="doc_files[]" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Fee Challan / DMC</label>
                                        <input type="hidden" name="doc_types[]" value="Fee Challan / DMC">
                                        <input type="file" id="docFile_2" name="doc_files[]" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="text-align: center; margin-top: 18px;">
                            <button type="submit" class="btn btn-success" name="submit" style="padding: 10px 34px; font-size: 14px;"><i class="fa fa-check"></i> Submit</button>
                        </div>
                    </form>

                    <!-- Camera Capture Modal -->
                    <div class="modal fade" id="cameraModal" tabindex="-1" role="dialog" aria-labelledby="cameraModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="cameraModalLabel"><i class="fa fa-video-camera"></i> Capture Photo</h4>
                                </div>
                                <div class="modal-body" style="text-align:center;">
                                    <div style="position:relative; max-width:320px; margin:0 auto;">
                                        <video id="cameraVideo" autoplay playsinline style="width:100%; border-radius:12px; background:#111;"></video>
                                        <div style="position:absolute; top:0; left:0; right:0; bottom:0; margin:auto; width:150px; height:150px; border:3px solid #FF7A1B; border-radius:50%; pointer-events:none;"></div>
                                    </div>
                                    <canvas id="cameraCanvas" style="display:none; width:100%; border-radius:12px;"></canvas>
                                    <div id="cameraMessage" style="display:none; margin-top:10px; color:#dc3545; font-weight:600;"></div>
                                    <img id="cameraPreview" src="" alt="Captured" style="display:none; max-width:200px; border-radius:12px; border:2px solid #FF7A1B; margin:10px auto;">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-info" id="btnUseCapture" style="display:none;"><i class="fa fa-check"></i> Use This Photo</button>
                                    <button type="button" class="btn btn-warning" id="btnCapture"><i class="fa fa-camera"></i> Capture</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Select Family Modal -->
                    <div class="modal fade" id="familyModal" tabindex="-1" role="dialog" aria-labelledby="familyModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="familyModalLabel"><i class="fa fa-users"></i> Select Family</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="input-group" style="margin-bottom:10px;">
                                        <input type="text" id="familySearchInput" class="form-control" placeholder="Search by family name, father, cell, CNIC or address...">
                                        <span class="input-group-btn"><button class="btn btn-default" type="button" id="familySearchBtn"><i class="fa fa-search"></i></button></span>
                                    </div>
                                    <div id="familyResults">
                                        <p style="color:#6B7280;">Start typing to find an existing family.</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="multi">
                    <div class="pane-wrap" style="margin-top:16px;">
                        <div class="pane-title"><i class="fa fa-users"></i> Add Multi Students</div>
                        <p style="color:#6B7280;">Multi student entry coming soon. Use Add New Student tab below.</p>
                    </div>
                </div>

                <div class="tab-pane" id="import">
                    <div class="pane-wrap" style="margin-top:16px;">
                        <div class="pane-title"><i class="fa fa-upload"></i> Import Students with CSV</div>
                        <p style="color:#6B7280;">CSV import coming soon. Use Add New Student tab below.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.icon-tab-item').forEach(function(tab){
    tab.addEventListener('click', function(){
        var target = this.getAttribute('data-tab');
        document.querySelectorAll('.icon-tab-item').forEach(function(t){ t.classList.remove('active'); });
        this.classList.add('active');
        document.querySelectorAll('.wizard-pane').forEach(function(p){ p.classList.remove('active'); });
        document.getElementById('pane-' + target).classList.add('active');
    });
});

document.getElementById('class').addEventListener('change', function(){
    var cid = this.value;
    var sel = document.getElementById('txt_section');
    sel.innerHTML = '<option value="">Loading...</option>';
    fetch('ajax_get_sections.php?class_id=' + cid)
        .then(function(r){ return r.json(); })
        .then(function(data){
            sel.innerHTML = '<option value="">Select Section</option>';
            data.forEach(function(s){
                var o = document.createElement('option');
                o.value = s.section_id; o.textContent = s.section_name;
                sel.appendChild(o);
            });
        });
});

/* ===== Live Photo Capture (Webcam) ===== */
(function(){
    var stream = null;
    var video = document.getElementById('cameraVideo');
    var canvas = document.getElementById('cameraCanvas');
    var preview = document.getElementById('cameraPreview');
    var msg = document.getElementById('cameraMessage');
    var captureBtn = document.getElementById('btnCapture');
    var useBtn = document.getElementById('btnUseCapture');
    var captured = document.getElementById('captured_image');

    function stopStream() {
        if (stream) { stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; }
    }

    function showMsg(text) {
        msg.textContent = text;
        msg.style.display = 'block';
    }
    function hideMsg() { msg.style.display = 'none'; }

    function startCamera() {
        stopStream();
        video.style.display = 'block';
        hideMsg();
        captureBtn.style.display = 'inline-block';
        useBtn.style.display = 'none';
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({video: true})
                .then(function(s){
                    stream = s;
                    video.srcObject = s;
                    video.play();
                })
                .catch(function(err){
                    console.error('Camera error:', err);
                    showMsg('Camera unavailable or blocked. Please allow camera access or use the Upload Photo option instead.');
                    captureBtn.style.display = 'none';
                });
        } else {
            showMsg('Live camera is not supported in this browser. Please use the Upload Photo option instead.');
            captureBtn.style.display = 'none';
        }
    }

    captureBtn.addEventListener('click', function(){
        if (!video.videoWidth) { showMsg('Camera is not ready yet. Please wait...'); return; }
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        var dataURL = canvas.toDataURL('image/jpeg', 0.92);
        canvas.style.display = 'block';
        video.style.display = 'none';
        preview.src = dataURL;
        preview.style.display = 'block';
        captureBtn.style.display = 'none';
        useBtn.style.display = 'inline-block';
        captured.value = dataURL;
        stopStream();
    });

    useBtn.addEventListener('click', function(){
        var thumb = document.getElementById('photoPreview');
        if (captured.value) { thumb.src = captured.value; thumb.style.display = 'block'; }
        $('#cameraModal').modal('hide');
        if (document.getElementById('fileInput')) { document.getElementById('fileInput').value = ''; }
    });

    $('#cameraModal').on('hidden.bs.modal', function(){ stopStream(); });
    document.getElementById('btnCapturePhoto').addEventListener('click', function(){ startCamera(); $('#cameraModal').modal('show'); });

    /* File upload still shows preview so it can be used instead */
    document.getElementById('fileInput').addEventListener('change', function(){
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e){
                var thumb = document.getElementById('photoPreview');
                thumb.src = e.target.result;
                thumb.style.display = 'block';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
})();

/* ===== Select Family ===== */
(function(){
    var modal = $('#familyModal');
    var input = document.getElementById('familySearchInput');
    var results = document.getElementById('familyResults');
    var timer = null;

    function search() {
        var q = input.value.trim();
        if (!q) {
            results.innerHTML = '<p style="color:#6B7280;">Type to search for an existing family.</p>';
            return;
        }
        fetch('ajax_get_families.php?q=' + encodeURIComponent(q))
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (!data || !data.length) {
                    results.innerHTML = '<p style="color:#6B7280;">No family found matching "' + escapeHtml(q) + '".</p>';
                    return;
                }
                results.innerHTML = '';
                data.forEach(function(f){
                    var row = document.createElement('div');
                    row.className = 'family-result-item';
                    row.style.cssText = 'padding:9px 10px; border:1px solid #E5E7EB; border-radius:8px; margin-bottom:6px; cursor:pointer; transition:background .15s; font-size:13px;';
                    row.innerHTML = '<strong>' + escapeHtml(f.family_name) + '</strong> <small style="color:#6B7280;">(Father: ' + escapeHtml(f.father_name || '-') + ' | Cell: ' + escapeHtml(f.gcellno || '-') + ')</small>';
                    row.addEventListener('mouseenter', function(){ this.style.background = '#FFF3E6'; });
                    row.addEventListener('mouseleave', function(){ this.style.background = '#fff'; });
                    row.addEventListener('click', (function(family){ return function(){
                        fillForm(family);
                        modal.modal('hide');
                    }; })(f));
                    results.appendChild(row);
                });
            })
            .catch(function(){ results.innerHTML = '<p style="color:#dc3545;">Failed to load families. Please try again.</p>'; });
    }

    function fillForm(f) {
        var map = {
            'gname': f.guardian_name,
            'Gcnic': f.guardian_cnic,
            'Gcellno': f.guardian_cellno,
            'Gqualification': f.guardian_qualification,
            'Goccupation': f.guardian_occupation,
            'Gincome': f.guardian_income,
            'gardian_email': f.guardian_email,
            'Gaddress': f.guardian_address,
            'father_occupation': f.father_occupation,
            'cnic': f.cnic,
            'address': f.address
        };
        Object.keys(map).forEach(function(name){
            var el = document.querySelector('#studentForm [name="' + name + '"]');
            if (el && map[name] !== null && map[name] !== undefined) { el.value = map[name]; }
        });
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
        });
    }

    document.getElementById('btnSelectFamily').addEventListener('click', function(){
        input.value = '';
        results.innerHTML = '<p style="color:#6B7280;">Type to search for an existing family.</p>';
        modal.modal('show');
    });
    input.addEventListener('input', function(){ clearTimeout(timer); timer = setTimeout(search, 350); });
    document.getElementById('familySearchBtn').addEventListener('click', search);
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>