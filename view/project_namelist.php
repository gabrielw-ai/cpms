<?php
$page_title = "Project Namelist";
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Project List</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addProjectModal">
                Add New Project
            </button>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Main Project</th>
                    <th>Project Name</th>
                    <th>Unit Name</th>
                    <th>Job Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once '../controller/conn.php';
                try {
                    $stmt = $conn->query("SELECT * FROM project_namelist ORDER BY id");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['main_project']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['project_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['unit_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['job_code']) . "</td>";
                        echo "<td>
                                <button class='btn btn-sm btn-info' onclick='editProject(" . $row['id'] . ")'>Edit</button>
                                <button class='btn btn-sm btn-danger' onclick='deleteProject(" . $row['id'] . ")'>Delete</button>
                              </td>";
                        echo "</tr>";
                    }
                } catch (PDOException $e) {
                    echo "Error: " . $e->getMessage();
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1" role="dialog" aria-labelledby="addProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProjectModalLabel">Add New Project</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="../controller/c_project_namelist.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="main_project">Main Project</label>
                        <input type="text" class="form-control" id="main_project" name="main_project" required>
                    </div>
                    <div class="form-group">
                        <label for="project_name">Project Name</label>
                        <input type="text" class="form-control" id="project_name" name="project_name" required>
                    </div>
                    <div class="form-group">
                        <label for="unit_name">Unit Name</label>
                        <input type="text" class="form-control" id="unit_name" name="unit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="job_code">Job Code</label>
                        <input type="text" class="form-control" id="job_code" name="job_code" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_project" class="btn btn-primary">Save Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1" role="dialog" aria-labelledby="editProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="../controller/c_project_namelist.php" method="POST">
                <input type="hidden" id="edit_id" name="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_main_project">Main Project</label>
                        <input type="text" class="form-control" id="edit_main_project" name="main_project" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_project_name">Project Name</label>
                        <input type="text" class="form-control" id="edit_project_name" name="project_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_unit_name">Unit Name</label>
                        <input type="text" class="form-control" id="edit_unit_name" name="unit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_job_code">Job Code</label>
                        <input type="text" class="form-control" id="edit_job_code" name="job_code" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_project" class="btn btn-primary">Update Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProject(projectId) {
    // Fetch project data and populate edit modal
    fetch(`../controller/c_project_namelist.php?get_project=${projectId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_main_project').value = data.main_project;
            document.getElementById('edit_project_name').value = data.project_name;
            document.getElementById('edit_unit_name').value = data.unit_name;
            document.getElementById('edit_job_code').value = data.job_code;
            $('#editProjectModal').modal('show');
        })
        .catch(error => console.error('Error:', error));
}

function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project?')) {
        window.location.href = `../controller/c_project_namelist.php?delete_project=${projectId}`;
    }
}
</script>

<?php
$content = ob_get_clean();
require_once '../main_navbar.php';
?>
