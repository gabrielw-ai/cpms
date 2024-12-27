<?php
session_start();
$page_title = "Project Namelist";
ob_start();

// Include routing and database connection
require_once dirname(__DIR__) . '/routing.php';
require_once dirname(__DIR__) . '/controller/conn.php';
global $conn;

// Add CSS for notifications
$additional_css = '
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

    .alert {
        margin-bottom: 1rem;
        border: none;
        border-radius: 4px;
        color: #fff;
    }

    .alert-success {
        background-color: #28a745;
    }

    .alert-danger {
        background-color: #dc3545;
    }

    .alert-info {
        background-color: #17a2b8;
    }

    .alert .close {
        color: #fff;
        opacity: 0.8;
    }

    .alert .close:hover {
        opacity: 1;
    }
</style>';

// Add DataTables CSS to additional_css
$additional_css .= '
<!-- DataTables -->
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . Router::url('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') . '">';

// Add this to include DataTables JavaScript files
$additional_js = '
<!-- DataTables -->
<script src="' . Router::url('adminlte/plugins/datatables/jquery.dataTables.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') . '"></script>
<script src="' . Router::url('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') . '"></script>';

// Handle session messages
if (isset($_SESSION['success_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('" . addslashes($_SESSION['success_message']) . "', 'success');
        });
    </script>";
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('" . addslashes($_SESSION['error_message']) . "', 'danger');
        });
    </script>";
    unset($_SESSION['error_message']);
}
?>

<!-- Add notification handling -->
<?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show floating-alert">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo htmlspecialchars($_GET['message']); ?>
    </div>
    <script>
        // Auto dismiss alert after 3 seconds
        setTimeout(function() {
            $('.floating-alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    </script>
<?php endif; ?>

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
        <table id="projectTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Main Project</th>
                    <th>Project Name</th>
                    <th>Unit Name</th>
                    <th>Job Code</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $conn->query("SELECT * FROM project_namelist ORDER BY id");
                    $no = 1; // Initialize counter
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td class='text-center'>" . $no++ . "</td>"; // Add number here
                        echo "<td>" . htmlspecialchars($row['main_project']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['project_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['unit_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['job_code']) . "</td>";
                        echo "<td class='text-center'>
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
            <form action="<?php echo Router::url('project/add'); ?>" method="POST" id="addProjectForm">
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
            <form action="<?php echo Router::url('project/edit'); ?>" method="POST">
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
// Define baseUrl
const baseUrl = '<?php echo Router::url(''); ?>';

function showNotification(message, type = 'success') {
    // Remove any existing notifications
    $('.floating-alert').remove();
    
    // Create the notification element
    const alert = $('<div class="alert alert-' + type + ' alert-dismissible fade show floating-alert">' +
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

function editProject(projectId) {
    console.log('Editing project:', projectId);
    
    fetch(`${baseUrl}project/get?get_project=${projectId}`)
        .then(async response => {
            const text = await response.text();
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text);
                if (data) {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_main_project').value = data.main_project;
                    document.getElementById('edit_project_name').value = data.project_name;
                    document.getElementById('edit_unit_name').value = data.unit_name;
                    document.getElementById('edit_job_code').value = data.job_code;
                    $('#editProjectModal').modal('show');
                } else {
                    throw new Error('Project data not found');
                }
            } catch (e) {
                console.error('Parse error:', e);
                showNotification('Error loading project data: ' + e.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showNotification('Error loading project data', 'danger');
        });
}

function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project?')) {
        console.log('Deleting project:', projectId);
        
        // Show loading notification
        showNotification('Deleting project...', 'info');
        
        fetch(`${baseUrl}project/delete?delete_project=${projectId}`)
            .then(async response => {
                const text = await response.text();
                console.log('Raw response:', text);
                
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        // Remove the row immediately for better UX
                        $(`button[onclick="deleteProject(${projectId})"]`).closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                        
                        // Show success notification
                        showNotification(data.message || 'Project deleted successfully', 'success');
                        
                        // Reload page after a delay
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        throw new Error(data.message || 'Failed to delete project');
                    }
                } catch (e) {
                    console.error('Error:', e);
                    showNotification(e.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                showNotification('Error deleting project: ' + error.message, 'danger');
            });
    }
}

// Add form submission handlers
$(document).ready(function() {
    // Add Project form handler
    $('#addProjectModal form').on('submit', function(e) {
        e.preventDefault();
        
        // Show loading notification
        showNotification('Adding project...', 'info');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#addProjectModal').modal('hide');
                showNotification('Project added successfully', 'success');
                setTimeout(() => {
                    window.location.href = baseUrl + 'projects';
                }, 1000);
            },
            error: function(xhr) {
                showNotification('Error adding project', 'danger');
            }
        });
    });

    // Edit Project form handler
    $('#editProjectModal form').on('submit', function(e) {
        e.preventDefault();
        
        // Show loading notification
        showNotification('Updating project...', 'info');
        
        fetch($(this).attr('action'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams($(this).serialize())
        })
        .then(async response => {
            const text = await response.text();
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    $('#editProjectModal').modal('hide');
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = baseUrl + 'projects';
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to update project');
                }
            } catch (e) {
                console.error('Error:', e);
                showNotification(e.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Update error:', error);
            showNotification('Error updating project: ' + error.message, 'danger');
        });
    });
});

$(document).ready(function() {
    var table = $('#projectTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "pageLength": 10,
        "order": [[1, 'asc']], // Sort by Main Project column
        "columnDefs": [
            {
                "targets": 0,
                "orderable": false,
                "searchable": false
            },
            {
                "targets": -1, // Last column (Actions)
                "orderable": false,
                "searchable": false
            }
        ],
        "drawCallback": function(settings) {
            // Update row numbers after draw
            this.api().column(0).nodes().each(function(cell, i) {
                cell.innerHTML = i + 1;
            });
        },
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total records)"
        }
    });
});
</script>

<?php
$content = ob_get_clean();

?>
