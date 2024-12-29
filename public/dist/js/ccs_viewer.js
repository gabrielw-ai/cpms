// Declare table as a global variable
let table;

$(document).ready(function() {
    // Initialize DataTable
    table = $('#rulesTable').DataTable({
        "responsive": false,
        "pageLength": 20,
        "lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "All"]],
        "searching": true,
        "search": {
            "smart": true,
            "caseInsensitive": true
        },
        "autoWidth": false,
        "scrollX": true,
        "order": [[7, 'desc']], // Sort by Effective Date by default
        "ajax": {
            "url": baseUrl + "controller/get_rules.php",
            "type": "GET",
            "dataSrc": function(json) {
                return json.data || [];
            }
        },
        "columns": [
            { "data": "project" },
            { "data": "nik" },
            { "data": "name" },
            { "data": "role" },
            { "data": "tenure" },
            { "data": "case_chronology" },
            { "data": "consequences" },
            { "data": "effective_date" },
            { "data": "end_date" },
            { 
                "data": "status",
                "render": function(data) {
                    return '<span class="badge badge-' + 
                        (data === 'active' ? 'success' : 'danger') + 
                        '">' + data + '</span>';
                }
            },
            { 
                "data": "supporting_doc_url",
                "render": function(data) {
                    if (data) {
                        return '<a href="' + baseUrl + data + '" class="btn btn-xs btn-info">View</a>';
                    }
                    return '';
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    return '<div class="btn-group">' +
                           '<button type="button" class="btn btn-primary btn-sm edit-rule" ' +
                           'data-id="' + row.id + '">' +
                           '<i class="fas fa-edit"></i></button>' +
                           '<button type="button" class="btn btn-danger btn-sm delete-ccs-rule" ' +
                           'data-id="' + row.id + '" ' +
                           'data-project="' + row.project + '">' +
                           '<i class="fas fa-trash"></i></button>' +
                           '</div>';
                }
            }
        ],
        "columnDefs": [
            {
                "targets": [-3, -2, -1], // Last 3 columns (status, doc, actions)
                "searchable": false
            }
        ]
    });

    // Add custom filtering
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var projectFilter = $('#projectFilter').val();
            var roleFilter = $('#roleFilter').val();
            var statusFilter = $('#statusFilter').val();

            var project = data[0];  // Project column
            var role = data[3];     // Role column
            var endDate = new Date(data[8]); // End Date column
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Determine status based on end date
            var status = endDate < today ? 'expired' : 'active';

            // Check each filter
            if (projectFilter && project !== projectFilter) return false;
            if (roleFilter && role !== roleFilter) return false;
            if (statusFilter && status !== statusFilter.toLowerCase()) return false;

            return true;
        }
    );

    // Initialize Select2 for filters
    $('.select2-filter').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true,
        dropdownParent: $('.filter-section')
    });

    // Handle filter changes
    $('#projectFilter, #roleFilter, #statusFilter').on('change', function() {
        table.draw();
    });

    // Clear filters button
    $('#clearFilters').on('click', function() {
        $('.select2-filter').val(null).trigger('change');
        table.draw();
    });

    // Handle table redraw on sidebar toggle
    $('[data-widget="pushmenu"]').on('click', function() {
        setTimeout(function() {
            table.columns.adjust().draw();
        }, 300);
    });

    // Edit form submission
    $('#editRuleForm').on('submit', function(e) {
        e.preventDefault();
        
        // Create a regular object with form data
        var formData = {
            action: 'edit',
            id: $('#edit_id').val(),
            project: $('#edit_project').val(),
            case_chronology: $('#edit_case_chronology').val(),
            consequences: $('#edit_consequences').val(),
            effective_date: $('#edit_effective_date').val(),
            end_date: $('#edit_end_date').val()
        };

        // Handle file upload if present
        var fileInput = $('#edit_supporting_doc')[0];
        if (fileInput && fileInput.files.length > 0) {
            formData.supporting_doc = fileInput.files[0];
        }
        
        console.log('Sending update data:', formData);

        $.ajax({
            url: baseUrl + 'ccs/update',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Update response:', response);
                if (response.success) {
                    $('#editRuleModal').modal('hide');
                    table.ajax.reload();
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message || 'Failed to update rule');
                }
            },
            error: function(xhr, status, error) {
                console.error('Update error:', error);
                showAlert('error', 'Error updating rule: ' + error);
            }
        });
    });

    // Edit button handler
    $(document).on('click', '.edit-rule', function() {
        var id = $(this).data('id');
        editRule(id);
    });

    // File input handler
    $(document).on('change', '.custom-file-input', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choose file');
    });

    // Modal Select2 initialization
    $('#editRuleModal').on('shown.bs.modal', function() {
        $('#edit_consequences').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#editRuleModal'),
            minimumResultsForSearch: Infinity,
            dropdownPosition: 'below',
            dropdownAutoWidth: true,
            dropdownCssClass: 'select2-dropdown-below'
        });
    });

    // Modal Select2 cleanup
    $('#editRuleModal').on('hide.bs.modal', function() {
        $('#edit_consequences').select2('destroy');
    });

    // Delete button handler
    $(document).on('click', '.delete-ccs-rule', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var project = $(this).data('project');
        
        console.log('Delete clicked - ID:', id, 'Project:', project);
        var deleteUrl = baseUrl + 'ccs/delete';
        console.log('Delete URL:', deleteUrl);
        
        if (confirm('Are you sure you want to delete this rule?')) {
            $.ajax({
                url: deleteUrl,
                type: 'POST',
                data: { 
                    action: 'delete',
                    id: id,
                    project: project
                },
                beforeSend: function(xhr) {
                    console.log('Sending delete request to:', deleteUrl);
                },
                success: function(response) {
                    console.log('Delete response:', response);
                    if (response.success) {
                        table.ajax.reload();
                        showAlert('success', response.message);
                    } else {
                        showAlert('error', response.message || 'Failed to delete rule');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete error:', {
                        url: deleteUrl,
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    showAlert('error', 'Error deleting rule: ' + error);
                }
            });
        }
    });
});

// Functions outside document.ready
function editRule(id) {
    var row = table.rows().data().toArray().find(r => r.id == id);
    
    if (row) {
        console.log('Editing row:', row); // Debug log
        
        $('#editRuleModal').modal('show');
        
        // Set values
        $('#edit_id').val(row.id);
        $('#edit_project').val(row.project);
        $('#edit_nik').val(row.nik);
        $('#edit_name').val(row.name);
        $('#edit_role').val(row.role);
        $('#edit_case_chronology').val(row.case_chronology);
        $('#edit_effective_date').val(row.effective_date);
        $('#edit_end_date').val(row.end_date);
        
        // Set consequences value and trigger end date calculation
        setTimeout(function() {
            $('#edit_consequences').val(row.consequences).trigger('change');
        }, 100);
    } else {
        showAlert('error', 'Could not find rule data');
    }
}

function showAlert(type, message) {
    // Remove existing alerts
    $('.alert-notification').remove();
    
    // Create simple alert
    var alert = $('<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible alert-notification">' +
                  '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                  message +
                  '</div>')
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'z-index': '9999',
            'max-width': '300px'
        });
    
    $('body').append(alert);
    
    setTimeout(function() {
        alert.fadeOut('slow', function() { $(this).remove(); });
    }, 3000);
}

// Update the date validation and end date calculation
$(document).on('change', '#edit_effective_date', function() {
    var effectiveDate = new Date($(this).val());
    var today = new Date();
    today.setHours(0, 0, 0, 0);

    if (effectiveDate > today) {
        showAlert('error', 'Effective date cannot be later than today');
        $(this).val(today.toISOString().split('T')[0]);
    }

    // Recalculate end date when effective date changes
    const consequences = $('#edit_consequences').val();
    if (consequences) {
        const endDate = calculateEndDate($(this).val(), consequences);
        $('#edit_end_date').val(endDate);
    }
});

// Add consequences change handler
$(document).on('change', '#edit_consequences', function() {
    const effectiveDate = $('#edit_effective_date').val();
    const consequences = $(this).val();
    
    if (effectiveDate && consequences) {
        const endDate = calculateEndDate(effectiveDate, consequences);
        $('#edit_end_date').val(endDate);
    }
});

// Add this function to calculate end date based on consequences
function calculateEndDate(effectiveDate, consequences) {
    const date = new Date(effectiveDate);
    
    if (consequences.toLowerCase().includes('warning letter')) {
        // Add 6 months for Warning Letters
        date.setMonth(date.getMonth() + 6);
    } else if (consequences.toLowerCase().includes('written reminder')) {
        // Add 1 year for Written Reminders
        date.setFullYear(date.getFullYear() + 1);
    }
    
    return date.toISOString().split('T')[0];
}

// Your existing editRule and showNotification functions... 