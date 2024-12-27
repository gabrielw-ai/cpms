<?php
session_start();

// Include routing and database connection
require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Check if user is logged in, if not redirect to login
if (!isset($_SESSION['user_nik'])) {
    header('Location: ' . Router::url('login'));
    exit;
}

// Get user's role and project
$userRole = $_SESSION['user_role'] ?? '';
$isLimitedAccess = ($userRole === 'Agent' || $userRole === 'Team Leader');

// Get user's project if they have limited access
$userProject = null;
if ($isLimitedAccess) {
    try {
        $stmt = $conn->prepare("SELECT project FROM employee_active WHERE NIK = ?");
        $stmt->execute([$_SESSION['user_nik']]);
        $userProject = $stmt->fetchColumn();
        
        if ($userProject) {
            $tableName = 'KPI_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $userProject);
            $_GET['table'] = $tableName;
        }
    } catch (PDOException $e) {
        error_log("Error getting user project: " . $e->getMessage());
    }
}

// Set required variables for main_navbar.php
$page_title = "KPI Viewer";

// Additional CSS
$additional_css = '
<!-- Select2 -->
<link rel="stylesheet" href="../adminlte/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<!-- DataTables -->
<link rel="stylesheet" href="../adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<style>
    .select2-container--default .select2-selection--single {
        height: calc(2.25rem + 2px);
        padding: .375rem .75rem;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
    }
    .table th, .table td {
        white-space: nowrap;
        vertical-align: middle;
    }
</style>';

// Additional JavaScript
$additional_js = '
<!-- Select2 -->
<script src="../adminlte/plugins/select2/js/select2.full.min.js"></script>
<!-- DataTables -->
<script src="../adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 Elements
    $(".select2").select2({
        theme: "bootstrap4"
    });

    // Initialize DataTable
    $("#kpiTable").DataTable({
        "responsive": false,
        "autoWidth": false,
        "scrollX": true,
        "scrollCollapse": true,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [[0, "asc"], [1, "asc"]],
        "columnDefs": [
            {
                "targets": "_all",
                "defaultContent": "-"
            }
        ]
    });
});
</script>';

// Start output buffering for main content
ob_start();
?>

<!-- Only the content part, no HTML structure -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">KPI Data Viewer</h3>
        <?php if (isset($_GET['table'])): ?>
            <div class="card-tools">
                <!-- Export Button -->
                <a href="<?php echo Router::url('controller/c_export_kpi.php'); ?>?table=<?php echo urlencode($_GET['table']); ?>&view=<?php echo urlencode($_GET['view'] ?? 'weekly'); ?>" 
                   class="btn btn-success btn-sm">
                    <i class="fas fa-download"></i> Export
                </a>
                <!-- Import Button -->
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#importModal">
                    <i class="fas fa-upload"></i> Import
                </button>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form id="kpiFilterForm" method="GET">
            <div class="row mb-4">
                <!-- Project Selection -->
                <div class="col-md-6">
                    <?php if (!$isLimitedAccess): ?>
                        <div class="form-group">
                            <label>Select Project:</label>
                            <select class="form-control select2" name="table" onchange="this.form.submit()">
                                <option value="">-- Select Project --</option>
                    <?php
                    try {
                        $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $tableName = 'KPI_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $row['project_name']);
                                        $selected = (isset($_GET['table']) && $_GET['table'] === $tableName) ? 'selected' : '';
                                        // Add default view=weekly to the form submission
                                        if ($selected && !isset($_GET['view'])) {
                                            echo "<script>window.location.href = '?table=" . urlencode($tableName) . "&view=weekly';</script>";
                                        }
                                        echo "<option value='" . htmlspecialchars($tableName) . "' {$selected}>" . 
                                             htmlspecialchars($row['project_name']) . "</option>";
                        }
                    } catch (PDOException $e) {
                                    echo "<option value=''>Error loading projects</option>";
                    }
                    ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Period Selection -->
                <div class="col-md-6">
                <?php if (isset($_GET['table'])): ?>
                        <div class="form-group">
                            <label>View Period:</label>
                            <select class="form-control select2" name="view" onchange="this.form.submit()">
                                <option value="weekly" <?php echo (!isset($_GET['view']) || $_GET['view'] === 'weekly') ? 'selected' : ''; ?>>
                                    Weekly View
                                </option>
                                <option value="monthly" <?php echo (isset($_GET['view']) && $_GET['view'] === 'monthly') ? 'selected' : ''; ?>>
                                    Monthly View
                                </option>
                            </select>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </form>

        <!-- Content Area -->
        <div class="mt-4">
            <?php if (!isset($_GET['table'])): ?>
                <div class="alert alert-info">
                    <i class="icon fas fa-info-circle"></i> Please select a project to view its KPI data.
                </div>
            <?php else: ?>
                <div id="kpiDataContainer">
                    <div class="table-responsive">
                        <table id="kpiTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 100px">Actions</th>
                                    <th>Queues</th>
                                    <th>KPI Metrics</th>
                                    <th>Target</th>
                                    <?php
                                    if (isset($_GET['view'])) {
                                        if ($_GET['view'] === 'monthly') {
                                            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                                     'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                            foreach ($months as $month) {
                                                echo "<th>{$month}</th>";
                                            }
                                        } else { // weekly
                                            for ($week = 1; $week <= 52; $week++) {
                                                echo "<th>Wk " . str_pad($week, 2, '0', STR_PAD_LEFT) . "</th>";
                                            }
                                        }
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
        <?php
                                try {
                                    $tableName = $_GET['table'] ?? '';
                                    if ($tableName) {
                                        // Get view type
                                        $viewType = isset($_GET['view']) && $_GET['view'] === 'monthly' ? 'monthly' : 'weekly';
                                        
                                        // Set table names based on view type
            if ($viewType === 'monthly') {
                                            $kpiTable = $tableName . "_MON";  // Use _MON table for KPI definitions
                                            $valuesTable = $tableName . "_MON_VALUES";
                                            $periodColumn = 'month';
                } else {
                                            $kpiTable = $tableName;  // Use base table for KPI definitions
                                            $valuesTable = $tableName . "_VALUES";
                                            $periodColumn = 'week';
                                        }
                                        
                                        // Get data with JOIN - using correct tables
                                        $sql = "SELECT k.queue, k.kpi_metrics, k.target, k.target_type, 
                                               v.{$periodColumn}, v.value
                                        FROM `$kpiTable` k
                                        LEFT JOIN `{$valuesTable}` v ON k.id = v.kpi_id
                                        ORDER BY k.queue, k.kpi_metrics, v.{$periodColumn}";
                                        
                                        $stmt = $conn->query($sql);
                                        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        // Process data into rows
                                        $rowData = [];
                                        $periodCount = ($viewType === 'monthly') ? 12 : 52;
                                        
                                        foreach ($data as $row) {
                                            $kpiKey = $row['queue'] . '|' . $row['kpi_metrics'];
                                            
                                            if (!isset($rowData[$kpiKey])) {
                                                $rowData[$kpiKey] = [
                                                    'queue' => $row['queue'],
                                                    'kpi_metrics' => $row['kpi_metrics'],
                                                    'target' => $row['target'],
                                                    'target_type' => $row['target_type'],
                                                    'values' => array_fill(1, $periodCount, null)
                                                ];
                                            }
                                            
                                            // Store value using the correct period
                                            $period = $row[$periodColumn];
                                            if ($period !== null) {
                                                $rowData[$kpiKey]['values'][$period] = $row['value'];
                                            }
                                        }
                
                // Display the data
                                        foreach ($rowData as $row) {
                    echo "<tr>";
                                            echo "<td class='text-nowrap'>
                                                    <button type='button' class='btn btn-sm btn-primary edit-kpi' 
                                                            data-toggle='modal' 
                                                            data-target='#editModal'
                                data-queue='" . htmlspecialchars($row['queue']) . "'
                                                            data-kpi_metrics='" . htmlspecialchars($row['kpi_metrics']) . "'
                                                            data-kpi_target='" . htmlspecialchars($row['target']) . "'
                                                            data-target_type='" . htmlspecialchars($row['target_type']) . "'>
                                <i class='fas fa-edit'></i>
                            </button>
                                                    <button type='button' class='btn btn-sm btn-danger delete-kpi'
                                data-queue='" . htmlspecialchars($row['queue']) . "'
                                                            data-kpi_metrics='" . htmlspecialchars($row['kpi_metrics']) . "'>
                                <i class='fas fa-trash'></i>
                                                    </button>
                                                  </td>";
                                            echo "<td>" . htmlspecialchars($row['queue']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['kpi_metrics']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['target']) . 
                         ($row['target_type'] === 'percentage' ? '%' : '') . "</td>";

                                            // Write period values
                                            for ($i = 1; $i <= $periodCount; $i++) {
                                                $value = $row['values'][$i];
                                                if ($value !== null) {
                                                    if ($row['target_type'] === 'percentage') {
                                                        $value .= '%';
                                                    }
                                                    echo "<td>" . htmlspecialchars($value) . "</td>";
                    } else {
                                                    echo "<td>-</td>";
                        }
                    }
                    echo "</tr>";
                                        }
                }
            } catch (PDOException $e) {
                                    error_log("Error loading KPI data: " . $e->getMessage());
                                    echo "<tr><td colspan='15' class='text-center text-danger'>Error loading KPI data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import KPI Data</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="../controller/c_import_kpi.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($_GET['table'] ?? ''); ?>">
                    <input type="hidden" name="view_type" value="<?php echo htmlspecialchars($_GET['view'] ?? 'weekly'); ?>">
                    
                    <div class="form-group">
                        <label for="file">Select Excel File</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="file" name="file" accept=".xlsx,.xls" required>
                            <label class="custom-file-label" for="file">Choose file</label>
                        </div>
                        <small class="form-text text-muted mt-2">
                            Download the template first to ensure correct format.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="../controller/c_export_kpi.php?table=<?php echo urlencode($_GET['table'] ?? ''); ?>&template=1" 
                       class="btn btn-info">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="importKPI" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit KPI</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="../controller/c_viewer_update.php" method="POST">
            <div class="modal-body">
                    <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($_GET['table'] ?? ''); ?>">
                    <input type="hidden" name="view_type" value="<?php echo htmlspecialchars($_GET['view'] ?? 'weekly'); ?>">
                    <input type="hidden" name="original_queue" id="original_queue">
                    <input type="hidden" name="original_kpi_metrics" id="original_kpi_metrics">
                    
                    <div class="form-group">
                        <label for="queue">Queue</label>
                        <input type="text" class="form-control" id="queue" name="queue" required>
                    </div>
                    <div class="form-group">
                        <label for="kpi_metrics">KPI Metrics</label>
                        <input type="text" class="form-control" id="kpi_metrics" name="kpi_metrics" required>
                    </div>
                    <div class="form-group">
                        <label for="target">Target</label>
                        <input type="text" class="form-control" id="target" name="target" required>
                    </div>
                    <div class="form-group">
                        <label for="target_type">Target Type</label>
                        <select class="form-control" id="target_type" name="target_type" required>
                            <option value="percentage">Percentage</option>
                            <option value="number">Number</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
            </form>
        </div>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Add to $additional_js
$additional_js .= '
<!-- bs-custom-file-input -->
<script src="../adminlte/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
$(document).ready(function() {
    bsCustomFileInput.init();
    
    // Show success/error messages if they exist
    ';

if (isset($_SESSION['success'])) {
    $additional_js .= 'showNotification("' . addslashes($_SESSION['success']) . '", "success");';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $additional_js .= 'showNotification("' . addslashes($_SESSION['error']) . '", "error");';
    unset($_SESSION['error']);
}

$additional_js .= '
});

function showNotification(message, type) {
    $(document).Toasts("create", {
        class: type === "success" ? "bg-success" : "bg-danger",
        body: message,
        autohide: true,
        delay: 3000,
        hide: true,
        closeButton: false
    });
}

$(document).ready(function() {
    // Handle edit button clicks
    $(".edit-kpi").click(function() {
        var queue = $(this).data("queue");
        var kpi_metrics = $(this).data("kpi_metrics");
        var target = $(this).data("kpi_target");
        var target_type = $(this).data("target_type");
        
        $("#original_queue").val(queue);
        $("#original_kpi_metrics").val(kpi_metrics);
        $("#queue").val(queue);
        $("#kpi_metrics").val(kpi_metrics);
        $("#target").val(target);
        $("#target_type").val(target_type);
    });
    
    // Handle delete button clicks
    $(".delete-kpi").click(function() {
        if (confirm("Are you sure you want to delete this KPI?")) {
            var form = $("<form>")
                .attr("method", "POST")
                .attr("action", "../controller/c_viewer_del.php");
                
            form.append($("<input>")
                .attr("type", "hidden")
                .attr("name", "table_name")
                .val("' . htmlspecialchars($_GET['table'] ?? '') . '"));
                
            form.append($("<input>")
                .attr("type", "hidden")
                .attr("name", "queue")
                .val($(this).data("queue")));
                
            form.append($("<input>")
                .attr("type", "hidden")
                .attr("name", "kpi_metrics")
                .val($(this).data("kpi_metrics")));
            
            $("body").append(form);
            form.submit();
        }
    });
});
</script>';
?>