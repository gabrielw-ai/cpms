<?php
session_start();
$page_title = "Individual KPI";
ob_start();
require_once dirname(__DIR__) . '/controller/conn.php';
require_once dirname(__DIR__) . '/routing.php';
global $conn;
$router = new Router();

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

.alert .close {
    color: inherit;
    opacity: 0.8;
}

.alert .close:hover {
    opacity: 1;
}
</style>
';

// Add required JS
$additional_js = <<<EOT
<script src="../adminlte/plugins/select2/js/select2.full.min.js"></script>
<script src="../adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Add base URL meta tag -->
<meta name="base-url" content="{$router->url('')}">
<!-- Include KPI Individual JS -->
<script src="../public/dist/js/kpi_individual.js"></script>
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
                        <th>nik</th>
                        <th>Name</th>
                        <th>KPI Metrics</th>
                        <th>Queue</th>
                        <th>January</th>
                        <th>February</th>
                        <th>March</th>
                        <th>April</th>
                        <th>May</th>
                        <th>June</th>
                        <th>July</th>
                        <th>August</th>
                        <th>September</th>
                        <th>October</th>
                        <th>November</th>
                        <th>December</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add KPI Modal -->
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
                        <select class="form-control" name="employee" id="employeeSelect" required>
                            <option value="">Search employee...</option>
                        </select>
                        <input type="hidden" name="nik" id="selectedNik">
                        <input type="hidden" name="name" id="selectedName">
                    </div>
                    <div class="form-group">
                        <label>KPI Metrics</label>
                        <input type="text" class="form-control" name="kpi_metrics" id="modalKPIMetrics" required>
                    </div>
                    <div class="form-group">
                        <label>Queue</label>
                        <input type="text" class="form-control" name="queue" id="modalQueue" required>
                    </div>
                    <div class="form-group">
                        <label>Month</label>
                        <select class="form-control" name="month" required>
                            <option value="january">January</option>
                            <option value="february">February</option>
                            <option value="march">March</option>
                            <option value="april">April</option>
                            <option value="may">May</option>
                            <option value="june">June</option>
                            <option value="july">July</option>
                            <option value="august">August</option>
                            <option value="september">September</option>
                            <option value="october">October</option>
                            <option value="november">November</option>
                            <option value="december">December</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Value (%)</label>
                        <input type="number" class="form-control" name="value" min="0" max="100" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add KPI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
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
?>
