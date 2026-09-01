<?php
define('HIIFI', true);
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Add New Student';

$message = '';
$error = '';

$classes = [];
$res = db_query("SELECT class_id, class_name FROM classes WHERE status=1 ORDER BY class_name");
while ($row = $res->fetch_assoc()) { $classes[] = $row; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'AddAdmission') {
    $first_name  = trim($_POST['first_name'] ?? '');
    $father_name = trim($_POST['lname'] ?? '');
    $cellno      = trim($_POST['cellno'] ?? '');
    $class_id    = (int) ($_POST['class'] ?? 0);
    $section_id  = (int) ($_POST['section'] ?? 0);
    $gender      = $_POST['gender'] ?? 'male';
    $religion    = $_POST['religion'] ?? 'Islam';
    $dob         = trim($_POST['dob'] ?? '');
    $admission_date = trim($_POST['date_of_adms'] ?? date('d/M/Y'));
    $email       = trim($_POST['email'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');

    // Parse dates (format dd/Mmm/yyyy or dd/mm/yyyy)
    function parse_date($d) {
        $d = trim($d);
        if ($d === '') return null;
        $ts = strtotime(str_replace('/', '-', $d));
        return $ts ? date('Y-m-d', $ts) : null;
    }

    if ($first_name === '' || $class_id === 0) {
        $error = 'Student Name and Class are required.';
    } else {
        $sec = $section_id > 0 ? $section_id : null;
        $stmt = db_prepare("INSERT INTO students (first_name, last_name, father_name, mother_name, email, phone, dob, gender, address, class_id, section_id, admission_date, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $lname = $father_name;
        $dob_db = parse_date($dob);
        $adm_db = parse_date($admission_date) ?? date('Y-m-d');
        $stmt->bind_param('sssssssssiis', $first_name, $lname, $father_name, $mother_name, $email, $cellno, $dob_db, $gender, $address, $class_id, $sec, $adm_db);
        try {
            $stmt->execute();
            $message = 'Student added successfully!';
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
                        <li class="icon-tab-item" data-tab="academic-info"><i class="fa fa-graduation-cap"></i> Academic Information</li>
                        <li class="icon-tab-item" data-tab="contact-info"><i class="fa fa-phone-alt"></i> Contact Information</li>
                        <li class="icon-tab-item" data-tab="documents"><i class="fa fa-file-alt"></i> Documents</li>
                    </ul>

                    <form id="studentForm" action="add_student.php" method="post" enctype="multipart/form-data" class="form-horizontal form-label-left">
                        <input type="hidden" name="action" value="AddAdmission">

                        <div class="wizard-pane active" id="pane-basic-info">
                            <div class="pane-wrap">
                                <div class="pane-title"><i class="fa fa-id-card"></i> Basic Information</div>
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
                                        <label for="cell_no">Cell No / Reporting SMS <span style="color:red;">*</span></label>
                                        <input type="text" value="" class="form-control" name="cellno" required="" id="cell_no" placeholder="Number">
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
                                        <label>Date of Birth</label>
                                        <input type="text" class="form-control" name="dob" id="dob" placeholder="dd/mm/yyyy" value="<?php echo date('d/M/Y'); ?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Date of Admission</label>
                                        <input type="text" class="form-control" name="date_of_adms" id="date_of_adms" placeholder="dd/mm/yyyy" value="<?php echo date('d/M/Y'); ?>">
                                    </div>
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
                                        <label>Photo</label>
                                        <input type="file" class="form-control" name="img_file" id="fileInput" accept="image/*">
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
                                        <label>Father Business Address</label>
                                        <input type="text" value="" class="form-control" name="Fbusiness_address" id="Fbusiness_address" placeholder="Father Business Address">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Father Income</label>
                                        <input type="text" value="" class="form-control" name="Fincome" id="father_income" placeholder="Father Income">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Father Occupation</label>
                                        <select name="father_occupation" id="father_occupation" class="form-control">
                                            <option value="">Select Occupation</option>
                                            <option value="Business">Business</option>
                                            <option value="Government Service">Government Service</option>
                                            <option value="Private Job">Private Job</option>
                                            <option value="Other">Other</option>
                                        </select>
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
                                        <label>Mother Cell Number</label>
                                        <input type="text" value="" class="form-control" name="mother_cell" id="mother_cell" placeholder="Mother Cell Number">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Mother Qualification</label>
                                        <input type="text" value="" class="form-control" name="mother_qualification" id="mother_qualification" placeholder="Mother Qualification">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Mother Activity</label>
                                        <select id="mother_activity" name="mother_activity" class="form-control">
                                            <option value="House Wife">House Wife</option>
                                            <option value="Working">Working</option>
                                            <option value="Business">Business</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Family Home Address</label>
                                        <input type="text" value="" class="form-control" name="address" id="address" placeholder="Family Home Address">
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
                                        <label>Previous Class</label>
                                        <input type="text" value="" class="form-control" name="old_class" id="old_class" placeholder="Previous Class">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Previous Institute</label>
                                        <input type="text" value="" class="form-control" name="old_school" id="old_school" placeholder="Previous Institute">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label>Total Marks</label>
                                        <input type="text" value="" class="form-control" name="old_tmarks" id="old_tmarks" placeholder="Total Marks">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Obtained Marks</label>
                                        <input type="text" value="" class="form-control" name="old_obtmarks" id="old_obtmarks" placeholder="Obtained Marks">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Reason of Previous School Leaving</label>
                                        <input type="text" value="" class="form-control" name="school_leaving" id="school_leaving" placeholder="Reason">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Cast</label>
                                        <input type="text" value="" class="form-control" name="cast" id="cast" placeholder="Cast">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-pane" id="pane-contact-info">
                            <div class="pane-wrap">
                                <div class="pane-title"><i class="fa fa-phone-alt"></i> Contact Information</div>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label>Email</label>
                                        <input type="text" value="" class="form-control" name="email" id="email" placeholder="Email">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Whatsapp Number</label>
                                        <input type="text" value="" class="form-control" name="whatsapp_number" id="whatsapp_number" placeholder="Whatsapp Number">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Home PTCL Number</label>
                                        <input type="text" value="" class="form-control" name="home_number" id="home_number" placeholder="Home Phone">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Place Of Birth</label>
                                        <input type="text" value="" class="form-control" name="place_of_birth" id="place_of_birth" placeholder="Place Of Birth">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Form-B No</label>
                                        <input type="text" value="" class="form-control" name="formBNo" id="formBNo" placeholder="Form-B No">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Locality</label>
                                        <select name="Locality" id="locality" class="form-control">
                                            <option value="">Select Locality</option>
                                            <?php
                                            $loc = db_query("SELECT locality_id, locality_name FROM localities WHERE status=1");
                                            while ($l = $loc->fetch_assoc()) {
                                                echo '<option value="' . $l['locality_id'] . '">' . e($l['locality_name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Guardian Name</label>
                                        <input type="text" value="" class="form-control" name="gname" id="gardian_name" placeholder="Guardian Name">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-pane" id="pane-documents">
                            <div class="pane-wrap">
                                <div class="pane-title"><i class="fa fa-file-alt"></i> Documents</div>
                                <div class="form-group col-md-6">
                                    <label>Document (B-Form / CNIC / Photo)</label>
                                    <input type="file" id="docFile_0" name="doc_files[]" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Previous School Certificate</label>
                                    <input type="file" id="docFile_1" name="doc_files[]" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="text-align: center; margin-top: 18px;">
                            <button type="submit" class="btn btn-success" name="submit" style="padding: 10px 34px; font-size: 14px;"><i class="fa fa-check"></i> Submit</button>
                        </div>
                    </form>
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>