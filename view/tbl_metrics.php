<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$page_title = "Add KPI";

// Include config instead of helpers
require_once dirname(__DIR__) . '/config.php';

// Include the database connection with function redeclaration protection
$controller_path = realpath(dirname(__DIR__) . '/controller/conn.php');
if (!file_exists($controller_path)) {
    die("Cannot find database connection file at: " . $controller_path);
}

// Include only if closeConnection isn't already defined
if (!function_exists('closeConnection')) {
    global $conn;
    include $controller_path;
} else {
    // If function exists, just make sure we have access to $conn
    global $conn;
}

// Verify connection is established with more detailed error reporting
if (!isset($conn)) {
    $error_message = "Database connection failed in tbl_metrics.php\n";
    $error_message .= "Path used: " . $controller_path . "\n";
    $error_message .= "Current file: " . __FILE__ . "\n";
    $error_message .= "Variables available: " . implode(', ', array_keys(get_defined_vars()));
    
    error_log($error_message);
    die("Database connection failed - check error log for details");
}

// Test the connection explicitly
try {
    $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS);
} catch (PDOException $e) {
    error_log("Database connection test failed: " . $e->getMessage());
    die("Database connection test failed");
}

// Add required CSS and JS in the correct order
$additional_css = '
<link rel="stylesheet" href="' . getAssetUrl('plugins/select2/css/select2.min.css') . '">
<link rel="stylesheet" href="' . getAssetUrl('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . getAssetUrl('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<style>
    .floating-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 250px;
        max-width: 350px;
        animation: slideIn 0.5s ease-in-out;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: none;
    }

    /* Ensure alerts are always on top */
    .alert {
        margin-bottom: 1rem;
        border: none;
        border-radius: 4px;
    }

    .alert-success {
        background-color: #28a745;
        color: #fff;
    }

    .alert-danger {
        background-color: #dc3545;
        color: #fff;
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

    /* Ensure alert close button is visible */
    .alert .close {
        color: inherit;
        opacity: 0.8;
    }

    .alert .close:hover {
        opacity: 1;
    }

    .modal-body {
        position: relative;
        min-height: 100px;
    }

    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    body.modal-open {
        overflow: auto !important;
    }
    
    .modal-backdrop {
        opacity: 0.5;
    }
    
    .modal-backdrop.fade.show {
        opacity: 0.5;
    }
    
    .modal.fade.show {
        background-color: rgba(0, 0, 0, 0.5);
    }

    /* Remove select arrow and make styling consistent */
    select.form-control {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: none !important;
        padding-right: 12px !important; /* Same padding as other inputs */
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

    .btn:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }
</style>
';

$additional_js = '
<script src="' . getAssetUrl('plugins/jquery/jquery.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/jquery-ui/jquery-ui.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/bootstrap/js/bootstrap.bundle.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/select2/js/select2.full.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/datatables/jquery.dataTables.min.js') . '"></script>
<script src="' . getAssetUrl('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') . '"></script>
';

// Define allowed roles and check permissions
$allowed_roles = ['Operational Manager', 'Unit Manager', 'MIS Analyst', 'TQA Manager', 'General Manager', 'Sr. Manager', 'Super_User'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    $_SESSION['error'] = "Access Denied. You don't have permission to access this page.";
    header('Location: ' . Router::url('dashboard'));
    exit;
}

// Start capturing content
ob_start();
?>

<div class="row">
    <div class="col-12">
        <!-- Create New KPI Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create New KPI</h3>
            </div>
            <div class="card-body">
                <!-- Form -->
                <form action="../controller/c_tbl_metrics.php" method="POST">
                    <div class="form-group">
                        <label>Project</label>
                        <select class="form-control select2" name="project" required>
                            <option value="">Project</option>
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
                    <div class="form-group">
                        <label>Queue</label>
                        <input type="text" class="form-control" name="queue" required>
                    </div>
                    <div class="form-group">
                        <label>KPI Metrics</label>
                        <input type="text" class="form-control" name="kpi_metrics" required>
                    </div>
                    <div class="form-group">
                        <label>Target</label>
                        <input type="number" class="form-control" name="target" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Target Type</label>
                        <select class="form-control" name="target_type" required>
                            <option value="percentage">Percentage</option>
                            <option value="number">Number</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create KPI</button>
                </form>
            </div>
        </div>

        <!-- KPI Summary Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">KPI Summary</h3>
                <div class="card-tools">
                    <button type="button" id="exportKPISummary" class="btn btn-sm btn-success mr-1" disabled>
                        <i class="fas fa-download mr-1"></i> Export
                    </button>
                    <button type="button" id="importKPISummary" class="btn btn-sm btn-warning mr-1" 
                            data-toggle="modal" data-target="#importSummaryModal" disabled>
                        <i class="fas fa-upload mr-1"></i> Import
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Select Project</label>
                    <select class="form-control select2" id="summaryProject">
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

                <div class="table-responsive">
                    <table id="kpiSummaryTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Queue</th>
                                <th>KPI Metrics</th>
                                <th>Target</th>
                                <th>Target Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit KPI Modal -->
<div class="modal fade" id="editKPIModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit KPI</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="editKPIForm">
                <input type="hidden" id="editKPIId" name="id">
                <input type="hidden" id="original_queue" name="original_queue">
                <input type="hidden" id="original_kpi_metrics" name="original_kpi_metrics">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Queue</label>
                        <input type="text" class="form-control" id="editQueue" name="queue" required>
                    </div>
                    <div class="form-group">
                        <label>KPI Metrics</label>
                        <input type="text" class="form-control" id="editKPIMetrics" name="kpi_metrics" required>
                    </div>
                    <div class="form-group">
                        <label>Target</label>
                        <input type="number" class="form-control" id="editTarget" name="target" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Target Type</label>
                        <select class="form-control" id="editTargetType" name="target_type" required>
                            <option value="percentage">Percentage</option>
                            <option value="number">Number</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update KPI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importSummaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import KPI Data</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="importSummaryForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="importProject" name="project">
                    <div class="form-group">
                        <label>Select Excel File</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file" accept=".xlsx,.xls" required>
                            <label class="custom-file-label">Choose file</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="../controller/c_export_kpi_summary.php?template=1" class="btn btn-info">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Initially disable export and import buttons
    $('#exportKPISummary').prop('disabled', true);
    $('#importKPISummary').prop('disabled', true);

    // Initialize DataTable with simpler configuration first
    var kpiTable = $('#kpiSummaryTable').DataTable({
        processing: true,
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        },
        columns: [
            { data: 'queue' },
            { data: 'kpi_metrics' },
            { data: 'target' },
            { data: 'target_type' },
            { 
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return '<button class="btn btn-sm btn-info edit-kpi" ' +
                           'data-id="' + row.id + '" ' +
                           'data-queue="' + row.queue + '" ' +
                           'data-kpi-metrics="' + row.kpi_metrics + '" ' +
                           'data-target="' + row.target + '" ' +
                           'data-target-type="' + row.target_type + '">' +
                           '<i class="fas fa-edit"></i></button>';
                }
            }
        ]
    });

    // Add this after your DataTable initialization
    $('#kpiSummaryTable').on('click', '.edit-kpi', function() {
        var button = $(this);
        var id = button.data('id');
        var queue = button.data('queue');
        var kpiMetrics = button.data('kpi-metrics');
        var target = button.data('target');
        var targetType = button.data('target-type');

        // Fill the edit modal with data
        $('#editQueue').val(queue);
        $('#editKPIMetrics').val(kpiMetrics);
        $('#editTarget').val(target);
        $('#editTargetType').val(targetType);
        $('#editKPIId').val(id);
        $('#original_queue').val(queue);
        $('#original_kpi_metrics').val(kpiMetrics);

        // Show the modal
        $('#editKPIModal').modal('show');
    });

    // Handle edit form submission
    $('#editKPIForm').on('submit', function(e) {
        e.preventDefault();
        var project = $('#summaryProject').val();
        
        if (!project) {
            showNotification('Please select a project first', 'error');
            return;
        }

        var formData = new FormData(this);
        formData.append('project', project);
        formData.append('table_name', 'kpi_' + project.replace(/[^a-zA-Z0-9_]/g, '_').toLowerCase());

        $.ajax({
            url: '../controller/c_viewer_update.php',
            type: 'POST',
            data: Object.fromEntries(formData),
            success: function(response) {
                console.log('Update response:', response);
                if (response.success) {
                    // Properly close modal and clean up
                    $('#editKPIModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                    
                    // Refresh data and show notification
                    $('#summaryProject').trigger('change');
                    showNotification('KPI edited successfully', 'success');
                } else {
                    showNotification(response.error || 'Failed to update KPI', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Update error:', error);
                console.error('Response:', xhr.responseText);
                showNotification('Error updating KPI: ' + error, 'error');
            }
        });
    });

    // Add modal hidden event handler
    $('#editKPIModal').on('hidden.bs.modal', function() {
        // Reset form when modal is closed
        $('#editKPIForm')[0].reset();
        // Clean up any remaining backdrop
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    });

    // Handle project selection
    $('#summaryProject').on('change', function() {
        const selectedProject = $(this).val();
        
        // Enable/disable buttons based on project selection
        $('#exportKPISummary').prop('disabled', !selectedProject);
        $('#importKPISummary').prop('disabled', !selectedProject);
        
        if (!selectedProject) {
            kpiTable.clear().draw();
            return;
        }

        // Load KPI data
        $.ajax({
            url: '../controller/get_kpi_summary.php',
            type: 'GET',
            data: { project: selectedProject },
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);
                if (response.success && response.kpis) {
                    kpiTable.clear().rows.add(response.kpis).draw();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    });

    // Export button handler
    $('#exportKPISummary').on('click', function() {
        var project = $('#summaryProject').val();
        if (!project) {
            showNotification('Please select a project first', 'error');
            return;
        }

        // Redirect to export controller
        window.location.href = '../controller/c_export_kpi_summary.php?project=' + encodeURIComponent(project);
    });

    // Import modal and form handling
    $('#importSummaryModal').on('shown.bs.modal', function() {
        var project = $('#summaryProject').val();
        if (!project) {
            showNotification('Please select a project first', 'error');
            $('#importSummaryModal').modal('hide');
            return;
        }
        // Set the project value in the import form
        $('#importProject').val(project);
    });

    // Import form submission
    $('#importSummaryForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var project = $('#summaryProject').val();
        var form = this;
        
        $.ajax({
            url: '../controller/c_import_kpi_summary.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Reset form and hide modal
                    form.reset();
                    $('.custom-file-label').html('Choose file');
                    
                    // Properly close modal and remove backdrop
                    $('#importSummaryModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                    
                    // Immediately refresh table data
                    $.ajax({
                        url: '../controller/get_kpi_summary.php',
                        type: 'GET',
                        data: { project: project },
                        dataType: 'json',
                        success: function(data) {
                            if (data.success && data.kpis) {
                                $('#kpiSummaryTable').DataTable()
                                    .clear()
                                    .rows.add(data.kpis)
                                    .draw();
                                showNotification('KPI data imported successfully', 'success');
                            }
                        }
                    });
                } else {
                    showNotification(response.error || 'Import failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error importing data: ' + error, 'error');
            }
        });
    });

    // Add modal hidden event handler
    $('#importSummaryModal').on('hidden.bs.modal', function() {
        // Reset form and button state when modal is closed
        var form = $('#importSummaryForm')[0];
        var submitBtn = $(form).find('button[type="submit"]');
        form.reset();
        $('.custom-file-label').html('Choose file');
        submitBtn.prop('disabled', false).html('Import');
    });

    // Update the refreshKPITable function
    function refreshKPITable(project) {
        if (!project) return;
        
        var table = $('#kpiSummaryTable').DataTable();
        table.processing(true);
        
        $.ajax({
            url: '../controller/get_kpi_summary.php',
            type: 'GET',
            data: { project: project },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.kpis) {
                    table.clear().rows.add(response.kpis).draw();
                }
            },
            complete: function() {
                table.processing(false);
            }
        });
    }

    // File input change handler
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choose file');
    });

    // Handle form submission
    $('form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                // Clear form
                $('form')[0].reset();
                $('.select2').val('').trigger('change');
                
                // Show success message
                showNotification('KPI added successfully', 'success');
                
                // Refresh KPI table if it exists
                if ($('#summaryProject').val()) {
                    $('#summaryProject').trigger('change');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error adding KPI: ' + error, 'error');
            }
        });
    });
});

// Update showNotification function
function showNotification(message, type = 'success') {
    // Remove any existing notifications
    $('.floating-alert').remove();
    
    // Create the notification element
    const alert = $('<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show floating-alert">' +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        message +
        '</div>');

    // Add to body
    $('body').append(alert);

    // Auto dismiss after 3 seconds
    setTimeout(function() {
        alert.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}
</script>

<?php
// Store the buffered content
$content = ob_get_clean();
?>
