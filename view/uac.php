<?php
// Change session_start() to check if session is already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "User Access Control";
ob_start();
require_once '../controller/conn.php';
require_once '../controller/c_uac.php';

// Check if user has Super_User role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Super_User') {
    $_SESSION['error'] = "Access Denied. Super User privileges required.";
    header('Location: ../index.php');
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">User Access Control Management</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUACModal">
                <i class="fas fa-plus mr-2"></i>Add New Access Control
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Notifications -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="table-responsive">
            <table id="uacTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Role Name</th>
                        <th>Menu Access</th>
                        <th>Read</th>
                        <th>Write</th>
                        <th>Delete</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Updated By</th>
                        <th>Updated At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $uacEntries = getAllUAC($conn);
                    foreach ($uacEntries as $uac) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($uac['role_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($uac['menu_access']) . "</td>";
                        echo "<td><span class='badge badge-" . ($uac['read'] === '1' ? 'success' : 'danger') . "'>" 
                             . ($uac['read'] === '1' ? 'Yes' : 'No') . "</span></td>";
                        echo "<td><span class='badge badge-" . ($uac['write'] === '1' ? 'success' : 'danger') . "'>" 
                             . ($uac['write'] === '1' ? 'Yes' : 'No') . "</span></td>";
                        echo "<td><span class='badge badge-" . ($uac['delete'] === '1' ? 'success' : 'danger') . "'>" 
                             . ($uac['delete'] === '1' ? 'Yes' : 'No') . "</span></td>";
                        echo "<td>" . htmlspecialchars($uac['created_by']) . "</td>";
                        echo "<td>" . htmlspecialchars($uac['created_at']) . "</td>";
                        echo "<td>" . htmlspecialchars($uac['updated_by']) . "</td>";
                        echo "<td>" . htmlspecialchars($uac['updated_at']) . "</td>";
                        echo "<td>
                                <button type='button' class='btn btn-sm btn-primary' onclick='editUAC(this)' 
                                        data-id='" . $uac['id'] . "'
                                        data-read='" . $uac['read'] . "'
                                        data-write='" . $uac['write'] . "'
                                        data-delete='" . $uac['delete'] . "'>
                                    <i class='fas fa-edit'></i>
                                </button>
                                <button type='button' class='btn btn-sm btn-danger' onclick='deleteUAC(" . $uac['id'] . ")'>
                                    <i class='fas fa-trash'></i>
                                </button>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add UAC Modal -->
<div class="modal fade" id="addUACModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Access Control</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="../controller/c_uac.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <!-- Role Selection -->
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control select2" name="role_name" required>
                            <option value="">Select Role</option>
                            <?php
                            $stmt = $conn->query("SELECT role FROM role_mgmt ORDER BY role");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . htmlspecialchars($row['role']) . "'>" . 
                                     htmlspecialchars($row['role']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Menu Access -->
                    <div class="form-group">
                        <label>Menu Access</label>
                        <select class="form-control select2" name="menu_access[]" multiple required>
                            <!-- KPI Management -->
                            <option value="kpi_metrics">KPI Metrics</option>
                            <option value="kpi_viewer">KPI Viewer</option>
                            <option value="chart_generator">Chart Generator</option>
                            
                            <!-- Employee Management -->
                            <option value="employee_list">Employee List</option>
                            
                            <!-- CCS Rules -->
                            <option value="add_ccs_rules">Add CCS Rules</option>
                            <option value="ccs_viewer">CCS Rules Viewer</option>
                            
                            <!-- Other Management -->
                            <option value="project_namelist">Project Namelist</option>
                            <option value="role_management">Role Management</option>
                        </select>
                        <small class="form-text text-muted">You can select multiple menus</small>
                    </div>

                    <!-- Read Access -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="read" name="read" value="1">
                            <label class="custom-control-label" for="read">Read</label>
                        </div>
                    </div>

                    <!-- Write Access -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="write" name="write" value="1">
                            <label class="custom-control-label" for="write">Write</label>
                        </div>
                    </div>

                    <!-- Delete Access -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="delete" name="delete" value="1">
                            <label class="custom-control-label" for="delete">Delete</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Access Control</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit UAC Modal -->
<div class="modal fade" id="editUACModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Access Control</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="../controller/c_uac.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <!-- Role Selection -->
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control select2" name="role_name" id="edit_role_name" required>
                            <?php
                            $stmt = $conn->query("SELECT role FROM role_mgmt ORDER BY role");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . htmlspecialchars($row['role']) . "'>" . 
                                     htmlspecialchars($row['role']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Menu Access -->
                    <div class="form-group">
                        <label>Menu Access</label>
                        <select class="form-control select2" name="menu_access[]" id="edit_menu_access" multiple required>
                            <!-- KPI Management -->
                            <option value="kpi_metrics">KPI Metrics</option>
                            <option value="kpi_viewer">KPI Viewer</option>
                            <option value="chart_generator">Chart Generator</option>
                            
                            <!-- Employee Management -->
                            <option value="employee_list">Employee List</option>
                            
                            <!-- CCS Rules -->
                            <option value="add_ccs_rules">Add CCS Rules</option>
                            <option value="ccs_viewer">CCS Rules Viewer</option>
                            
                            <!-- Other Management -->
                            <option value="project_namelist">Project Namelist</option>
                            <option value="role_management">Role Management</option>
                        </select>
                    </div>

                    <!-- Read Access -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_read" name="read" value="1">
                            <label class="custom-control-label" for="edit_read">Read</label>
                        </div>
                    </div>

                    <!-- Write Access -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_write" name="write" value="1">
                            <label class="custom-control-label" for="edit_write">Write</label>
                        </div>
                    </div>

                    <!-- Delete Access -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_delete" name="delete" value="1">
                            <label class="custom-control-label" for="edit_delete">Delete</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Access Control</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../main_navbar.php';
?>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Initialize DataTable
    $('#uacTable').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false
    });
});

function editUAC(btn) {
    const id = $(btn).data('id');
    const read = $(btn).data('read');
    const write = $(btn).data('write');
    const deleteAccess = $(btn).data('delete');
    
    // Get other data from the row
    const row = $(btn).closest('tr');
    const roleName = row.find('td:eq(0)').text();
    const menuAccess = JSON.parse(row.find('td:eq(1)').text());
    
    // Fill the edit form
    $('#edit_id').val(id);
    $('#edit_role_name').val(roleName).trigger('change');
    $('#edit_menu_access').val(menuAccess).trigger('change');
    $('#edit_read').prop('checked', read === '1');
    $('#edit_write').prop('checked', write === '1');
    $('#edit_delete').prop('checked', deleteAccess === '1');
    
    // Show modal
    $('#editUACModal').modal('show');
}

function deleteUAC(id) {
    if (confirm('Are you sure you want to delete this access control?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controller/c_uac.php';

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
</script>
