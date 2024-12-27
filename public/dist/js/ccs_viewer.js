// Declare table as a global variable
let table;

document.addEventListener('DOMContentLoaded', function() {
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
                "width": "80px",
                "className": "text-center",
                "render": function(data) {
                    return '<span class="badge badge-' + 
                        (data === 'active' ? 'success' : 'danger') + 
                        '">' + data + '</span>';
                }
            },
            { 
                "data": "supporting_doc_url",
                "width": "60px",
                "className": "text-center",
                "render": function(data, type, row) {
                    if (data) {
                        return '<a href="' + baseUrl + data + '" class="btn btn-xs btn-info">View</a>';
                    }
                    return '';
                }
            },
            { 
                "data": null,
                "width": "100px",
                "className": "text-center",
                "orderable": false,
                "render": function(data, type, row) {
                    return '<div class="btn-group">' +
                           '<button type="button" class="btn btn-primary btn-sm edit-rule" data-id="' + row.id + '">' +
                           '<i class="fas fa-edit"></i></button>' +
                           '<button type="button" class="btn btn-danger btn-sm" onclick="deleteRule(' + row.id + ')">' +
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
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true
    });

    // Handle filter changes
    $('#projectFilter, #roleFilter, #statusFilter').on('change', function() {
        table.draw();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#projectFilter, #roleFilter, #statusFilter').val(null).trigger('change');
        table.draw();
    });

    // Add this to handle table redraw on sidebar toggle
    $('[data-widget="pushmenu"]').on('click', function() {
        setTimeout(function() {
            table.columns.adjust().draw();
        }, 300);
    });

    // Your existing filter functionality
    $('#projectFilter, #roleFilter, #statusFilter').on('change', function() {
        table.draw();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('.select2').val('').trigger('change');
        table.search('').columns().search('').draw();
    });

    // Add this to your document ready function
    $('#editRuleForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: baseUrl + 'controller/c_viewer_update.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#editRuleModal').modal('hide');
                    table.ajax.reload();
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('error', 'Error updating rule: ' + error);
            }
        });
    });

    // Add this event handler inside your DOMContentLoaded
    $(document).on('click', '.edit-rule', function() {
        var id = $(this).data('id');
        editRule(id);
    });

    // Add file input handler for showing selected filename
    $(document).on('change', '.custom-file-input', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choose file');
    });

    // Initialize Select2 Elements
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Initialize the modal's Select2
    $('#editRuleModal').on('shown.bs.modal', function() {
        $('#edit_consequences').select2('destroy').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#editRuleModal'),
            minimumResultsForSearch: Infinity // Disable search
        });
    });

    // Add input group focus handling
    $('.input-group .form-control').on('focus blur', function(e) {
        $(this).parent('.input-group').toggleClass('focused', e.type === 'focus');
    });
});

// Functions outside DOMContentLoaded
function editRule(id) {
    var row = table.rows().data().toArray().find(r => r.id == id);
    
    if (row) {
        $('#editRuleModal').modal('show');
        
        // Set values
        $('#edit_id').val(row.id);
        $('#edit_nik').val(row.nik);
        $('#edit_name').val(row.name);
        $('#edit_role').val(row.role);
        $('#edit_case_chronology').val(row.case_chronology);
        $('#edit_effective_date').val(row.effective_date);
        
        // Set consequences value and trigger end date calculation
        setTimeout(function() {
            $('#edit_consequences').val(row.consequences).trigger('change');
        }, 100);
    } else {
        showAlert('error', 'Could not find rule data');
    }
}

function deleteRule(id) {
    if (confirm('Are you sure you want to delete this rule?')) {
        $.ajax({
            url: baseUrl + 'controller/c_viewer_del.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    table.ajax.reload();
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('error', 'Error deleting rule: ' + error);
            }
        });
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