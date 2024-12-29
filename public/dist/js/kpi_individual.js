// Define baseUrl globally
const baseUrl = document.querySelector('meta[name="base-url"]').content;

// Utility function to generate consistent table names
function generateTableName(projectName) {
    return 'kpi_' + projectName.toLowerCase().replace(/[^a-z0-9_]/g, '_');
}

$(document).ready(function() {
    // Initialize Select2
    $(".select2").select2({
        theme: "bootstrap4",
        width: "100%",
        placeholder: "Select options"
    });

    // Initialize staging table
    let stagingTable = $("#stagingTable").DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        processing: true,
        scrollX: true,
        scrollCollapse: true,
        language: {
            emptyTable: "No data available",
            loadingRecords: "Loading...",
            processing: "Processing...",
            zeroRecords: "No matching records found"
        },
        columns: [
            { data: "nik" },
            { data: "employee_name" },
            { data: "kpi_metrics" },
            { data: "queue" },
            { data: "january", defaultContent: "-" },
            { data: "february", defaultContent: "-" },
            { data: "march", defaultContent: "-" },
            { data: "april", defaultContent: "-" },
            { data: "may", defaultContent: "-" },
            { data: "june", defaultContent: "-" },
            { data: "july", defaultContent: "-" },
            { data: "august", defaultContent: "-" },
            { data: "september", defaultContent: "-" },
            { data: "october", defaultContent: "-" },
            { data: "november", defaultContent: "-" },
            { data: "december", defaultContent: "-" }
        ],
        data: []
    });

    // Initialize metrics table
    $("#metricsTable").DataTable({
        responsive: true,
        autoWidth: false
    });

    // Project change handler
    $("#project").on("change", function() {
        const tableName = $(this).val();
        const kpiSelect = $("#kpiMetrics");
        const queueSelect = $("#queue");

        if (tableName) {
            kpiSelect.prop("disabled", false);
            
            console.log('Using table name:', tableName);
            
            const url = baseUrl + 'project/kpi?project=' + encodeURIComponent(tableName);
            console.log('Request URL:', url);
            
            // Fetch KPI metrics for selected project
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    kpiSelect.empty().append('<option value="">Select KPI Metrics</option>');
                    
                    if (data.success) {
                        data.data.forEach(metric => {
                            kpiSelect.append('<option value="' + metric + '">' + metric + '</option>');
                        });
                    } else {
                        throw new Error(data.error || 'Failed to load KPI metrics');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading KPI metrics: ' + error.message, 'error');
                    kpiSelect.prop("disabled", true)
                        .empty()
                        .append('<option value="">Error loading metrics</option>');
                    queueSelect.prop("disabled", true)
                        .empty()
                        .append('<option value="">Select KPI Metrics First</option>');
                });
        } else {
            // Reset selects when no project is selected
            kpiSelect.prop("disabled", true)
                .empty()
                .append('<option value="">Select Project First</option>');
            queueSelect.prop("disabled", true)
                .empty()
                .append('<option value="">Select KPI Metrics First</option>');
        }
    });

    // KPI Metrics change handler
    $("#kpiMetrics").on("change", function() {
        const project = $("#project").val();
        const selectedMetrics = $(this).val();
        const queueSelect = $("#queue");

        if (selectedMetrics && selectedMetrics.length > 0) {
            queueSelect.prop("disabled", false);
            
            const url = baseUrl + 'project/queues?project=' + encodeURIComponent(project) + 
                       '&kpi=' + encodeURIComponent(JSON.stringify(selectedMetrics));
            console.log('Fetching queues from URL:', url);
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    queueSelect.empty().append('<option value="">Select Queue</option>');
                    
                    if (data.success) {
                        data.data.forEach(queue => {
                            queueSelect.append('<option value="' + queue + '">' + queue + '</option>');
                        });
                    } else {
                        throw new Error(data.error || 'Failed to load queues');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading queues: ' + error.message, 'error');
                    queueSelect.prop("disabled", true)
                        .empty()
                        .append('<option value="">Error loading queues</option>');
                });
        } else {
            queueSelect.prop("disabled", true)
                .empty()
                .append('<option value="">Select KPI Metrics First</option>');
        }
    });

    // Process button click handler
    $("#processKPI").on("click", function() {
        const project = $("#project").val();
        const selectedMetrics = $("#kpiMetrics").val();
        const selectedQueues = $("#queue").val();

        if (!project || !selectedMetrics || !selectedQueues) {
            showNotification('Please select all required fields', 'error');
            return;
        }

        // Show loading state
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');

        // Prepare the request data
        const requestData = {
            project: project,
            metrics: selectedMetrics,
            queues: selectedQueues
        };

        console.log('Processing data for table:', project);

        // Fetch data from server
        fetch(baseUrl + 'kpi/individual/process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Received data:', data.data); // Debug the received data
                stagingTable.clear().rows.add(data.data).draw();
                showNotification('Data processed successfully', 'success');
            } else {
                throw new Error(data.message || 'Failed to process data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error processing data: ' + error.message, 'error');
        })
        .finally(() => {
            // Reset button state
            $(this).prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Process');
        });
    });

    // Initialize Select2 for employee dropdown when modal opens
    $('#addKPIModal').on('shown.bs.modal', function () {
        $('#employeeSelect').select2({
            theme: "bootstrap4",
            width: "100%",
            dropdownParent: $('#addKPIModal'), // Ensure dropdown shows over modal
            placeholder: "Search employee...",
            allowClear: true
        });
        
        // Make sure input fields are editable
        $('#modalKPIMetrics').prop('readonly', false);
        $('#modalQueue').prop('readonly', false);
    });

    // Clean up Select2 when modal closes
    $('#addKPIModal').on('hidden.bs.modal', function () {
        $('#employeeSelect').select2('destroy');
    });

    // Add KPI button click handler
    $("#addKPI").on("click", function() {
        const project = $("#project").val();
        if (!project) {
            showNotification('Please select a project first', 'error');
            return;
        }

        const url = baseUrl + 'project/employees?project=' + encodeURIComponent(project);
        console.log('Fetching employees from URL:', url);

        // Fetch employees for the selected project
        fetch(url)
            .then(response => response.json())
            .then(response => {
                console.log('Employee data:', response);
                const employeeSelect = $("#employeeSelect");
                employeeSelect.empty().append('<option value="">Search employee...</option>');
                
                if (response.success) {
                    response.data.forEach(emp => {
                        employeeSelect.append(
                            `<option value="${emp.nik}" data-name="${emp.employee_name}">
                                ${emp.nik} - ${emp.employee_name}
                            </option>`
                        );
                    });

                    // Initialize Select2 for employee dropdown
                    employeeSelect.select2({
                        theme: "bootstrap4",
                        width: "100%",
                        dropdownParent: $('#addKPIModal'),
                        placeholder: "Search employee..."
                    });

                    // Clear previous values
                    $("#modalKPIMetrics").val('');
                    $("#modalQueue").val('');
                    $("#selectedNik").val('');
                    $("#selectedName").val('');

                    // Get selected values from multi-select
                    const selectedMetrics = $("#kpiMetrics").val();
                    const selectedQueue = $("#queue").val();

                    // Set default values if only one item is selected
                    if (selectedMetrics && selectedMetrics.length === 1) {
                        $("#modalKPIMetrics").val(selectedMetrics[0]);
                    }
                    if (selectedQueue && selectedQueue.length === 1) {
                        $("#modalQueue").val(selectedQueue[0]);
                    }

                    // Show modal
                    $("#addKPIModal").modal('show');
                } else {
                    throw new Error(response.message || 'Failed to load employees');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error loading employees: ' + error.message, 'error');
            });
    });

    // Employee select change handler
    $("#employeeSelect").on("change", function() {
        const selectedOption = $(this).find("option:selected");
        const nik = selectedOption.val();
        const name = selectedOption.data('name');
        
        console.log('Selected employee:', { nik, name });
        
        $("#selectedNik").val(nik);
        $("#selectedName").val(name);
    });

    // Add KPI form submit handler
    $("#addKPIForm").on("submit", function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('project', $("#project").val());

        fetch(baseUrl + 'kpi/individual/save', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $("#addKPIModal").modal('hide');
                showNotification('KPI added successfully', 'success');
                // Refresh the table
                $("#processKPI").click();
            } else {
                throw new Error(data.message || 'Failed to add KPI');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error adding KPI: ' + error.message, 'error');
        });
    });

    // Import form submit handler
    $("#importForm").on("submit", function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('project', $("#project").val());

        fetch(baseUrl + 'kpi/individual/import', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $("#importModal").modal('hide');
                showNotification('Data imported successfully', 'success');
                // Refresh the table
                $("#processKPI").click();
            } else {
                throw new Error(data.message || 'Import failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error importing data: ' + error.message, 'error');
        });
    });

    // Export button click handler
    $("#exportKPI").on("click", function() {
        const project = $("#project").val(); // This already contains 'kpi_' prefix
        if (!project) {
            showNotification('Please select a project first', 'error');
            return;
        }

        console.log('Exporting project:', project); // Debug log

        // Build the export URL
        let url = baseUrl + 'kpi/individual/export?project=' + encodeURIComponent(project);

        // Add metrics and queues if selected
        const selectedMetrics = $("#kpiMetrics").val();
        const selectedQueues = $("#queue").val();
        if (selectedMetrics && selectedMetrics.length > 0) {
            url += '&kpi=' + encodeURIComponent(JSON.stringify(selectedMetrics));
        }
        if (selectedQueues && selectedQueues.length > 0) {
            url += '&queue=' + encodeURIComponent(JSON.stringify(selectedQueues));
        }

        console.log('Export URL:', url); // Debug log
        window.location.href = url;
    });

    // View metrics button click handler
    $(".view-metrics").on("click", function() {
        const project = $(this).data('project');
        const kpi = $(this).data('kpi');
        const queue = $(this).data('queue');

        // Set the selections
        $("#project").val(project).trigger('change');
        
        // Wait for KPI metrics to load
        setTimeout(() => {
            $("#kpiMetrics").val(kpi).trigger('change');
            
            // Wait for queues to load
            setTimeout(() => {
                $("#queue").val(queue).trigger('change');
                $("#processKPI").click();
            }, 500);
        }, 500);
    });

    // Make sure the input fields are editable when modal shows
    $('#addKPIModal').on('shown.bs.modal', function () {
        $('#modalKPIMetrics').prop('readonly', false);
        $('#modalQueue').prop('readonly', false);
    });

    // Use this function whenever you need to generate a table name
    $('#project').on('change', function() {
        let project = $(this).val();
        if (project) {
            let tableName = generateTableName(project);
            // Rest of your code...
        }
    });
});

// Notification helper function
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

// File input handler
$('.custom-file-input').on('change', function() {
    let fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').addClass("selected").html(fileName);
});
