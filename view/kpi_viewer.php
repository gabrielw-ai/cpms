<?php
session_start();
$page_title = "KPI Viewer";
ob_start();
require_once '../controller/conn.php';

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
        
        // Automatically set the table parameter for limited access users
        if ($userProject) {
            $tableName = 'KPI_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $userProject);
            $_GET['table'] = $tableName;
        }
    } catch (PDOException $e) {
        error_log("Error getting user project: " . $e->getMessage());
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">KPI Data Viewer</h3>
    </div>
    <div class="card-body">
        <!-- Project Selection - Only show for non-limited access users -->
        <?php if (!$isLimitedAccess): ?>
        <div class="tables-list mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5>Select Project:</h5>
                    <?php
                    try {
                        $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $tableName = 'KPI_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $row['project_name']);
                            $activeClass = (isset($_GET['table']) && $_GET['table'] === $tableName) ? 'active' : '';
                            echo "<a href='?table=" . urlencode($tableName) . "&view=" . ($_GET['view'] ?? 'weekly') . "' 
                                 class='btn btn-outline-primary mr-2 mb-2 {$activeClass}'>" . 
                                 htmlspecialchars($row['project_name']) . "</a>";
                        }
                    } catch (PDOException $e) {
                        echo "Error: " . $e->getMessage();
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Weekly/Monthly View Selector - Show for all users -->
        <div class="row mb-4">
            <div class="col-md-12 text-right">
                <?php if (isset($_GET['table'])): ?>
                    <div class="btn-group" role="group">
                        <a href="?table=<?php echo urlencode(preg_replace('/_MON$/', '', $_GET['table'])); ?>&view=weekly" 
                           class="btn btn-outline-primary <?php echo (!isset($_GET['view']) || $_GET['view'] === 'weekly') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-week mr-1"></i> Weekly View
                        </a>
                        <a href="?table=<?php echo urlencode(preg_replace('/_MON$/', '', $_GET['table'])) . '_MON'; ?>&view=monthly" 
                           class="btn btn-outline-primary <?php echo (isset($_GET['view']) && $_GET['view'] === 'monthly') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt mr-1"></i> Monthly View
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Display Selected Table -->
        <?php
        if (isset($_GET['table'])) {
            $baseTableName = $_GET['table'];
            $viewType = $_GET['view'] ?? 'weekly';
            
            // Fix monthly table name to avoid duplicate _MON
            if ($viewType === 'monthly') {
                // If table doesn't end with _MON, add it
                if (!str_ends_with($baseTableName, '_MON')) {
                    $tableName = $baseTableName . '_MON';
                } else {
                    $tableName = $baseTableName;
                }
            } else {
                $tableName = $baseTableName;
            }
            
            // Get project name
            $projectName = substr($baseTableName, 4);  // Remove 'KPI_' prefix
            $projectName = str_replace('_MON', '', $projectName);  // Remove '_MON' if present
            $projectName = str_replace('_', ' ', $projectName);
            
            echo "<div class='card'>
                    <div class='card-header'>
                        <h3 class='card-title'>KPI Data for: " . htmlspecialchars($projectName) . 
                        " (" . ucfirst($viewType) . " View)</h3>
                    </div>
                    <div class='card-body table-responsive p-0'>";
            
            echo "<table class='table table-bordered table-striped table-hover table-fixed-header'>";
            echo "<thead><tr class='bg-primary'>";
            echo "<th class='fixed-column bg-primary'>Actions</th>";
            echo "<th class='fixed-column bg-primary'>Queue</th>";
            echo "<th class='fixed-column bg-primary'>KPI Metrics</th>";
            echo "<th class='fixed-column bg-primary'>Target</th>";
            
            if ($viewType === 'weekly') {
                // Week headers
                for ($i = 1; $i <= 52; $i++) {
                    $weekNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                    echo "<th class='week-header'>WK" . $weekNum . "</th>";
                }
            } else {
                // Month headers
                $months = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 
                          'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];
                foreach ($months as $month) {
                    echo "<th class='month-header'>" . substr($month, 0, 3) . "</th>";
                }
            }
            echo "</tr></thead>";
            
            echo "<tbody>";
            try {
                // Get KPI data with values
                if ($viewType === 'monthly') {
                    $sql = "SELECT k.queue, k.kpi_metrics, k.target, k.target_type, ";

                    // Add month columns dynamically
                    $monthColumns = [];
                    for ($i = 1; $i <= 12; $i++) {
                        $monthColumns[] = "MAX(CASE WHEN v.month = $i THEN v.value END) as MONTH_$i";
                    }
                    $sql .= implode(", ", $monthColumns);

                    $sql .= " FROM `$tableName` k
                             LEFT JOIN `{$tableName}_VALUES` v ON k.id = v.kpi_id
                             GROUP BY k.id, k.queue, k.kpi_metrics, k.target, k.target_type";
                } else {
                    $sql = "SELECT k.queue, k.kpi_metrics, k.target, k.target_type, ";

                    // Add week columns dynamically
                    $weekColumns = [];
                    for ($i = 1; $i <= 52; $i++) {
                        $weekNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                        $weekColumns[] = "MAX(CASE WHEN v.week = $i THEN v.value END) as WK$weekNum";
                    }
                    $sql .= implode(", ", $weekColumns);

                    $sql .= " FROM `$tableName` k
                             LEFT JOIN `{$tableName}_VALUES` v ON k.id = v.kpi_id
                             GROUP BY k.id, k.queue, k.kpi_metrics, k.target, k.target_type";
                }
                
                $stmt = $conn->query($sql);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Display the data
                foreach ($results as $row) {
                    echo "<tr>";
                    echo "<td class='fixed-column text-nowrap'>";
                    if (!$isLimitedAccess) {
                        // Only show edit/delete buttons for non-limited users
                        echo "<button type='button' class='btn btn-sm btn-info mr-1' onclick='editKPI(this)' 
                                data-queue='" . htmlspecialchars($row['queue']) . "'
                                data-kpi-metrics='" . htmlspecialchars($row['kpi_metrics']) . "'
                                data-target='" . htmlspecialchars($row['target']) . "'
                                data-target-type='" . htmlspecialchars($row['target_type']) . "'>
                                <i class='fas fa-edit'></i>
                            </button>
                            <button type='button' class='btn btn-sm btn-danger mr-1' onclick='deleteKPI(this)'
                                data-queue='" . htmlspecialchars($row['queue']) . "'
                                data-kpi-metrics='" . htmlspecialchars($row['kpi_metrics']) . "'>
                                <i class='fas fa-trash'></i>
                            </button>";
                    }
                    // Show chart button for everyone
                    echo "<button type='button' class='btn btn-sm btn-success' onclick='showChart(this)'
                            data-table='" . htmlspecialchars($tableName) . "'
                            data-kpi-metrics='" . htmlspecialchars($row['kpi_metrics']) . "'
                            data-period='" . htmlspecialchars($viewType) . "'>
                            <i class='fas fa-chart-line'></i>
                        </button>";
                    echo "</td>";
                    echo "<td class='fixed-column'>" . htmlspecialchars($row['queue']) . "</td>";
                    echo "<td class='fixed-column'><a href='kpi_individual.php?table=" . urlencode($tableName) . 
                         "&metric=" . urlencode($row['kpi_metrics']) . 
                         "&view=" . urlencode($viewType) . 
                         "&queue=" . urlencode($row['queue']) . "'>" . 
                         htmlspecialchars($row['kpi_metrics']) . "</a></td>";
                    echo "<td class='fixed-column'>" . htmlspecialchars($row['target']) . 
                         ($row['target_type'] === 'percentage' ? '%' : '') . "</td>";

                    // Display values based on view type
                    if ($viewType === 'monthly') {
                        for ($i = 1; $i <= 12; $i++) {
                            $columnName = "MONTH_" . $i;
                            $value = $row[$columnName];
                            echo "<td>" . ($value !== null ? 
                                ($row['target_type'] === 'percentage' ? $value . '%' : $value) : 
                                '') . "</td>";
                        }
                    } else {
                        for ($i = 1; $i <= 52; $i++) {
                            $weekNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $columnName = "WK" . $weekNum;
                            $value = $row[$columnName];
                            echo "<td>" . ($value !== null ? 
                                ($row['target_type'] === 'percentage' ? $value . '%' : $value) : 
                                '') . "</td>";
                        }
                    }
                    echo "</tr>";
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            echo "</tbody></table></div></div>";

            // Add Import/Export buttons - Note the proper PHP syntax
            if (!$isLimitedAccess) {
                echo "<div class='mt-4'>
                    <div class='btn-group'>
                        <form method='GET' action='../controller/c_export_kpi.php' style='display: inline-block;'>
                            <input type='hidden' name='table' value='" . htmlspecialchars($tableName) . "'>
                            <button type='submit' class='btn btn-success'>
                                <i class='fas fa-file-excel mr-2'></i>Export to Excel
                            </button>
                        </form>
                        
                        <form method='POST' action='../controller/c_import_kpi.php' enctype='multipart/form-data' 
                              class='ml-2 d-inline-flex align-items-center'>
                            <input type='hidden' name='table_name' value='" . htmlspecialchars($tableName) . "'>
                            <input type='hidden' name='view_type' value='" . htmlspecialchars($viewType) . "'>
                            <div class='custom-file' style='width: 250px;'>
                                <input type='file' class='custom-file-input' name='file' accept='.xlsx' required 
                                       id='customFile'>
                                <label class='custom-file-label' for='customFile'>Choose file</label>
                            </div>
                            <button type='submit' name='importKPI' class='btn btn-primary ml-2'>
                                <i class='fas fa-file-import mr-2'></i>Import
                            </button>
                        </form>
                    </div>
                </div>";
            }
        } else {
            echo "<div class='alert alert-info'>
                    <i class='icon fas fa-info-circle'></i> Please select a project to view its KPI data.
                  </div>";
        }
        ?>
    </div>
</div>

<style>
.table-container {
    overflow-x: auto;
    margin-top: 20px;
}
.week-header, .month-header {
    padding: 8px 4px;
    font-size: 0.85rem;
    text-align: center;
    min-width: 50px;
}
.month-header {
    min-width: 70px;
}
.btn-outline-primary.active {
    color: #fff;
}
.table th {
    white-space: nowrap;
    vertical-align: middle;
}
.custom-file-label::after {
    content: "Browse";
}
.table-fixed-header {
    position: relative;
}
.fixed-column {
    position: sticky;
    left: 0;
    background-color: #fff;
    z-index: 1;
}
.fixed-column.bg-primary {
    background-color: #007bff !important;
}
.fixed-column::after {
    content: '';
    position: absolute;
    right: -5px;
    top: 0;
    bottom: 0;
    width: 5px;
    background: linear-gradient(to right, rgba(0,0,0,0.1), rgba(0,0,0,0));
}
</style>

<script>
// Existing file input handler
document.querySelector('.custom-file-input')?.addEventListener('change', function(e) {
    var fileName = e.target.files[0].name;
    var nextSibling = e.target.nextElementSibling;
    nextSibling.innerText = fileName;
});

// Edit KPI function
function editKPI(button) {
    // Get data from button attributes
    const queue = button.getAttribute('data-queue');
    const kpiMetrics = button.getAttribute('data-kpi-metrics');
    const target = button.getAttribute('data-target');
    const targetType = button.getAttribute('data-target-type');

    // Set values in the edit modal
    document.getElementById('original_queue').value = queue;
    document.getElementById('original_kpi_metrics').value = kpiMetrics;
    document.getElementById('edit_queue').value = queue;
    document.getElementById('edit_kpi_metrics').value = kpiMetrics;
    document.getElementById('edit_target').value = target;
    document.getElementById('edit_target_type').value = targetType;

    // Show the modal
    $('#editKPIModal').modal('show');
}

// Delete KPI function
function deleteKPI(button) {
    if (confirm('Are you sure you want to delete this KPI?')) {
        // Get data from button attributes
        const queue = button.getAttribute('data-queue');
        const kpiMetrics = button.getAttribute('data-kpi-metrics');

        // Set values in the hidden delete form
        document.getElementById('delete_queue').value = queue;
        document.getElementById('delete_kpi_metrics').value = kpiMetrics;

        // Submit the delete form
        document.getElementById('deleteKPIForm').submit();
    }
}

// Initialize tooltips if you want to use them
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<!-- Edit KPI Modal -->
<div class="modal fade" id="editKPIModal" tabindex="-1" role="dialog" aria-labelledby="editKPIModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editKPIModalLabel">Edit KPI</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="../controller/c_viewer_update.php" method="POST">
                <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($baseTableName ?? ''); ?>">
                <input type="hidden" name="view_type" value="<?php echo htmlspecialchars($viewType ?? 'weekly'); ?>">
                <input type="hidden" name="original_queue" id="original_queue">
                <input type="hidden" name="original_kpi_metrics" id="original_kpi_metrics">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_queue">Queue</label>
                        <input type="text" class="form-control" id="edit_queue" name="queue" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_kpi_metrics">KPI Metrics</label>
                        <input type="text" class="form-control" id="edit_kpi_metrics" name="kpi_metrics" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_target">Target</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="edit_target" name="target" required>
                            <div class="input-group-append">
                                <select class="form-control" name="target_type" id="edit_target_type">
                                    <option value="number">Number</option>
                                    <option value="percentage">Percentage (%)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_kpi" class="btn btn-primary">Update KPI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete KPI Form (Hidden) -->
<form id="deleteKPIForm" action="../controller/c_viewer_del.php" method="POST" style="display: none;">
    <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($baseTableName ?? ''); ?>">
    <input type="hidden" name="view_type" value="<?php echo htmlspecialchars($viewType ?? 'weekly'); ?>">
    <input type="hidden" name="queue" id="delete_queue">
    <input type="hidden" name="kpi_metrics" id="delete_kpi_metrics">
    <input type="hidden" name="delete_kpi" value="1">
</form>

<!-- Chart Modal -->
<div class="modal fade" id="chartModal" tabindex="-1" role="dialog" aria-labelledby="chartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chartModalLabel">KPI Chart</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="chartContainer" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let kpiChart = null;

function showChart(button) {
    const table = button.getAttribute('data-table');
    const kpiMetric = button.getAttribute('data-kpi-metrics');
    const period = button.getAttribute('data-period');
    
    // Show modal
    $('#chartModal').modal('show');
    
    // Show loading
    const chartContainer = document.getElementById('chartContainer');
    chartContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="height:400px"><div class="spinner-border text-primary"></div></div>';
    
    fetch(`../controller/get_chart_data.php?project=${encodeURIComponent(table)}&period=${period}&metric=${encodeURIComponent(kpiMetric)}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.error);
            
            // Clear loading
            chartContainer.innerHTML = '<canvas></canvas>';
            
            // Create simple line chart
            new Chart(chartContainer.querySelector('canvas'), {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: kpiMetric,
                        data: data.values,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: kpiMetric
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
        .catch(error => {
            chartContainer.innerHTML = `<div class="alert alert-danger m-3">${error.message}</div>`;
        });
}

// Clean up when modal closes
$('#chartModal').on('hidden.bs.modal', function() {
    document.getElementById('chartContainer').innerHTML = '';
});
</script>

<?php
$content = ob_get_clean();
require_once '../main_navbar.php';
?>
