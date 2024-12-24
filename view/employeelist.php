<?php
$page_title = "Employee List";
ob_start();
require_once '../controller/conn.php';
?>

<!-- CSS -->
<link rel="stylesheet" href="../adminlte/dist/css/adminlte.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="../adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

<!-- Content -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Employee List</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display: none;">
                    <i class="fas fa-trash mr-2"></i>Delete Selected (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="employeeTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="50px">
                            <input type="checkbox" id="selectAll" class="select-all-checkbox" onclick="toggleAllCheckboxes()">
                        </th>
                        <th>NIK</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Project</th>
                        <th>Join Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    try {
                        $sql = "SELECT NIK, employee_name, employee_email, role, project, 
                                DATE_FORMAT(join_date, '%d-%m-%Y') as formatted_join_date 
                                FROM employee_active 
                                ORDER BY employee_name";
                        
                        $stmt = $conn->query($sql);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td><input type='checkbox' class='employee-select' value='" . htmlspecialchars($row['NIK']) . "' onchange='toggleAllCheckboxes()'></td>";
                            echo "<td>" . htmlspecialchars($row['NIK']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['employee_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['employee_email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['project']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['formatted_join_date']) . "</td>";
                            echo "</tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='7'>Error: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- REQUIRED SCRIPTS -->
<script src="../adminlte/plugins/jquery/jquery.min.js"></script>
<script src="../adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../adminlte/dist/js/adminlte.min.js"></script>
<script src="../adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    let table = $('#employeeTable').DataTable({
        "responsive": true,
        "pageLength": 20,
        "lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "All"]]
    });
});

function toggleAllCheckboxes() {
    const mainCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.employee-select');
    
    // If triggered by clicking individual checkboxes, update the select all checkbox
    if (!event.target.matches('#selectAll')) {
        mainCheckbox.checked = [...checkboxes].every(checkbox => checkbox.checked);
    } else {
        // If triggered by select all checkbox, update all individual checkboxes
        checkboxes.forEach(checkbox => {
            checkbox.checked = mainCheckbox.checked;
        });
    }
    
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkedCount = document.querySelectorAll('.employee-select:checked').length;
    document.getElementById('selectedCount').textContent = checkedCount;
    document.getElementById('bulkDeleteBtn').style.display = checkedCount > 0 ? 'block' : 'none';
}

// Add event listener for bulk delete
document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
    const selectedNIKs = Array.from(document.querySelectorAll('.employee-select:checked')).map(cb => cb.value);
    
    if (selectedNIKs.length > 0 && confirm('Delete ' + selectedNIKs.length + ' selected employees?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controller/c_employeelist.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'bulk_delete';

        const niksInput = document.createElement('input');
        niksInput.type = 'hidden';
        niksInput.name = 'niks';
        niksInput.value = JSON.stringify(selectedNIKs);

        form.appendChild(actionInput);
        form.appendChild(niksInput);
        document.body.appendChild(form);
        form.submit();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once '../main_navbar.php';
?> 