// Wait for document ready and ensure jQuery is loaded
document.addEventListener('DOMContentLoaded', function() {
    function initializeComponents() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initializeComponents, 100);
            return;
        }

        // Define baseUrl if not already defined
        if (typeof baseUrl === 'undefined') {
            console.error('baseUrl not defined');
            return;
        }

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

        // Format project name when typing
        $('#project_name').on('input', function() {
            let value = $(this).val();
            let words = value.split(/[\s_]+/);
            let formatted = words.map(word => word.toUpperCase()).join('_');
            $(this).val(formatted);
        });

        // Format edit project name
        $('#edit_project_name').on('input', function() {
            let value = $(this).val();
            let words = value.split(/[\s_]+/);
            let formatted = words.map(word => word.toUpperCase()).join('_');
            $(this).val(formatted);
        });

        // Initialize DataTable
        var table = $('#projectTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "pageLength": 10,
            "order": [[1, 'asc']],
            "columnDefs": [
                {
                    "targets": 0,
                    "orderable": false,
                    "searchable": false
                },
                {
                    "targets": -1,
                    "orderable": false,
                    "searchable": false
                }
            ],
            "drawCallback": function(settings) {
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

        // Form handlers
        $('#addProjectModal form').on('submit', function(e) {
            e.preventDefault();
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

        // Edit form handler
        $('#editProjectModal form').on('submit', function(e) {
            e.preventDefault();
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

        // Make functions globally available
        window.editProject = function(projectId) {
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
        };

        window.deleteProject = function(projectId) {
            if (confirm('Are you sure you want to delete this project?')) {
                console.log('Deleting project:', projectId);
                showNotification('Deleting project...', 'info');
                
                fetch(`${baseUrl}project/delete?delete_project=${projectId}`)
                    .then(async response => {
                        const text = await response.text();
                        console.log('Raw response:', text);
                        
                        try {
                            const data = JSON.parse(text);
                            if (data.success) {
                                $(`button[onclick="deleteProject(${projectId})"]`).closest('tr').fadeOut(300, function() {
                                    $(this).remove();
                                });
                                showNotification(data.message || 'Project deleted successfully', 'success');
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
        };
    }

    // Start initialization
    initializeComponents();
}); 