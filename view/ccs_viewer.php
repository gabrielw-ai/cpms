<?php
// Change session_start() to check if session is already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "CCS Rules Viewer";
ob_start();
require_once '../controller/conn.php';

// Get user's role and check if they are an agent
$userRole = $_SESSION['user_role'] ?? '';
$isAgent = ($userRole === 'Agent');

// Debug log
error_log("User Role: " . $userRole);
error_log("Is Agent: " . ($isAgent ? 'Yes' : 'No'));
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">CCS Rules Viewer</h3>
    </div>
    <div class="card-body">
        <!-- Notifications -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show py-2">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true" style="padding: .5rem;">×</button>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true" style="padding: .5rem;">×</button>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Update the filters section -->
        <?php if (!$isAgent): ?>
        <!-- Add filters section -->
        <div class="filter-section mb-3">
            <div class="row">
                <!-- Project Filter -->
                <div class="col-md-2">
                    <label>Project</label>
                    <select class="form-control select2" id="projectFilter" data-placeholder="All Projects">
                        <option value=""></option>
                        <?php
                        $stmt = $conn->query("SELECT DISTINCT project FROM ccs_rules ORDER BY project");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='" . htmlspecialchars($row['project']) . "'>" . 
                                 htmlspecialchars($row['project']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Role Filter -->
                <div class="col-md-2">
                    <label>Role</label>
                    <select class="form-control select2" id="roleFilter" data-placeholder="All Roles">
                        <option value=""></option>
                        <?php
                        $stmt = $conn->query("SELECT DISTINCT role FROM ccs_rules ORDER BY role");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='" . htmlspecialchars($row['role']) . "'>" . 
                                 htmlspecialchars($row['role']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Consequences Filter -->
                <div class="col-md-2">
                    <label>Consequences</label>
                    <select class="form-control select2" id="consequencesFilter" data-placeholder="All Consequences">
                        <option value=""></option>
                        <option value="WR1">Written Reminder 1</option>
                        <option value="WR2">Written Reminder 2</option>
                        <option value="WR3">Written Reminder 3</option>
                        <option value="WL1">Warning Letter 1</option>
                        <option value="WL2">Warning Letter 2</option>
                        <option value="WL3">Warning Letter 3</option>
                        <option value="FLW">First & Last Warning Letter</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <label>Status</label>
                    <select class="form-control select2" id="statusFilter" data-placeholder="All Status">
                        <option value=""></option>
                        <option value="active">Active</option>
                        <option value="deactive">Deactive</option>
                    </select>
                </div>

                <!-- Name/NIK Search -->
                <div class="col-md-4">
                    <label>Search Name/NIK</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="nameSearch" placeholder="Type to search...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clear Filters Button -->
            <div class="row mt-2">
                <div class="col-12">
                    <button id="clearFilters" class="btn btn-secondary btn-sm">
                        <i class="fas fa-eraser"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="table-responsive">
            <table id="ccsRulesTable" class="table table-bordered table-striped">
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
                        <th>Status</th>
                        <th>Supporting Doc</th>
                        <?php if (!$isAgent): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Modify query based on user role
                        if ($isAgent) {
                            $stmt = $conn->prepare("SELECT * FROM ccs_rules WHERE NIK = ? ORDER BY effective_date DESC");
                            $stmt->execute([$_SESSION['user_nik']]);
                        } else {
                            $stmt = $conn->query("SELECT * FROM ccs_rules ORDER BY effective_date DESC");
                        }

                        while ($row = ($isAgent ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetch(PDO::FETCH_ASSOC))) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['project']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nik']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['tenure']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['case_chronology']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['consequences']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['effective_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['end_date']) . "</td>";
                            echo "<td><span class='badge badge-" . 
                                 ($row['status'] === 'active' ? 'success' : 'danger') . "'>" . 
                                 htmlspecialchars($row['status']) . "</span></td>";
                            echo "<td>";
                            if ($row['supporting_doc_url']) {
                                echo "<a href='" . htmlspecialchars($row['supporting_doc_url']) . 
                                     "' target='_blank' class='btn btn-sm btn-info'><i class='fas fa-file'></i> View</a>";
                            }
                            echo "</td>";
                            
                            // Only show action buttons for non-agents
                            if (!$isAgent) {
                                echo "<td>
                                        <button type='button' class='btn btn-sm btn-primary' onclick='editRule(this)' 
                                                data-id='" . $row['id'] . "'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <button type='button' class='btn btn-sm btn-danger' onclick='deleteRule(" . $row['id'] . ")'>
                                            <i class='fas fa-trash'></i>
                                        </button>
                                      </td>";
                            }
                            echo "</tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='" . ($isAgent ? '11' : '12') . "'>Error: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add DataTables CSS -->
<?php
$additional_css = '
<link rel="stylesheet" href="../adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
';

$additional_js = '
<script src="../adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../adminlte/plugins/datatables-fixedheader/js/dataTables.fixedHeader.min.js"></script>
<script src="../adminlte/plugins/select2/js/select2.full.min.js"></script>
';
?>

<!-- Add DataTables JS -->
<script>
// Define these functions globally
function editRule(btn) {
    const id = $(btn).data('id');
    
    // Fetch rule data
    $.get(`../controller/get_rule.php?id=${id}`, function(data) {
        if (data.success) {
            // Fill the form
            $('#edit_id').val(data.rule.id);
            $('#edit_project').val(data.rule.project).trigger('change');
            $('#edit_case_chronology').val(data.rule.case_chronology);
            $('#edit_ccs_rule').val(data.rule.consequences);
            $('#edit_effective_date').val(data.rule.effective_date);
            $('#edit_end_date').val(data.rule.end_date);
            
            // Show modal
            $('#editModal').modal('show');
        } else {
            alert('Error loading rule data');
        }
    });
}

function deleteRule(id) {
    if (confirm('Are you sure you want to delete this rule?')) {
        // Create and submit form for deletion
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controller/c_ccs_rules.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;

        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Your existing DOMContentLoaded event handler
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true,
        placeholder: 'Select an option'
    });

    // Initialize DataTable
    var table = $('#ccsRulesTable').DataTable({
        "responsive": true,
        "pageLength": 20,
        "lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "All"]],
        "dom": 
            "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "scrollY": "500px",
        "scrollX": true,
        "scrollCollapse": true,
        "fixedHeader": true,
        "searching": true,
        "autoWidth": false,
        "order": [[7, "desc"]]
    });

    // Name/NIK search
    $('#nameSearch').on('keyup', function() {
        var searchText = $(this).val().toLowerCase();
        table.search(searchText).draw();
    });

    // Project filter
    $('#projectFilter').on('change', function() {
        var val = $(this).val();
        table.column(0).search(val ? val : '', true, false).draw();
    });

    // Role filter
    $('#roleFilter').on('change', function() {
        var val = $(this).val();
        table.column(3).search(val ? val : '', true, false).draw();
    });

    // Consequences filter
    $('#consequencesFilter').on('change', function() {
        var val = $(this).val();
        table.column(6).search(val ? val : '', true, false).draw();
    });

    // Status filter
    $('#statusFilter').on('change', function() {
        var val = $(this).val();
        table.column(9).search(val ? val : '', true, false).draw();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        // Clear select2 dropdowns
        $('#projectFilter, #roleFilter, #consequencesFilter, #statusFilter').val(null).trigger('change');
        
        // Clear search box
        $('#nameSearch').val('');
        
        // Clear all filters and search
        table.search('').columns().search('').draw();
    });

    // Add function to calculate end date when effective date or CCS rule changes
    $('#edit_effective_date, #edit_ccs_rule').on('change', function() {
        const effectiveDate = $('#edit_effective_date').val();
        const ccsRule = $('#edit_ccs_rule').val();
        
        if (effectiveDate && ccsRule) {
            const startDate = new Date(effectiveDate);
            let endDate = new Date(startDate);
            
            if (ccsRule.startsWith('WR')) {
                endDate.setFullYear(endDate.getFullYear() + 1);
                endDate.setDate(endDate.getDate() - 1);
            } else {
                endDate.setMonth(endDate.getMonth() + 6);
                endDate.setDate(endDate.getDate() - 1);
            }
            
            $('#edit_end_date').val(endDate.toISOString().split('T')[0]);
        }
    });
});
</script>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit CCS Rule</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editForm" action="../controller/c_ccs_rules.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <!-- Project -->
                    <div class="form-group">
                        <label>Project</label>
                        <select class="form-control select2" name="project" id="edit_project" required>
                            <?php
                            $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . htmlspecialchars($row['project_name']) . "'>" . 
                                     htmlspecialchars($row['project_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Case Chronology -->
                    <div class="form-group">
                        <label>Case Chronology</label>
                        <textarea class="form-control" name="case_chronology" id="edit_case_chronology" rows="3"></textarea>
                    </div>

                    <!-- CCS Rule -->
                    <div class="form-group">
                        <label>CCS Rule</label>
                        <select class="form-control" name="ccs_rule" id="edit_ccs_rule" required>
                            <option value="WR1">Written Reminder 1</option>
                            <option value="WR2">Written Reminder 2</option>
                            <option value="WR3">Written Reminder 3</option>
                            <option value="WL1">Warning Letter 1</option>
                            <option value="WL2">Warning Letter 2</option>
                            <option value="WL3">Warning Letter 3</option>
                            <option value="FLW">First & Last Warning Letter</option>
                        </select>
                    </div>

                    <!-- Effective Date -->
                    <div class="form-group">
                        <label>Effective Date</label>
                        <input type="date" class="form-control" name="effective_date" id="edit_effective_date" required>
                    </div>

                    <!-- Add End Date (Read-only) -->
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control" id="edit_end_date" readonly>
                        <small class="form-text text-muted">End date is automatically calculated based on CCS Rule type</small>
                    </div>

                    <!-- Supporting Document -->
                    <div class="form-group">
                        <label>Supporting Document (Optional)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="document" id="edit_document" accept=".pdf,.xlsx,.xls">
                            <label class="custom-file-label">Choose file</label>
                        </div>
                        <small class="form-text text-muted">Leave empty to keep existing document</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../main_navbar.php';
?>

<style>
/* Add to your existing styles */
.select2-container--bootstrap4.select2-container--focus .select2-selection {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.filter-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filter-section label {
    font-weight: 500;
    color: #555;
    margin-bottom: 0.3rem;
}

.filter-section .select2-container--bootstrap4 .select2-selection {
    border-radius: 4px;
}

#clearFilters {
    margin-top: 10px;
}

#nameSearch {
    height: calc(2.25rem + 2px);
}

.input-group-text {
    background-color: #fff;
    border-left: none;
}

#nameSearch {
    border-right: none;
}

.dataTables_scroll {
    margin-bottom: 15px;
}

.dataTables_scrollHead {
    background: white;
    position: sticky !important;
    top: 0;
    z-index: 1;
}

.dataTables_scrollBody {
    position: relative;
}

.table thead th {
    position: sticky;
    top: 0;
    background: white;
    z-index: 1;
}

.table-responsive {
    overflow-x: visible;  /* Allow horizontal scroll when needed */
}

/* Ensure proper spacing for info and pagination */
.dataTables_info {
    padding-top: 0.5em;
}

.dataTables_paginate {
    padding-top: 0.5em;
}
</style>
