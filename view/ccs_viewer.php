<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "CCS Rules Viewer";
ob_start();

require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add DataTables CSS
$additional_css = '
<!-- DataTables -->
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') . '">

<style>
    .card-tools {
        float: right;
    }
    
    .table thead th {
        vertical-align: middle;
        text-align: center;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .input-group-text {
        border-right: 0;
    }
    
    .input-group .form-control {
        border-left: 0;
    }
    
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1rem;
    }
    
    /* Add these styles to fix table responsiveness */
    .table-responsive {
        width: 100%;
        margin-bottom: 1rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Ensure table takes full width */
    .table {
        width: 100% !important;
        margin-bottom: 0;
    }

    /* Adjust content wrapper padding */
    .content-wrapper {
        transition: margin-left .3s ease-in-out;
        margin-left: 250px;  /* Default with sidebar open */
    }

    /* Adjust when sidebar is collapsed */
    body.sidebar-collapse .content-wrapper {
        margin-left: 4.6rem;
    }

    @media (max-width: 768px) {
        .content-wrapper {
            margin-left: 0;
        }
    }

    .btn-group .btn {
        padding: 6px 12px;
        line-height: 1.2;
        margin: 0 2px;
    }

    .btn-group .btn i {
        font-size: 16px;
    }

    .actions-column {
        white-space: nowrap;
        width: 120px;
        text-align: center;
        padding: 8px !important;
    }

    .actions-column .btn-group {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-sm {
        height: 32px;
        min-width: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .floating-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 250px;
        max-width: 350px;
        animation: slideIn 0.5s ease-in-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Add small spacing for pagination */
    .dataTables_wrapper .row {
        margin: 0;
        padding: 8px 0;
    }
    
    .dataTables_length, 
    .dataTables_filter,
    .dataTables_info, 
    .dataTables_paginate {
        padding: 8px;
    }

    /* Table column widths */
    .table th:nth-last-child(3), /* Status column */
    .table td:nth-last-child(3) {
        width: 80px !important;
        min-width: 80px !important;
    }

    .table th:nth-last-child(2), /* Doc column */
    .table td:nth-last-child(2) {
        width: 60px !important;
        min-width: 60px !important;
    }

    .table th:last-child, /* Actions column */
    .table td:last-child {
        width: 100px !important;
        min-width: 100px !important;
    }

    /* Center content in status, doc, and actions columns */
    .table td:nth-last-child(-n+3) {
        text-align: center;
        vertical-align: middle;
    }

    /* Button group styling */
    .btn-group .btn-sm {
        padding: 0.25rem 0.5rem;
    }

    .btn-group .btn-sm i {
        font-size: 0.875rem;
    }

    /* Simple button style for View link */
    .btn-xs {
        padding: 1px 5px;
        font-size: 12px;
        line-height: 1.5;
        border-radius: 3px;
    }

    .btn-default {
        color: #333;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
    }

    .btn-default:hover {
        background-color: #e9ecef;
        border-color: #ccc;
        text-decoration: none;
    }

    /* Select2 styling */
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(2.25rem + 2px) !important;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        line-height: 2.25rem !important;
    }

    .select2-container--bootstrap4 .select2-selection__arrow {
        height: calc(2.25rem + 2px) !important;
    }

    /* Date picker styling */
    .input-group-text {
        border-left: none;
    }

    .form-control:focus + .input-group-append .input-group-text {
        border-color: #80bdff;
    }

    /* Select2 Improvements */
    .select2-container--bootstrap4 {
        width: 100% !important;
    }

    .select2-container--bootstrap4 .select2-selection {
        height: 38px !important;
    }

    .select2-container--bootstrap4 .select2-selection--single {
        padding: 0.375rem 0.75rem;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        color: #495057;
        line-height: 1.5;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        top: 0;
        right: 0;
        width: 20px;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b {
        border-color: #888 transparent transparent transparent;
        border-style: solid;
        border-width: 5px 4px 0 4px;
        height: 0;
        left: 50%;
        margin-left: -4px;
        margin-top: -2px;
        position: absolute;
        top: 50%;
        width: 0;
        display: block !important;
    }

    .select2-container--bootstrap4.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent #888 transparent;
        border-width: 0 4px 5px 4px;
    }

    .select2-container--bootstrap4 .select2-dropdown {
        border-color: #80bdff;
        border-radius: 4px;
        margin-top: -1px;
    }

    .select2-container--bootstrap4 .select2-results__option {
        padding: 0.5rem 0.75rem;
        font-size: 1rem;
    }

    .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff;
        color: white;
    }

    /* Modal specific Select2 */
    #editRuleModal .select2-container {
        z-index: 1056;
    }

    #editRuleModal .select2-dropdown {
        z-index: 1057;
    }

    /* Date Input Styling */
    .input-group .form-control {
        border-right: 0;
    }

    .input-group-text {
        background-color: #fff;
        border-left: 0;
    }

    .input-group:focus-within .form-control,
    .input-group:focus-within .input-group-text {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    /* Modal Form Improvements */
    .modal .form-group {
        margin-bottom: 1rem;
    }

    .modal .form-group label {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .modal .input-group {
        flex-wrap: nowrap;
    }

    .modal input[type="date"] {
        padding: 0.375rem 0.75rem;
    }

    /* Fix modal z-index */
    .modal-backdrop {
        z-index: 1040 !important;
    }

    .modal {
        z-index: 1050 !important;
    }

    .select2-container {
        z-index: 1060 !important;
    }

    /* Filter section styling */
    .filter-section .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        display: none !important;
    }

    .filter-section .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        padding-right: 0 !important;
        margin-right: 0 !important;
    }

    .filter-section .select2-container--bootstrap4 .select2-selection {
        padding-right: 0.75rem !important;
    }

    /* Edit modal Select2 specific styles */
    #editRuleModal .select2-container--bootstrap4 .select2-selection {
        height: 38px !important;
    }

    #editRuleModal .select2-container--bootstrap4 .select2-selection--single {
        padding: 0.375rem 0.75rem;
    }

    #editRuleModal .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        color: #495057;
        line-height: 1.5;
    }

    #editRuleModal .select2-container--bootstrap4 .select2-results__option {
        padding: 0.5rem 0.75rem;
        font-size: 1rem;
    }

    #editRuleModal .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff;
        color: white;
    }

    /* Status badge styling */
    .badge-success, .badge-danger {
        color: #fff !important;
        font-weight: 500;
        padding: 0.4em 0.6em;
    }

    .badge-success {
        background-color: #28a745 !important;  /* Green for expired */
    }

    .badge-danger {
        background-color: #dc3545 !important;  /* Red for active */
    }

    /* Filter section styling */
    .select2-container--bootstrap4 .select2-selection {
        text-align: left !important;
    }

    .select2-container--bootstrap4 .select2-selection__rendered {
        text-align: left !important;
        padding-right: 0 !important;
    }

    .select2-container--bootstrap4 .select2-selection__arrow {
        display: none !important;
    }

    /* Keep dropdown options left-aligned */
    .select2-results__option {
        text-align: left !important;
    }

    /* Center the filter labels */
    .form-group label {
        text-align: center !important;
        width: 100%;
    }

    /* Make clear filters button text centered */
    #clearFilters {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 38px;
    }

    /* Select2 text alignment */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        padding-top: 5px !important;
        padding-left: 12px !important;
        text-align: left !important;
        line-height: 1.5 !important;
    }

    .select2-container--bootstrap4 .select2-results__option {
        padding-left: 12px !important;
        text-align: left !important;
    }

    /* Remove dropdown arrow */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        display: none !important;
    }

    /* Vertically center text */
    .select2-container--bootstrap4 .select2-selection {
        height: 38px !important;
        display: flex !important;
        align-items: center !important;
    }

    /* Center placeholder text */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
        text-align: center !important;
        width: 100% !important;
    }

    /* Filter section */
    .filter-section {
        position: relative;
        z-index: 1;
    }

    /* Modal styles */
    .modal-backdrop {
        z-index: 1040;
    }

    .modal {
        z-index: 1045;
    }

    /* Select2 styles */
    .select2-container {
        z-index: 1;  /* Default z-index for filters */
    }

    /* Select2 in modal should be above modal */
    .modal .select2-container {
        z-index: 1055 !important;
    }

    /* Select2 dropdown when in modal */
    .select2-dropdown {
        z-index: 1056 !important;
    }

    /* Ensure modal content is above Select2 dropdowns */
    .modal-content {
        position: relative;
        z-index: 1046;
    }

    /* Filter section Select2 */
    .card .select2-container {
        width: 100% !important;
    }

    /* Remove Select2 clear button (x) */
    .select2-container--bootstrap4 .select2-selection__clear {
        display: none !important;
    }

    /* Or if you want to style it instead of hiding it */
    /*
    .select2-container--bootstrap4 .select2-selection__clear {
        margin-right: 10px;
        color: #6c757d;
        font-size: 0.875rem;
    }
    .select2-container--bootstrap4 .select2-selection__clear:hover {
        color: #dc3545;
    }
    */

    /* Ensure proper alignment of text */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        padding-right: 10px !important;
    }

    /* Remove select arrow and make styling consistent */
    select.form-control {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: none !important;
        padding-right: 12px !important;
    }

    /* Remove default arrow in IE */
    select.form-control::-ms-expand {
        display: none;
    }

    /* Make select2 match other form controls */
    .select2-container--bootstrap4 .select2-selection {
        height: calc(2.25rem + 2px) !important;
        padding: .375rem .75rem !important;
        font-size: 1rem !important;
        line-height: 1.5 !important;
        border: 1px solid #ced4da !important;
        border-radius: .25rem !important;
    }

    /* Remove select2 dropdown arrow */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        display: none !important;
    }

    /* Fix select2 positioning and spacing */
    .select2-container {
        width: 100% !important;
        margin: 0;
    }

    .select2-container .select2-selection--single {
        height: 38px !important;
        padding: 8px 12px !important;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        padding: 0 !important;
        line-height: 1.5 !important;
        color: #495057;
    }

    /* Remove extra spacing */
    .select2-container--bootstrap4 {
        margin: 0 !important;
    }

    /* Ensure placeholder text aligns with other inputs */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
        color: #6c757d;
        line-height: 1.5;
    }

    /* Modal Select2 styles */
    #editRuleModal .select2-container {
        width: 100% !important;
    }

    #editRuleModal .select2-container--bootstrap4 .select2-selection {
        height: 38px !important;
    }

    #editRuleModal .select2-container--bootstrap4 .select2-selection--single {
        padding: 8px 12px !important;
    }

    #editRuleModal .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        padding: 0 !important;
        line-height: 1.5 !important;
    }

    /* Ensure dropdown appears below in modal */
    .select2-container--bootstrap4.select2-container--open .select2-dropdown {
        margin-top: 0 !important;
        border-top: 1px solid #ced4da !important;
    }

    .select2-container--bootstrap4 .select2-results__option {
        padding: 8px 12px !important;
    }

    .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff !important;
    }

    /* Center the filter labels only */
    .filter-section .form-group label {
        text-align: center !important;
        width: 100%;
    }

    /* Modal form labels - align left */
    #editRuleModal .form-group label {
        text-align: left !important;
        width: 100%;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    /* Keep filter labels centered but modal labels left-aligned */
    .card-body .form-group label:not(#editRuleModal .form-group label) {
        text-align: center !important;
    }
</style>';

// Add DataTables and AdminLTE JS
$additional_js = '
<!-- DataTables & Plugins -->
<script src="' . Router::url('adminlte/plugins/datatables/jquery.dataTables.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') . '"></script>
<script>
var baseUrl = "' . Router::url('') . '";
</script>
<script src="' . Router::url('public/dist/js/ccs_viewer.js') . '"></script>';

// Add Select2 CSS to your additional_css
$additional_css .= '
<!-- Select2 -->
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/select2/css/select2.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') . '">';

// Add Select2 JS to your additional_js
$additional_js .= '
<!-- Select2 -->
<script src="' . Router::url('adminlte/plugins/select2/js/select2.full.min.js') . '"></script>';
?>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
<div class="card">
    <div class="card-header">
        <h3 class="card-title">CCS Rules Viewer</h3>
    </div>
    <div class="card-body">
        <!-- Notifications -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show py-2">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

                        <!-- Filter section -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body p-3">
            <div class="row filter-section">
                <!-- Project Filter -->
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label class="mb-2">Project</label>
                        <select class="form-control select2-filter" id="projectFilter">
                            <option value="">All Projects</option>
                            <?php
                            $stmt = $conn->query("SELECT DISTINCT project FROM ccs_rules WHERE project IS NOT NULL ORDER BY project");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                if (!empty($row['project'])) {
                                    echo "<option value='" . htmlspecialchars($row['project']) . "'>" . 
                                         htmlspecialchars($row['project']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label class="mb-2">Role</label>
                        <select class="form-control select2-filter" id="roleFilter">
                            <option value="">All Roles</option>
                            <?php
                            $stmt = $conn->query("SELECT DISTINCT role FROM ccs_rules WHERE role IS NOT NULL ORDER BY role");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                if (!empty($row['role'])) {
                                    echo "<option value='" . htmlspecialchars($row['role']) . "'>" . 
                                         htmlspecialchars($row['role']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label class="mb-2">Status</label>
                        <select class="form-control select2-filter" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                </div>

                <!-- Clear Filters Button -->
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-secondary w-100" id="clearFilters" style="height: 38px;">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
                                </div>
                            </div>
                        </div>

        <!-- Table -->
        <div class="table-responsive">
                            <table id="rulesTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>NIK</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Tenure</th>
                        <th>Case Chronology</th>
                        <th>Consequences</th>
                        <th>Effective Date</th>
                        <th>End Date</th>
                        <th style="width: 80px;">Status</th>
                        <th style="width: 100px;">Doc</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                                    <!-- Data loaded via DataTables -->
                </tbody>
            </table>
        </div>
    </div>
</div>
            </div>
        </div>
    </div>
</section>

<!-- Edit Rule Modal -->
<div class="modal fade" id="editRuleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Rule</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editRuleForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <input type="hidden" id="edit_project" name="project">
                    
                    <!-- Display only fields -->
                    <div class="form-group">
                        <label>NIK</label>
                        <input type="text" class="form-control" id="edit_nik" readonly>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" id="edit_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" class="form-control" id="edit_role" readonly>
                    </div>

                    <!-- Editable fields -->
                    <div class="form-group">
                        <label>Case Chronology</label>
                        <textarea class="form-control" id="edit_case_chronology" name="case_chronology" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Consequences</label>
                        <select class="form-control" id="edit_consequences" name="consequences" required>
                            <option value="">Select Consequences</option>
                            <option value="Written Reminder 1">Written Reminder 1</option>
                            <option value="Written Reminder 2">Written Reminder 2</option>
                            <option value="Written Reminder 3">Written Reminder 3</option>
                            <option value="Warning Letter 1">Warning Letter 1</option>
                            <option value="Warning Letter 2">Warning Letter 2</option>
                            <option value="Warning Letter 3">Warning Letter 3</option>
                            <option value="First & Last Warning Letter">First & Last Warning Letter</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Effective Date</label>
                        <input type="date" class="form-control" id="edit_effective_date" name="effective_date" 
                               max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control" id="edit_end_date" name="end_date" required readonly>
                    </div>
                    <div class="form-group">
                        <label>Supporting Document</label>
                        <div class="input-group">
                        <div class="custom-file">
                                <input type="file" class="custom-file-input" id="edit_supporting_doc" name="supporting_doc">
                                <label class="custom-file-label" for="edit_supporting_doc">Choose file</label>
                            </div>
                        </div>
                        <small class="form-text text-muted">Leave empty to keep existing document</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
?>
