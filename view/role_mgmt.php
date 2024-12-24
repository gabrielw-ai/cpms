<?php
$page_title = "Role Management";
ob_start();
require_once '../controller/conn.php';
require_once '../controller/c_role_mgmt.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Role Management</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addRoleModal">
                <i class="fas fa-plus mr-2"></i>Add Role
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php
        // Display success/error messages
        if (isset($_GET['success'])) {
            $message = '';
            switch ($_GET['success']) {
                case 'added': $message = 'Role added successfully'; break;
                case 'updated': $message = 'Role updated successfully'; break;
                case 'deleted': $message = 'Role deleted successfully'; break;
            }
            echo "<div class='alert alert-success'>{$message}</div>";
        }
        if (isset($_GET['error'])) {
            echo "<div class='alert alert-danger'>{$_GET['error']}</div>";
        }
        ?>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Actions</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $roles = getAllRoles($conn);
                    foreach ($roles as $role) {
                        echo "<tr>";
                        echo "<td class='text-nowrap'>
                                <button type='button' class='btn btn-sm btn-info' onclick='editRole(this)'
                                        data-id='" . htmlspecialchars($role['id']) . "'
                                        data-role='" . htmlspecialchars($role['role']) . "'>
                                    <i class='fas fa-edit'></i>
                                </button>
                                <button type='button' class='btn btn-sm btn-danger' onclick='deleteRole(" . htmlspecialchars($role['id']) . ")'>
                                    <i class='fas fa-trash'></i>
                                </button>
                              </td>";
                        echo "<td>" . htmlspecialchars($role['role']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="../controller/c_role_mgmt.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Role Name</label>
                        <input type="text" class="form-control" name="role" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="../controller/c_role_mgmt.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Role Name</label>
                        <input type="text" class="form-control" name="role" id="edit_role" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Role Form -->
<form id="deleteForm" action="../controller/c_role_mgmt.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function editRole(button) {
    const data = button.dataset;
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_role').value = data.role;
    $('#editRoleModal').modal('show');
}

function deleteRole(id) {
    if (confirm('Are you sure you want to delete this role?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Initialize DataTable
$(document).ready(function() {
    $('.table').DataTable({
        "responsive": true,
        "order": [[1, "asc"]], // Sort by role name by default
        "pageLength": 10
    });
});
</script>

<?php
$content = ob_get_clean();
require_once '../main_navbar.php';
?>
