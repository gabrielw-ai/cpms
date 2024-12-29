$(function() {
    // Initialize Select2 Elements
    $(".select2bs4").select2({
        theme: "bootstrap4",
        width: '100%'
    });

    // Initialize Custom File Input
    bsCustomFileInput.init();

    // Handle project selection
    $("#project").on("change", function() {
        var project = $(this).val();
        var employeeSelect = $("#employee");
        
        employeeSelect.empty().append("<option value=\"\">Select Employee</option>");
        
        if (project) {
            employeeSelect.prop('disabled', true);
            
            // Debug log
            console.log('Fetching employees for project:', project);
            console.log('Request URL:', baseUrl + 'controller/get_project_employees.php');
            
            $.ajax({
                url: baseUrl + 'controller/get_project_employees.php',
                type: "GET",
                data: { project: project },
                success: function(response) {
                    console.log('Raw response:', response);
                    
                    try {
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        
                        if (response.success && Array.isArray(response.data)) {
                            if (response.data.length === 0) {
                                employeeSelect.append(new Option(
                                    "No employees found in this project",
                                    ""
                                ));
                            } else {
                                response.data.forEach(function(employee) {
                                    employeeSelect.append(new Option(
                                        employee.nik + " - " + employee.employee_name,
                                        employee.nik
                                    ));
                                });
                            }
                        } else {
                            console.error('Invalid response format:', response);
                            if (response.debug) {
                                console.error('Debug info:', response.debug);
                            }
                            employeeSelect.append(new Option(
                                "Error loading employees: " + (response.message || 'Invalid format'),
                                ""
                            ));
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        console.error('Raw response:', response);
                        employeeSelect.append(new Option(
                            "Error parsing employee data",
                            ""
                        ));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        xhr: xhr,
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    employeeSelect.append(new Option(
                        "Error loading employees: " + error,
                        ""
                    ));
                },
                complete: function() {
                    employeeSelect.prop('disabled', false);
                    employeeSelect.trigger('change');
                }
            });
        }
    });

    // Handle employee selection with loading state
    $("#employee").on("change", function() {
        var selected = $(this).find(":selected");
        var nik = $(this).val();
        
        if (nik) {
            var fullText = selected.text();
            var name = fullText.split(" - ")[1];
            
            $("#name").val(name);
            $("#nik").val(nik);
            
            // Show loading state for role and tenure
            $("#role, #tenure").prop('disabled', true);
            
            $.get(baseUrl + 'controller/get_employee_role.php', { nik: nik }, function(response) {
                if (response.success) {
                    $("#role").val(response.role);
                    $("#tenure").val(response.tenure);
                }
            })
            .always(function() {
                // Remove loading state
                $("#role, #tenure").prop('disabled', false);
            });
        } else {
            // Clear fields if no employee selected
            $("#name, #nik, #role, #tenure").val('');
        }
    });

    // Enhanced form validation
    $('#ccsRulesForm').on('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        var requiredFields = ['project', 'employee', 'case_chronology', 'ccs_rule', 'effective_date'];
        var isValid = true;
        
        requiredFields.forEach(function(field) {
            var $field = $('#' + field);
            if (!$field.val()) {
                isValid = false;
                $field.addClass('is-invalid');
            } else {
                $field.removeClass('is-invalid');
            }
        });

        if (!isValid) {
            showNotification('Please fill in all required fields', 'error');
            return false;
        }

        // Submit form via AJAX
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification('Rule added successfully', 'success');
                    // Reset form
                    $('#ccsRulesForm')[0].reset();
                    $('.select2bs4').val('').trigger('change');
                    $('.custom-file-label').html('Choose file');
                } else {
                    showNotification(response.message || 'Error adding rule', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error: ' + error, 'error');
            }
        });
    });

    // File input handler with validation
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        var fileExtension = fileName.split('.').pop().toLowerCase();
        
        if (['pdf', 'xlsx', 'xls'].includes(fileExtension)) {
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
            $(this).removeClass('is-invalid');
        } else {
            $(this).val('');
            $(this).next('.custom-file-label').html('Choose file');
            $(this).addClass('is-invalid');
            alert('Please upload only PDF or Excel files');
        }
    });

    // Calculate end date with validation
    $('#effective_date, #ccs_rule').on('change', function() {
        calculateEndDate();
    });

    function calculateEndDate() {
        const effectiveDate = $('#effective_date').val();
        const ccsRule = $('#ccs_rule').val();
        
        if (effectiveDate && ccsRule) {
            const startDate = new Date(effectiveDate);
            let endDate = new Date(startDate);
            
            if (ccsRule.startsWith('WR')) {
                endDate.setFullYear(endDate.getFullYear() + 1);
                endDate.setDate(endDate.getDate() - 1);
            } else {
                endDate.setMonth(endDate.getMonth() + 6);
                endDate.setDate(endDate.getDate() - 1);
            }
            
            $('#end_date').val(endDate.toISOString().split('T')[0]);
        }
    }

    // Pre-fill form fields in edit mode
    if (typeof ruleData !== 'undefined') {
        $('#project').val(ruleData.project).trigger('change');
        $('#name').val(ruleData.name);
        $('#nik').val(ruleData.nik);
        $('#role').val(ruleData.role);
        $('#tenure').val(ruleData.tenure);
        $('#case_chronology').val(ruleData.case_chronology);
        $('#ccs_rule').val(ruleData.consequences);
        $('#effective_date').val(ruleData.effective_date);
        
        // Make document upload optional when editing
        $('#document').removeAttr('required');
    }
});

function showNotification(message, type = 'success') {
    // Remove any existing notifications
    $('.floating-alert').remove();
    
    // Create the notification element
    const alert = $('<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show floating-alert">' +
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