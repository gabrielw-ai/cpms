<?php
$page_title = "Chart Generator";
ob_start();
require_once '../controller/conn.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">KPI Chart Generator</h3>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form id="chartFilterForm" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="project">Project Name</label>
                        <select class="form-control" id="project" name="project" required>
                            <option value="">Select Project</option>
                            <?php
                            try {
                                $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $projectName = $row['project_name'];
                                    $tableName = 'KPI_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $projectName);
                                    echo "<option value='" . htmlspecialchars($tableName) . "'>" . 
                                         htmlspecialchars($projectName) . "</option>";
                                }
                            } catch (PDOException $e) {
                                echo "<option value=''>Error loading projects</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="period">Period</label>
                        <select class="form-control" id="period" name="period" required>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="kpi_metrics">KPI Metrics</label>
                        <select class="form-control" id="kpi_metrics" name="kpi_metrics" required>
                            <option value="">Select Project First</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-chart-line mr-2"></i>Generate Chart
                </button>
            </div>
        </form>

        <!-- Chart Container -->
        <div id="chartContainer" style="height: 500px;">
            <div class="text-center text-muted my-5">
                <i class="fas fa-chart-line fa-3x mb-3"></i>
                <p>Select filters and click Generate Chart to view the data</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../main_navbar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update KPI metrics when project or period changes
    $('#project, #period').change(function() {
        const project = $('#project').val();
        const period = $('#period').val();
        
        if (!project) {
            $('#kpi_metrics').html('<option value="">Select Project First</option>').prop('disabled', true);
            return;
        }

        const tableName = project + (period === 'monthly' ? '_MON' : '');
        
        // Show loading
        $('#kpi_metrics').html('<option value="">Loading...</option>').prop('disabled', true);
        
        // Fetch metrics
        fetch(`../controller/get_kpi_metrics.php?table=${encodeURIComponent(tableName)}`)
            .then(response => response.json())
            .then(metrics => {
                const select = $('#kpi_metrics');
                select.empty().append('<option value="">Select KPI Metric</option>');
                
                if (Array.isArray(metrics) && metrics.length > 0) {
                    metrics.forEach(metric => {
                        select.append(new Option(metric, metric));
                    });
                    select.prop('disabled', false);
                } else {
                    select.html('<option value="">No metrics found</option>');
                }
            })
            .catch(() => {
                $('#kpi_metrics').html('<option value="">Error loading metrics</option>').prop('disabled', true);
            });
    });

    // Handle form submission
    $('#chartFilterForm').submit(function(e) {
        e.preventDefault();
        
        const project = $('#project').val();
        const period = $('#period').val();
        const metric = $('#kpi_metrics').val();
        
        if (!project || !metric) return;
        
        // Show loading
        const chartContainer = document.getElementById('chartContainer');
        chartContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="height:500px"><div class="spinner-border text-primary"></div></div>';
        
        // Fetch data
        fetch(`../controller/get_chart_data.php?project=${encodeURIComponent(project)}&period=${period}&metric=${encodeURIComponent(metric)}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.error);
                
                // Clear loading
                chartContainer.innerHTML = '<canvas></canvas>';
                
                // Create chart
                new Chart(chartContainer.querySelector('canvas'), {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: metric,
                            data: data.values,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: metric
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            })
            .catch(error => {
                chartContainer.innerHTML = `<div class="alert alert-danger m-3">${error.message}</div>`;
            });
    });
});
</script>
