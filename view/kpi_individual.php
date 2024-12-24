<?php
session_start();
$page_title = "Individual KPI";
ob_start();
require_once '../controller/conn.php';

// Add required CSS
$additional_css = '
<link rel="stylesheet" href="../adminlte/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<style>
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-tools {
    margin-left: auto;
}

.btn-tool {
    padding: .25rem .5rem;
    font-size: .875rem;
    background: transparent;
    color: #939ba2;
}

.btn-tool:hover {
    color: #2d3238;
}

/* Add notification styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px;
    border-radius: 4px;
    color: #fff;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.notification.show {
    opacity: 1;
}

.notification.success {
    background-color: #28a745;
}

.notification.error {
    background-color: #dc3545;
}
</style>
';

// Add required JS
$additional_js = <<<'EOT'
<script src="../adminlte/plugins/select2/js/select2.full.min.js"></script>
<script src="../adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $(".select2").select2({
        theme: "bootstrap4",
        width: "100%",
        placeholder: "Select options"
    });

    // Initialize staging table
    let stagingTable = $("#stagingTable").DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        processing: true,
        language: {
            emptyTable: "No data available",
            loadingRecords: "Loading...",
            processing: "Processing...",
            zeroRecords: "No matching records found"
        },
        columns: [
            { data: "NIK" },
            { data: "employee_name" },
            { data: "kpi_metrics" },
            { data: "queue" },
            { data: "january", defaultContent: "-" },
            { data: "february", defaultContent: "-" },
            { data: "march", defaultContent: "-" },
            { data: "april", defaultContent: "-" },
            { data: "may", defaultContent: "-" },
            { data: "june", defaultContent: "-" },
            { data: "july", defaultContent: "-" },
            { data: "august", defaultContent: "-" },
            { data: "september", defaultContent: "-" },
            { data: "october", defaultContent: "-" },
            { data: "november", defaultContent: "-" },
            { data: "december", defaultContent: "-" }
        ],
        data: [] // Start with empty data
    });

    // Handle KPI Metrics change
    $("#kpiMetrics").on("change", function() {
        console.log("Selected KPI:", $(this).val());
    });

    // Handle Queue change
    $("#queue").on("change", function() {
        console.log("Selected Queue:", $(this).val());
    });

    // Update the Add button click handler
    $("#addKPI").click(function() {
        const project = $("#project").val();
        const kpiMetrics = $("#kpiMetrics").val();
        const queues = $("#queue").val();
        
        if (!project || !kpiMetrics?.length || !queues?.length) {
            showNotification("Please select Project, KPI Metrics and Queues first", 'error');
            return false;
        }
        
        // Fetch employees for selected project
        fetch(`../controller/get_project_employees.php?project=${encodeURIComponent(project)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const employeeSelect = $("#employeeSelect");
                    employeeSelect.empty().append('<option value="">Select Employee</option>');
                    
                    data.data.forEach(emp => {
                        employeeSelect.append(`
                            <option value="${emp.NIK}" 
                                    data-name="${emp.employee_name}">
                                ${emp.NIK} - ${emp.employee_name}
                            </option>
                        `);
                    });
                    
                    // Initialize or refresh Select2
                    employeeSelect.select2({
                        dropdownParent: $('#addKPIModal'),
                        theme: "bootstrap4",
                        width: '100%'
                    });
                    
                    // Store selected KPIs and queues in hidden fields as JSON
                    $("#modalKPIMetrics").val(JSON.stringify(kpiMetrics));
                    $("#modalQueue").val(JSON.stringify(queues));
                    
                    // Show modal
                    $("#addKPIModal").modal('show');
                } else {
                    showNotification('No employees found for selected project', 'error');
                }
            })
            .catch(error => {
                console.error("Error:", error);
                showNotification('Error loading employees', 'error');
            });
    });

    // Handle employee selection
    $("#employeeSelect").on('change', function() {
        const selected = $(this).find('option:selected');
        $("#selectedNIK").val(selected.val());
        $("#selectedName").val(selected.data('name'));
    });

    // Add this function at the start of your JavaScript
    function showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Add to body
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Update the form submission handler
    $("#addKPIForm").submit(function(e) {
        e.preventDefault();
        
        const project = $("#project").val();
        const kpiMetrics = JSON.parse($("#modalKPIMetrics").val());
        const queues = JSON.parse($("#modalQueue").val());
        const nik = $("#selectedNIK").val();
        const name = $("#selectedName").val();
        const month = $("select[name='month']").val();
        const value = $("input[name='value']").val();
        
        // Create an array of all combinations
        const combinations = [];
        kpiMetrics.forEach(kpi => {
            queues.forEach(queue => {
                combinations.push({
                    nik: nik,
                    name: name,
                    kpi_metrics: kpi,
                    queue: queue,
                    month: month,
                    value: value
                });
            });
        });
        
        // Send all combinations to server
        fetch("../controller/c_kpi_individual.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_multiple',
                project: project,
                data: combinations
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('KPI data has been added successfully');
                $("#addKPIModal").modal("hide");
                $(this).trigger("reset");
                refreshStagingTable();
            } else {
                showNotification(data.error || 'Failed to add KPI data', 'error');
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showNotification('An unexpected error occurred', 'error');
        });
    });

    // Function to refresh staging table
    function refreshStagingTable() {
        const project = $("#project").val();
        const kpiMetrics = $("#kpiMetrics").val();
        const queues = $("#queue").val();
        
        if (!project || !kpiMetrics?.length || !queues?.length) {
            stagingTable.clear().draw();
            return;
        }
        
        const requestData = {
            action: 'get_data',
            project: project,
            kpi_metrics: kpiMetrics,
            queues: queues
        };
        
        fetch("../controller/c_kpi_individual.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                stagingTable.clear();
                if (data.data && data.data.length > 0) {
                    stagingTable.rows.add(data.data).draw();
                }
            } else {
                throw new Error(data.error || 'Failed to load data');
            }
        })
        .catch(error => {
            console.error("Error refreshing table:", error);
            showNotification('Error refreshing data', 'error');
        });
    }

    // Initial load of staging data
    refreshStagingTable();

    // Update the Process button click handler
    $("#processKPI").click(function() {
        const project = $("#project").val();
        const kpiMetrics = $("#kpiMetrics").val();
        const queues = $("#queue").val();
        
        if (!project || !kpiMetrics?.length || !queues?.length) {
            showNotification('Please select Project, KPI Metrics and Queue', 'error');
            return;
        }

        // Show loading state
        stagingTable.clear().draw();
        $("#stagingTable tbody").html('<tr><td colspan="16" class="text-center">Loading...</td></tr>');

        const requestData = {
            action: 'get_data',
            project: project,
            kpi_metrics: kpiMetrics,
            queues: queues
        };

        console.log('Sending request with data:', requestData);

        // Fetch data with filters
        fetch("../controller/c_kpi_individual.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Received data:', data);
            if (data.success) {
                stagingTable.clear();
                if (data.data && data.data.length > 0) {
                    stagingTable.rows.add(data.data).draw();
                    showNotification('Data loaded successfully');
                } else {
                    showNotification('No data found for selected criteria', 'error');
                }
            } else {
                throw new Error(data.error || 'Failed to load data');
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showNotification('An unexpected error occurred: ' + error.message, 'error');
        });
    });

    // Initialize metrics table
    $("#metricsTable").DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10
    });

    // Handle view metrics button click
    $(document).on('click', '.view-metrics', function() {
        const project = $(this).data('project');
        const kpiMetric = $(this).data('kpi');
        const queue = $(this).data('queue');
        
        // Set the project first
        $("#project").val(project).trigger('change');
        
        // Wait for KPI metrics to load
        setTimeout(() => {
            // Set KPI metrics
            $("#kpiMetrics").val(kpiMetric).trigger('change');
            
            // Wait for queues to load
            setTimeout(() => {
                // Set queue
                $("#queue").val(queue).trigger('change');
                
                // Trigger the process button
                $("#processKPI").click();
                
                // Scroll to the staging table
                $('html, body').animate({
                    scrollTop: $("#stagingTable").offset().top - 100
                }, 500);
            }, 500);
        }, 500);
    });

    // Update the project change handler
    $("#project").on("change", function() {
        const project = $(this).val();
        const kpiSelect = $("#kpiMetrics");
        const queueSelect = $("#queue");
        
        // Reset and disable KPI metrics and queue dropdowns
        kpiSelect.val(null).trigger('change').prop('disabled', true);
        queueSelect.val(null).trigger('change').prop('disabled', true);
        
        if (!project) {
            kpiSelect.empty().append('<option value="">Select Project First</option>');
            queueSelect.empty().append('<option value="">Select KPI Metrics First</option>');
            return;
        }

        // Fetch KPI metrics for selected project
        fetch(`../controller/get_project_kpi.php?project=${encodeURIComponent(project)}`)
            .then(response => response.json())
            .then(data => {
                kpiSelect.empty();
                
                if (data.success && data.metrics?.length) {
                    data.metrics.forEach(metric => {
                        kpiSelect.append(`<option value="${metric}">${metric}</option>`);
                    });
                    kpiSelect.prop('disabled', false);
                } else {
                    kpiSelect.append('<option value="">No KPI metrics found</option>');
                }
            })
            .catch(error => {
                console.error("Error:", error);
                kpiSelect.empty().append('<option value="">Error loading KPI metrics</option>');
            });

        // Reset queue dropdown
        queueSelect.empty().append('<option value="">Select KPI Metrics First</option>');
    });

    // Update the KPI metrics change handler
    $("#kpiMetrics").on("change", function() {
        const project = $("#project").val();
        const selectedMetrics = $(this).val();
        const queueSelect = $("#queue");
        
        if (!project || !selectedMetrics?.length) {
            queueSelect.prop("disabled", true)
                .empty()
                .append('<option value="">Select KPI Metrics First</option>');
            return;
        }
        
        // Enable queue dropdown and show loading state
        queueSelect.prop("disabled", true)
            .empty()
            .append('<option value="">Loading queues...</option>');
        
        // Fetch queues for selected KPI metrics
        fetch(`../controller/get_project_queues.php?project=${encodeURIComponent(project)}&kpi=${encodeURIComponent(JSON.stringify(selectedMetrics))}`)
            .then(response => response.json())
            .then(data => {
                queueSelect.empty();
                
                if (data.success && data.queues?.length) {
                    queueSelect.append('<option value="">Select Queue</option>');
                    data.queues.forEach(queue => {
                        queueSelect.append(`<option value="${queue}">${queue}</option>`);
                    });
                    queueSelect.prop("disabled", false);
                } else {
                    queueSelect.append('<option value="">No queues available</option>');
                }
            })
            .catch(error => {
                console.error("Error:", error);
                queueSelect.empty().append('<option value="">Error loading queues</option>');
                showNotification('Error loading queues', 'error');
            });
    });

    // Handle export button click
    $("#exportKPI").click(function() {
        const project = $("#project").val();
        const kpiMetrics = $("#kpiMetrics").val();
        const queues = $("#queue").val();
        
        if (!project || !kpiMetrics?.length || !queues?.length) {
            showNotification('Please select Project and at least one KPI Metric and Queue', 'error');
            return;
        }
        
        const params = new URLSearchParams({
            project: project,
            kpi: JSON.stringify(kpiMetrics),
            queue: JSON.stringify(queues)
        });
        
        window.location.href = `../controller/c_export_kpi_individual.php?${params}`;
    });

    // Handle import form submission
    $("#importForm").submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('project', $("#project").val());
        formData.append('kpi_metrics', $("#kpiMetrics").val());
        formData.append('queue', $("#queue").val());
        
        if (!formData.get('project') || !formData.get('kpi_metrics') || !formData.get('queue')) {
            showNotification('Please select Project, KPI Metric and Queue first', 'error');
            return;
        }
        
        fetch('../controller/c_import_kpi_individual.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Data imported successfully');
                $("#importModal").modal('hide');
                refreshStagingTable();
            } else {
                showNotification(data.error || 'Import failed', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Import failed', 'error');
        });
    });

    // Update file input label when file is selected
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choose file');
    });
});
</script>
EOT;
?>

<!-- Staging Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">KPI Viewer Individual</h3>
        <div class="card-tools">
            <button type="button" id="exportKPI" class="btn btn-sm btn-success mr-1">
                <i class="fas fa-download mr-1"></i> Export
            </button>
            <button type="button" id="importKPI" class="btn btn-sm btn-warning mr-1" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-upload mr-1"></i> Import
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Add KPI and Queue selectors -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Project</label>
                    <select class="form-control select2" id="project" name="project">
                        <option value="">Select Project</option>
                        <?php
                        try {
                            $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . htmlspecialchars($row['project_name']) . "'>" . 
                                     htmlspecialchars($row['project_name']) . "</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option value=''>Error loading projects</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>KPI Metrics</label>
                    <select class="form-control select2" id="kpiMetrics" name="kpiMetrics[]" multiple="multiple" disabled>
                        <option value="">Select Project First</option>
                    </select>
                    <small class="form-text text-muted">You can select multiple metrics</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Queue</label>
                    <select class="form-control select2" id="queue" name="queue[]" multiple="multiple" disabled>
                        <option value="">Select KPI Metrics First</option>
                    </select>
                    <small class="form-text text-muted">You can select multiple queues</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="d-flex">
                        <button type="button" id="processKPI" class="btn btn-info mr-2">
                            <i class="fas fa-sync-alt mr-1"></i> Process
                        </button>
                        <button type="button" id="addKPI" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Table -->
        <div class="table-responsive">
            <table id="stagingTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Name</th>
                        <th>KPI Metrics</th>
                        <th>Queue</th>
                        <th>Jan</th>
                        <th>Feb</th>
                        <th>Mar</th>
                        <th>Apr</th>
                        <th>May</th>
                        <th>Jun</th>
                        <th>Jul</th>
                        <th>Aug</th>
                        <th>Sep</th>
                        <th>Oct</th>
                        <th>Nov</th>
                        <th>Dec</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add this new card after the existing staging card -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">KPI Metrics View</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="metricsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>KPI Metrics</th>
                        <th>Queue</th>
                        <th>Total Employees</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Get all projects first
                        $projectStmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                        $projects = $projectStmt->fetchAll(PDO::FETCH_COLUMN);

                        foreach ($projects as $project) {
                            // Get the individual monthly table name for this project
                            $tableName = "KPI_" . str_replace(" ", "_", strtoupper($project)) . "_INDIVIDUAL_MON";
                            
                            // Check if table exists
                            $tableExists = $conn->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
                            
                            if ($tableExists) {
                                // Get metrics and queues for this project
                                $stmt = $conn->query("
                                    SELECT 
                                        '$project' as project,
                                        kpi_metrics,
                                        queue,
                                        COUNT(DISTINCT NIK) as total_employees
                                    FROM `$tableName` 
                                    GROUP BY kpi_metrics, queue
                                    ORDER BY kpi_metrics, queue
                                ");
                                
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['project']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['kpi_metrics']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['queue']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['total_employees']) . "</td>";
                                    echo "<td>
                                            <button type='button' class='btn btn-sm btn-info view-metrics' 
                                                    data-project='" . htmlspecialchars($row['project']) . "'
                                                    data-kpi='" . htmlspecialchars($row['kpi_metrics']) . "' 
                                                    data-queue='" . htmlspecialchars($row['queue']) . "'>
                                                <i class='fas fa-eye'></i> View
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            }
                        }
                    } catch (PDOException $e) {
                        error_log("Error in KPI metrics view: " . $e->getMessage());
                        echo "<tr><td colspan='5' class='text-center text-danger'>Error loading metrics data</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update the Add KPI Modal -->
<div class="modal fade" id="addKPIModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Individual KPI</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addKPIForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Employee</label>
                        <select class="form-control select2" name="employee" id="employeeSelect" required>
                            <option value="">Select Employee</option>
                        </select>
                        <input type="hidden" name="nik" id="selectedNIK">
                        <input type="hidden" name="name" id="selectedName">
                    </div>
                    <div class="form-group">
                        <label>Month</label>
                        <select class="form-control" name="month" required>
                            <option value="jan">January</option>
                            <option value="feb">February</option>
                            <option value="mar">March</option>
                            <option value="apr">April</option>
                            <option value="may">May</option>
                            <option value="jun">June</option>
                            <option value="jul">July</option>
                            <option value="aug">August</option>
                            <option value="sep">September</option>
                            <option value="oct">October</option>
                            <option value="nov">November</option>
                            <option value="dec">December</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Value (%)</label>
                        <input type="number" class="form-control" name="value" min="0" max="100" step="0.01" required>
                    </div>
                    <!-- Hidden fields for KPI metrics and queue -->
                    <input type="hidden" name="kpi_metrics" id="modalKPIMetrics">
                    <input type="hidden" name="queue" id="modalQueue">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add KPI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import KPI Data</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Excel File (.xlsx)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file" accept=".xlsx" required>
                            <label class="custom-file-label">Choose file</label>
                        </div>
                        <small class="form-text text-muted">
                            Download the template <a href="../controller/c_export_kpi_individual.php?template=1">here</a>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../main_navbar.php';
?>
