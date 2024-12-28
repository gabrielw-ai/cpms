$(function() {
    // Handle default view for selected project
    var selectedOption = $("select[name='table'] option:selected");
    if (selectedOption.length && selectedOption.data('default-url')) {
        window.location.href = selectedOption.data('default-url');
    }

    // Initialize Select2 Elements
    $(".select2").select2({
        theme: "bootstrap4"
    });

    // Initialize DataTable
    $("#kpiTable").DataTable({
        "responsive": false,
        "autoWidth": false,
        "scrollX": true,
        "scrollCollapse": true,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "order": [[0, "asc"], [1, "asc"]],
        "columnDefs": [
            {
                "targets": "_all",
                "defaultContent": "-"
            }
        ]
    });

    // Initialize file input
    bsCustomFileInput.init();

    // Handle edit button clicks
    $(".edit-kpi").click(function() {
        var queue = $(this).data("queue");
        var kpi_metrics = $(this).data("kpi_metrics");
        var target = $(this).data("kpi_target");
        var target_type = $(this).data("target_type");
        
        $("#original_queue").val(queue);
        $("#original_kpi_metrics").val(kpi_metrics);
        $("#queue").val(queue);
        $("#kpi_metrics").val(kpi_metrics);
        $("#target").val(target);
        $("#target_type").val(target_type);
    });
    
    // Handle edit form submission with AJAX
    $("#editKPIForm").on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var button = form.find('button[type="submit"]');
        button.prop('disabled', true);

        // Add table_name to form data
        var formData = new FormData(this);
        formData.append('table_name', $("select[name='table']").val());
        formData.append('view_type', $("select[name='view']").val() || "weekly");

        $.ajax({
            url: '../controller/c_viewer_update.php',
            type: 'POST',
            data: Object.fromEntries(formData),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.message || 'Failed to update KPI', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error updating KPI: ' + error, 'error');
                console.error("Update error:", error);
            },
            complete: function() {
                button.prop('disabled', false);
                $('#editModal').modal('hide');
            }
        });
    });

    // Handle delete button clicks
    $(".delete-kpi").click(function() {
        if (confirm("Are you sure you want to delete this KPI?")) {
            var button = $(this);
            button.prop('disabled', true);
            
            var form = $("<form>")
                .attr("method", "POST")
                .attr("action", "../controller/c_viewer_del.php");
                
            form.append($("<input>")
                .attr("type", "hidden")
                .attr("name", "table_name")
                .val($("select[name='table']").val()));
                
            form.append($("<input>")
                .attr("type", "hidden")
                .attr("name", "queue")
                .val($(this).data("queue")));
                
            form.append($("<input>")
                .attr("type", "hidden")
                .attr("name", "kpi_metrics")
                .val($(this).data("kpi_metrics")));
                
            form.append($("<input>")
                .attr("type", "hidden")
                .attr("name", "view_type")
                .val($("select[name='view']").val() || "weekly"));

            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification(response.message, 'error');
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Error occurred while deleting KPI', 'error');
                    button.prop('disabled', false);
                    console.error("Delete error:", error);
                }
            });
        }
    });

    // Initialize Toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
});

// Function to show notifications
function showNotification(message, type = 'success') {
    // Remove any existing notifications
    $('.floating-alert').remove();
    
    // Create the notification element
    const alert = $('<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show floating-alert">' +
        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
        '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + ' mr-2"></i>' +
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
