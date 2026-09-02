<?php if (!defined('HIIFI')) exit('Direct access not allowed.'); ?>
<!DOCTYPE html><html lang="en"><head>
    <title><?php echo isset($page_title) ? e($page_title) . ' | HIIFI LMS' : 'HIIFI LMS'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/customizedStyling.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/custom.min1.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style11.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/font-awesome-5.min.css" rel="stylesheet">
    <style>
    .col-md-3.left_col { display: contents !important; width: auto !important; padding: 0 !important; margin: 0 !important; float: none !important; }
    </style>
<style>
.nav-container {
  width: 100%;
  background: #fff;
  border-bottom: 1px solid #eaecef;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 0;
  margin-top:15px;
}
.nav-bar {
  display: flex;
  gap: 1px;
  flex-wrap: nowrap;
  overflow-x: auto;
  justify-content: center;
}
.nav-item {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 15px;
  text-decoration: none;
  color: #2c3e50;
  font-weight: 500;
  font-size: 14px;
  white-space: nowrap;
  transition: all 0.3s ease;
  background: #f8f9fa;
  border: 1px solid #eaecef;
}
.nav-item:hover { background: #fff4e6; border-color: #ffd8b3; color: #e67e22; }
.nav-item.active { background: #ff7800; color: white; border-color: #ff7800; box-shadow: 0 3px 8px rgba(230, 126, 34, 0.3); }
.nav-item.active i { color: white; }
.nav-item i { font-size: 14px; margin-right: 6px; color: #2c3e50; transition: all 0.3s ease; }
.nav-item:hover i { color: #e67e22; }
.nav-bar::-webkit-scrollbar { display: none; }
.nav-bar { -ms-overflow-style: none; scrollbar-width: none; }
</style></head>
<body class="sidebar-expanded">
    <style>
  :root {
    --sb-collapsed: 90px;
    --sb-expanded: 244px;
    --sb-bg: #2A3F54;
    --sb-fly: #1f3548;
    --text: #ECEFF1;
  }
  .left_col {
    position: fixed !important;
    left: 0;
    top: 0;
    height: 100%;
    background: var(--sb-bg);
    z-index: 1000;
    transition: width .25s ease;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    overflow-y: auto;
  }
  body.sidebar-collapsed .left_col { width: var(--sb-collapsed); }
  body.sidebar-expanded .left_col { width: var(--sb-expanded); }
  body.sidebar-collapsed .right_col { margin-left: var(--sb-collapsed) !important; transition: margin-left .25s ease; width: calc(100% - var(--sb-collapsed)); }
  body.sidebar-expanded .right_col { margin-left: var(--sb-expanded) !important; transition: margin-left .25s ease; width: calc(100% - var(--sb-expanded)); }
  .right_col { position: relative; min-height: 100vh; box-sizing: border-box; }
  .main_content, .content-wrapper, .dashboard-content { position: relative; z-index: 1; }
  .ds-brand { display: flex; align-items: center; justify-content: center; padding: 10px 12px; height: 60px; box-sizing: border-box; }
  .ds-brand .ds-toggle { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; color: #fff; }
  .ds-brand .ds-toggle:hover { background: rgba(255, 255, 255, 0.08); }
  .ds-branch { color: #fff; text-align: center; padding: 6px 8px; font-size: 12px; opacity: .9; }
  body.sidebar-collapsed .ds-branch { display: none; }
  #sidebar-menu { padding: 6px 0 16px; }
  #sidebar-menu .side-menu { list-style: none; margin: 0; padding: 0; }
  #sidebar-menu .side-menu>li { position: relative; }
  #sidebar-menu .side-menu>li>a {
    display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: var(--text);
    text-decoration: none; border-radius: 10px; margin: 4px 8px; white-space: nowrap;
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif;
    font-weight: 500; font-size: 14px; letter-spacing: 0.2px; transition: all 0.2s ease;
  }
  #sidebar-menu .side-menu>li>a:hover { background: rgba(255, 255, 255, 0.08); transform: translateX(2px); }
  #sidebar-menu .side-menu>li>a i { min-width: 20px; text-align: center; font-size: 18px; opacity: 0.9; }
  #sidebar-menu .side-menu>li>a .chev { margin-left: auto; opacity: .6; transition: transform 0.2s ease; }
  body.sidebar-collapsed #sidebar-menu .side-menu>li>a { justify-content: center; font-size: 0; padding: 8px 6px; flex-direction: column; align-items: center; gap: 0; }
  body.sidebar-collapsed #sidebar-menu .side-menu>li>a i { font-size: 18px; line-height: 1; }
  body.sidebar-collapsed #sidebar-menu .side-menu>li>a .label { display: block; font-size: 10px; margin-top: 2px; line-height: 1.05; max-width: 64px; text-align: center; white-space: normal; }
  body.sidebar-collapsed #sidebar-menu .side-menu>li>a .chev { display: none; }
  .child_menu { display: none; list-style: none; margin: 0; padding: 6px 0; background: var(--sb-fly); box-shadow: 0 8px 24px rgba(0, 0, 0, .25); border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.06); }
  .child_menu li a { display: block; padding: 10px 16px; font-size: 13px; color: #fff; text-decoration: none; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif; font-weight: 400; letter-spacing: 0.1px; transition: all 0.2s ease; border-radius: 6px; margin: 2px 6px; }
  .child_menu li a:hover { background: rgba(255, 255, 255, 0.08); transform: translateX(3px); color: #fff; }
  body.sidebar-expanded #sidebar-menu .side-menu>li.active>.child_menu { display: block; position: static; margin: 2px 8px 8px 44px; border-radius: 8px; animation: slideIn 0.2s ease; }
  body.sidebar-collapsed #sidebar-menu .side-menu>li>.child_menu { position: fixed; left: var(--sb-collapsed); min-width: 220px; max-height: 80vh; overflow: auto; display: none; z-index: 2001; border: 1px solid rgba(255, 255, 255, 0.05); }
  @media (max-width: 768px) {
    .left_col { width: var(--sb-collapsed) !important; }
    body.sidebar-collapsed .right_col { margin-left: var(--sb-collapsed) !important; width: calc(100% - var(--sb-collapsed)) !important; }
    #sidebar-menu .side-menu>li>a { min-height: 44px; padding: 12px 8px; }
    body.sidebar-collapsed #sidebar-menu .side-menu>li>.child_menu { left: var(--sb-collapsed); min-width: 200px; max-width: calc(100vw - var(--sb-collapsed) - 20px); }
    .child_menu { font-size: 14px; }
    .child_menu li a { padding: 12px 16px; min-height: 44px; display: flex; align-items: center; }
  }
  body.sidebar-expanded #sidebar-menu .side-menu>li>a .label { font-weight: 500; font-size: 14px; color: #fff; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); }
  body.sidebar-expanded #sidebar-menu .side-menu>li>a .chev { font-size: 12px; transition: transform 0.2s ease; }
  body.sidebar-expanded #sidebar-menu .side-menu>li:hover>a .chev { transform: rotate(90deg); }
  .ds-branch { color: #fff; text-align: center; padding: 8px 12px; font-size: 11px; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif; font-weight: 500; opacity: .9; letter-spacing: 0.3px; text-transform: uppercase; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 8px; }
  @keyframes slideIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
  .ds-logout { margin: 12px; }
  .ds-logout a { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 14px; text-decoration: none; color: #fff; background: transparent; border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 10px; transition: all .2s ease; }
  .ds-logout a:hover { background: rgba(255, 255, 255, 0.08); border-color: rgba(255, 255, 255, 0.3); }
  .left_col::-webkit-scrollbar { width: 2px; }
  .left_col::-webkit-scrollbar-track { background: transparent; }
  .left_col::-webkit-scrollbar-thumb { background: linear-gradient(180deg, rgb(67, 67, 68), rgb(241, 243, 245)); border-radius: 10px; }
  .left_col::-webkit-scrollbar-thumb:hover { background: linear-gradient(180deg, rgb(86, 85, 87), rgb(237, 232, 247)); }
  .left_col { scrollbar-width: thin; scrollbar-color: rgb(100, 98, 101) transparent; border-top-right-radius: 1%; border-bottom-right-radius: 1%; }
  .sidebar-logo { text-align: center; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
  .sidebar-logo img { height: 54px; width: 54px; border-radius: 50%; object-fit: cover; border: 2px solid #ff8c00; box-shadow: 0 4px 14px rgba(255, 140, 0, 0.35); background: #fff; display: block; margin: 0 auto; }
</style>

<div class="left_col scroll-view" id="dsLeftCol">
  <div class="sidebar-logo">
    <img src="<?php echo BASE_URL; ?>assets/img/logo.jpg" alt="Logo">
  </div>
  <div class="ds-branch">
    <div style="font-weight:700; font-size:12px;"><?php echo e(get_setting('school_name', 'HIIFI LMS')); ?></div>
    <div style="font-size:12px;">(<?php echo e(get_setting('session_year', '2026-2027')); ?>)</div>
  </div>
  <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
    <ul class="side-menu" id="menu-web">
      <li><a href="<?php echo BASE_URL; ?>software_demo_videos.php"><i class="fa fa-play"></i><span class="label" style="font-weight: normal !important;">Guidline Videos</span></a></li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-phone"></i><span class="label" style="font-weight: normal !important;">Front Office</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>front_desk_analytics.php">Front Desk Overview</a></li>
          <li><a href="<?php echo BASE_URL; ?>student_inquiry.php">Admission Inquiries</a></li>
          <li><a href="<?php echo BASE_URL; ?>manage_complaint.php">Complaint Hub</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-tachometer-alt"></i><span class="label" style="font-weight: normal !important;">Dashboard</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>dashboard.php">Executive Dashboard</a></li>
          <li><a href="<?php echo BASE_URL; ?>basic_dashboard.php">Staff Dashboard</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-users"></i><span class="label" style="font-weight: normal !important;">Students</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>add_student.php">Add New Student</a></li>
          <li><a href="<?php echo BASE_URL; ?>students_analytics_dashboard.php">Student Analytics</a></li>
          <li><a href="<?php echo BASE_URL; ?>class_promotion.php">Class Promotion</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-microphone"></i><span class="label" style="font-weight: normal !important;">Attendance</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>mark_attend.php">Mark Attendance</a></li>
          <li><a href="<?php echo BASE_URL; ?>mark_attendanceReport_list.php">Attendance Analytics</a></li>
          <li><a href="<?php echo BASE_URL; ?>send_msgs.php?attendance=A">Send SMS Report</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-envelope"></i><span class="label" style="font-weight: normal !important;">Messages</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>new_message.php">New Message</a></li>
          <li><a href="<?php echo BASE_URL; ?>messages_history.php">View Messages</a></li>
          <li><a href="<?php echo BASE_URL; ?>view_templates.php">View Templates</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-money"></i><span class="label" style="font-weight: normal !important;">Fee Collection</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>monthly_challan.php">Create Challan</a></li>
          <li><a href="<?php echo BASE_URL; ?>view_challan_details.php">View Challan</a></li>
          <li><a href="<?php echo BASE_URL; ?>multi_fee_reports.php">Fee Reporting</a></li>
          <li><a href="<?php echo BASE_URL; ?>update_fee_settings.php">Fee Settings</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-graduation-cap"></i><span class="label" style="font-weight: normal !important;">Examination</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>view_marksheet.php">Add Marks Sheet</a></li>
          <li><a href="<?php echo BASE_URL; ?>reportcards.php">View Marks Sheet</a></li>
          <li><a href="<?php echo BASE_URL; ?>manage_exams.php">Academic Setting</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-clock-o"></i><span class="label" style="font-weight: normal !important;">Timetable</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>period_categories.php">Periods Category</a></li>
          <li><a href="<?php echo BASE_URL; ?>create_period_details.php">Create/Manage Periods</a></li>
          <li><a href="<?php echo BASE_URL; ?>class_period.php">Assign Periods to Classes</a></li>
          <li><a href="<?php echo BASE_URL; ?>class_period_selection.php">Create Timetable</a></li>
          <li><a href="<?php echo BASE_URL; ?>view_class_period_selection.php">View Timetable</a></li>
          <li><a href="<?php echo BASE_URL; ?>view_teachers_timetable.php">Teachers Timetable</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-user"></i><span class="label" style="font-weight: normal !important;">Employees/HRM</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>add_emp.php">Add Employee</a></li>
          <li><a href="<?php echo BASE_URL; ?>view_emp.php">View Employees</a></li>
          <li><a href="<?php echo BASE_URL; ?>view_emp_attendance.php">Staff Attendance</a></li>
          <li><a href="<?php echo BASE_URL; ?>monthly_attendance.php">Attendance Report</a></li>
          <li><a href="<?php echo BASE_URL; ?>old_employee.php">Old Employees</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-clock-o"></i><span class="label" style="font-weight: normal !important;">Datesheet</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>create_datesheet.php">Create Datesheet</a></li>
          <li><a href="<?php echo BASE_URL; ?>view_datesheet.php">View Datesheet</a></li>
          <li><a href="<?php echo BASE_URL; ?>generate_rollnoSlips.php">Generate Roll No Slips</a></li>
          <li><a href="<?php echo BASE_URL; ?>syllabus_management.php">Syllabus Management</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-truck"></i><span class="label" style="font-weight: normal !important;">Transport</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>vehicles.php">Vehicles</a></li>
          <li><a href="<?php echo BASE_URL; ?>route.php">Routes</a></li>
          <li><a href="<?php echo BASE_URL; ?>vehicle_route.php">Assign Vehicles</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-book"></i><span class="label" style="font-weight: normal !important;">Library</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>list_books.php">Book List</a></li>
          <li><a href="<?php echo BASE_URL; ?>issue_return.php">Issue Return</a></li>
          <li><a href="<?php echo BASE_URL; ?>issue_return_employee.php">Employee Issue&amp;Return</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fab fa-paypal"></i><span class="label" style="font-weight: normal !important;">PayRoll</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>creat_payroll.php">Create PayRoll</a></li>
          <li><a href="<?php echo BASE_URL; ?>view_payroll.php">View PayRoll</a></li>
          <li><a href="<?php echo BASE_URL; ?>staff_security.php">Staff Security Fee</a></li>
          <li><a href="<?php echo BASE_URL; ?>payroll_setting.php">PayRoll Setting</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-home"></i><span class="label" style="font-weight: normal !important;">Parents Portal</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>parents_portal_dashboard.php">Parents Overview</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-money"></i><span class="label" style="font-weight: normal !important;">Expenses</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>manage_expenses.php">Add/View Expenses</a></li>
          <li><a href="<?php echo BASE_URL; ?>monthly_expenses_report.php">Expenses Report</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-file"></i><span class="label" style="font-weight: normal !important;">Cards Generator</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>cards.php">Staff Cards</a></li>
          <li><a href="<?php echo BASE_URL; ?>students_card.php">Students Cards</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-search"></i><span class="label" style="font-weight: normal !important;">Point of Sale</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>canteen_dashboard.php">POS Dashboard</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-dollar"></i><span class="label" style="font-weight: normal !important;">Academic Setup</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>academic_setup.php">Manage Academics</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-gear"></i><span class="label" style="font-weight: normal !important;">System Settings</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>settings.php">Update Settings</a></li>
          <li><a href="<?php echo BASE_URL; ?>manage_localities.php">Manage Localities</a></li>
        </ul>
      </li>
      <li class="has-children">
        <a href="javascript:void(0)"><i class="fa fa-calculator"></i><span class="label" style="font-weight: normal !important;">Accounts</span><span class="fa fa-chevron-right chev"></span></a>
        <ul class="child_menu" style="display: none;">
          <li><a href="<?php echo BASE_URL; ?>add_revenue.php">Add Revenue</a></li>
          <li><a href="<?php echo BASE_URL; ?>revenue_list.php">List of Revenues</a></li>
          <li><a href="<?php echo BASE_URL; ?>revenue_heads.php">Revenue Heads</a></li>
        </ul>
      </li>
      <li class="ds-logout">
        <a href="#" onclick="localStorage.clear(); window.location.href='<?php echo BASE_URL; ?>logout.php'; return false;">
          <span>LOGOUT</span> <i class="fa fa-sign-out" aria-hidden="true"></i>
        </a>
      </li>
    </ul>
  </div>
</div>

        <div class="right_col" role="main" style="min-height: 733px;">

<link href="<?php echo BASE_URL; ?>assets/plugins/select2/select2.min.css" rel="stylesheet">
<script src="<?php echo BASE_URL; ?>assets/js/jquery.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/bootstrap.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/plugins/select2/select2.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/app_shared.js"></script>
<?php
$topSchoolName = get_setting('school_name', 'HIIFI LMS');
$topSession    = get_setting('session_year', '2026-2027');
$topSmsUsed    = (int) get_setting('whatsapp_sms_used', 0);
$topSmsLimit   = (int) get_setting('whatsapp_sms_limit', 10000);
$topSmsPct     = $topSmsLimit > 0 ? min(100, round($topSmsUsed / $topSmsLimit * 100)) : 0;
$topNewComp    = (int) (db_query("SELECT COUNT(*) c FROM complaints WHERE status IN ('new','open')")->fetch_assoc()['c'] ?? 0);
$topUserName   = e($_SESSION['user_name'] ?? 'Admin');
?>
<style>
    .hiifi-topbar { display: flex; align-items: center; gap: 12px; padding: 9px 16px; background: #fff; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 900; flex-wrap: nowrap; }
    .hiifi-topbar .tb-brand { display: flex; align-items: center; gap: 8px; min-width: 0; }
    .hiifi-topbar .tb-brand img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1.5px solid #ff8c00; }
    .hiifi-topbar .tb-brand .tb-txt { display: flex; flex-direction: column; line-height: 1.1; min-width: 0; }
    .hiifi-topbar .tb-brand .tb-txt b { font-size: 13px; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hiifi-topbar .tb-brand .tb-txt small { font-size: 10.5px; color: #64748b; }
    .hiifi-topbar .tb-hamburger { background: none; border: none; font-size: 18px; color: #475569; cursor: pointer; padding: 6px; }
    .hiifi-topbar .tb-search { position: relative; flex: 1 1 240px; max-width: 430px; }
    .hiifi-topbar .tb-search input { width: 100%; border: 1px solid #e2e8f0; border-radius: 999px; padding: 7px 14px 7px 34px; font-size: 12.5px; outline: none; background: #f8fafc; }
    .hiifi-topbar .tb-search input:focus { border-color: #377dff; background: #fff; }
    .hiifi-topbar .tb-search > i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
    .tb-search-results { position: absolute; top: 108%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,.12); max-height: 320px; overflow: auto; display: none; z-index: 1200; }
    .tb-search-results a { display: flex; gap: 10px; padding: 9px 12px; text-decoration: none; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
    .tb-search-results a:hover { background: #f8fafc; }
    .tb-search-results .sr-ava { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg,#377dff,#7c3aed); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
    .tb-search-results .sr-meta { overflow: hidden; }
    .tb-search-results .sr-meta b { display: block; font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tb-search-results .sr-meta span { font-size: 11px; color: #64748b; }
    .tb-search-results .sr-empty { padding: 14px; font-size: 12.5px; color: #64748b; text-align: center; }
    .hiifi-topbar .tb-msgs, .hiifi-topbar .tb-acc { position: relative; }
    .hiifi-topbar .tb-btn { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
    .hiifi-topbar .tb-btn:hover { background: #f1f5f9; }
    .hiifi-topbar .tb-bell { position: relative; }
    .hiifi-topbar .tb-bell .dot { position: absolute; top: 2px; right: 2px; background: #ef4444; border-radius: 50%; min-width: 14px; height: 14px; font-size: 9px; color: #fff; display: flex; align-items: center; justify-content: center; padding: 0 3px; }
    .hiifi-dd { position: absolute; top: 112%; right: 0; background: #fff; border: 1px solid #eef2f7; border-radius: 12px; box-shadow: 0 14px 34px rgba(0,0,0,.14); min-width: 240px; padding: 8px; display: none; z-index: 1300; }
    .hiifi-dd:before { content: ''; position: absolute; top: -6px; right: 18px; width: 12px; height: 12px; background: #fff; border-left: 1px solid #eef2f7; border-top: 1px solid #eef2f7; transform: rotate(45deg); }
    .hiifi-dd .dd-h { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; padding: 6px 10px; letter-spacing: .4px; }
    .hiifi-dd a { display: flex; align-items: center; gap: 9px; padding: 8px 10px; font-size: 12.5px; color: #334155; text-decoration: none; border-radius: 8px; }
    .hiifi-dd a:hover { background: #f1f5f9; }
    .hiifi-dd a i { width: 16px; text-align: center; color: #64748b; }
    .hiifi-dd .smsbar { padding: 4px 10px 10px; }
    .hiifi-dd .smsbar .prog { height: 6px; background: #eef2f7; border-radius: 99px; overflow: hidden; margin: 6px 0 4px; }
    .hiifi-dd .smsbar .prog > i { display: block; height: 100%; background: linear-gradient(90deg,#377dff,#7c3aed); }
    .hiifi-dd .smsbar span { font-size: 11px; color: #64748b; }
    @media (max-width: 768px) { .hiifi-topbar .tb-brand .tb-txt, .hiifi-topbar .tb-msgs { display: none; } }
</style>

<div class="hiifi-topbar">
    <button type="button" class="tb-hamburger" onclick="document.body.classList.toggle('sidebar-collapsed');document.body.classList.toggle('sidebar-expanded')" title="Toggle sidebar"><i class="fa fa-bars"></i></button>
    <div class="tb-brand">
        <img src="<?php echo BASE_URL; ?>assets/img/logo.jpg" alt="Logo">
        <div class="tb-txt">
            <b><?php echo e($topSchoolName); ?></b>
            <small>Session <?php echo e($topSession); ?></small>
        </div>
    </div>
    <div class="tb-search">
        <i class="fa fa-search"></i>
        <input type="text" id="tbSearch" placeholder="Search student by name, GR No or cell..." autocomplete="off">
        <div class="tb-search-results" id="tbSearchRes"></div>
    </div>
    <div class="tb-msgs">
        <button type="button" class="tb-btn" onclick="var d=document.getElementById('smsdd');d.style.display=d.style.display==='block'?'none':'block'"><i class="fa fa-signal"></i> SMS Usage</button>
        <div class="hiifi-dd" id="smsdd" style="right:0">
            <div class="smsbar">
                <div class="dd-h">WhatsApp SMS Stared</div>
                <div class="prog"><i style="width:<?php echo $topSmsPct; ?>%"></i></div>
                <span><?php echo $topSmsUsed; ?> / <?php echo $topSmsLimit; ?> used this month</span>
            </div>
            <a href="<?php echo BASE_URL; ?>messages_history.php"><i class="fa fa-envelope-o"></i> View Messages History</a>
            <a href="<?php echo BASE_URL; ?>whatsapp_setting.php"><i class="fa fa-cog"></i> SMS Settings</a>
        </div>
    </div>
    <div class="tb-bell">
        <button type="button" class="tb-btn" onclick="var d=document.getElementById('notifydd');d.style.display=d.style.display==='block'?'none':'block'" style="padding:6px 9px;"><i class="fa fa-bell-o"></i><?php if ($topNewComp > 0): ?><span class="dot"><?php echo $topNewComp; ?></span><?php endif; ?></button>
        <div class="hiifi-dd" id="notifydd">
            <div class="dd-h">Notifications</div>
            <a href="<?php echo BASE_URL; ?>manage_complaint.php"><i class="fa fa-warning"></i> <?php echo $topNewComp; ?> open complaint<?php echo $topNewComp === 1 ? '' : 's'; ?></a>
            <a href="<?php echo BASE_URL; ?>customer_tickets.php"><i class="fa fa-life-ring"></i> Support tickets</a>
            <a href="<?php echo BASE_URL; ?>student_inquiry.php"><i class="fa fa-user-plus"></i> Admission inquiries</a>
        </div>
    </div>
    <div class="tb-acc">
        <button type="button" class="tb-btn" onclick="var d=document.getElementById('accdd');d.style.display=d.style.display==='block'?'none':'block'"><i class="fa fa-user-circle" style="font-size:15px; color:#377dff;"></i> <?php echo $topUserName; ?> <i class="fa fa-caret-down"></i></button>
        <div class="hiifi-dd" id="accdd">
            <div class="dd-h">Account</div>
            <a href="<?php echo BASE_URL; ?>update_profile.php"><i class="fa fa-user"></i> Profile</a>
            <a href="<?php echo BASE_URL; ?>customer_tickets.php"><i class="fa fa-life-ring"></i> Support Tickets</a>
            <a href="<?php echo BASE_URL; ?>monthly_invoices.php"><i class="fa fa-file-text-o"></i> Invoices</a>
            <a href="<?php echo BASE_URL; ?>staff_security.php"><i class="fa fa-shield"></i> Security</a>
            <a href="<?php echo BASE_URL; ?>update_pswd.php"><i class="fa fa-key"></i> Change Password</a>
            <a href="<?php echo BASE_URL; ?>logout.php" onclick="localStorage.clear()"><i class="fa fa-sign-out"></i> Logout</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    ['smsdd', 'notifydd', 'accdd'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el && !e.target.closest('.tb-msgs') && !e.target.closest('.tb-bell') && !e.target.closest('.tb-acc')) el.style.display = 'none';
    });
});
(function () {
    var inp = document.getElementById('tbSearch'), box = document.getElementById('tbSearchRes'), timer = null;
    if (!inp) return;
    inp.addEventListener('input', function () {
        clearTimeout(timer);
        var q = inp.value.trim();
        if (q.length < 2) { box.style.display = 'none'; return; }
        timer = setTimeout(function () {
            eduGet(HIIFI_BASE + 'live_search_students.php?term=' + encodeURIComponent(q), function (data) {
                box.innerHTML = '';
                if (!data || !data.length) {
                    box.innerHTML = '<div class="sr-empty">No students found</div>';
                } else {
                    data.forEach(function (s) {
                        var a = document.createElement('a');
                        a.href = HIIFI_BASE + 'manage_students.php?student_id=' + s.id;
                        var av = document.createElement('div'); av.className = 'sr-ava'; av.textContent = (s.name || '?').charAt(0).toUpperCase() + (s.father ? s.father.charAt(0) : '');
                        var meta = document.createElement('div'); meta.className = 'sr-meta';
                        var b = document.createElement('b'); b.textContent = s.name + ' (' + s.gr + ')';
                        var sp = document.createElement('span'); sp.textContent = s.class_sec;
                        meta.appendChild(b); meta.appendChild(sp);
                        a.appendChild(av); a.appendChild(meta);
                        box.appendChild(a);
                    });
                }
                box.style.display = 'block';
            });
        }, 220);
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.tb-search')) box.style.display = 'none'; });
})();
</script>