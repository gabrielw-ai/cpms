<?php
// Change session_start() to check if session is already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "CCS Rules Management";
ob_start();
require_once '../controller/conn.php';
require_once '../controller/c_ccs_rules.php';
require_once '../controller/c_uac.php';

// Get user's role and check access
$userRole = $_SESSION['user_role'] ?? '';
$menuAccess = getUserMenuAccess($conn, $userRole);

// Check if user has access to add_ccs_rules
if ($userRole !== 'Super_User' && !hasMenuAccess($menuAccess, 'add_ccs_rules', 'write')) {
    $_SESSION['error'] = "Access Denied. You don't have permission to access this page.";
    header('Location: ../index.php');
    exit;
}

// Debug output
if (isset($_SESSION)) {
    error_log("Session data: " . print_r($_SESSION, true));
}

// Add this at the top after require_once
$isEdit = false;
$ruleData = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $isEdit = true;
    $ruleData = getRuleById($conn, $_GET['id']);
    if (!$ruleData) {
        $_SESSION['error'] = "Rule not found";
        header('Location: ../view/ccs_viewer.php');
        exit;
    }
}
?>

<!-- Add jQuery first -->
<script src="../adminlte/plugins/jquery/jquery.min.js"></script>

<!-- Then add Select2 CSS and JS -->
<link href="../adminlte/plugins/select2/css/select2.min.css" rel="stylesheet" />
<link href="../adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css" rel="stylesheet" />
<script src="../adminlte/plugins/select2/js/select2.full.min.js"></script>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">CCS Rules Management</h3>
    </div>
    <div class="card-body">
        <!-- Add these notification alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show py-2">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true" style="padding: .5rem;">×</button>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true" style="padding: .5rem;">×</button>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Add this right before the form -->
        <div id="debug" style="display:none;">
            <pre id="debugValues"></pre>
        </div>

        <form id="ccsRulesForm" action="../controller/c_ccs_rules.php" method="POST" enctype="multipart/form-data">
            <?php if ($isEdit): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($ruleData['id']); ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <!-- Project Selection -->
                    <div class="form-group">
                        <label for="project">Project</label>
                        <select class="form-control select2" id="project" name="project" required>
                            <option value="">Select Project</option>
                            <?php
                            $stmt = $conn->query("SELECT project_name FROM project_namelist ORDER BY project_name");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $selected = ($isEdit && $ruleData['project'] === $row['project_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['project_name']) . "' $selected>" . 
                                     htmlspecialchars($row['project_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Name -->
                    <div class="form-group">
                        <label for="name">Name</label>
                        <div class="dropdown">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by NIK or Name" onclick="toggleDropdown()">
                            <input type="hidden" id="name" name="name" required>
                            <input type="hidden" id="nik" name="nik" required>
                            <div id="nameDropdown" class="dropdown-content">
                                <div class="search-box">
                                    <input type="text" class="form-control" placeholder="Type to search...">
                                </div>
                                <div class="dropdown-list">
                                    <?php
                                    $stmt = $conn->query("SELECT NIK, employee_name FROM employee_active ORDER BY employee_name");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $display = htmlspecialchars($row['NIK']) . ' - ' . htmlspecialchars($row['employee_name']);
                                        echo "<a href='#' data-value='" . htmlspecialchars($row['NIK']) . "'>" . $display . "</a>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" class="form-control" id="role" name="role" readonly>
                    </div>

                    <!-- Tenure -->
                    <div class="form-group">
                        <label for="tenure">Tenure</label>
                        <input type="text" class="form-control" id="tenure" name="tenure" readonly>
                    </div>

                    <!-- Add after Tenure field -->
                    <div class="form-group">
                        <label for="case_chronology">Case Chronology</label>
                        <textarea class="form-control" id="case_chronology" name="case_chronology" rows="3"><?php 
                            echo $isEdit ? htmlspecialchars($ruleData['case_chronology']) : ''; 
                        ?></textarea>
                    </div>

                    <!-- CCS Rules -->
                    <div class="form-group">
                        <label for="ccs_rule">CCS Rule</label>
                        <select class="form-control" id="ccs_rule" name="ccs_rule" required>
                            <option value="">Select CCS Rule</option>
                            <?php
                            $ccsRules = [
                                'WR1' => 'Written Reminder 1',
                                'WR2' => 'Written Reminder 2',
                                'WR3' => 'Written Reminder 3',
                                'WL1' => 'Warning Letter 1',
                                'WL2' => 'Warning Letter 2',
                                'WL3' => 'Warning Letter 3',
                                'FLW' => 'First & Last Warning Letter'
                            ];
                            foreach ($ccsRules as $value => $label) {
                                $selected = ($isEdit && $ruleData['consequences'] === $value) ? 'selected' : '';
                                echo "<option value='$value' $selected>$label</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Effective Date -->
                    <div class="form-group">
                        <label for="effective_date">Effective Date</label>
                        <input type="date" class="form-control" id="effective_date" name="effective_date" required>
                    </div>

                    <!-- After Effective Date field -->
                    <div class="form-group">
                        <label for="document">Supporting Document</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="document" name="document" accept=".pdf,.xlsx,.xls" required>
                            <label class="custom-file-label" for="document">Choose file</label>
                        </div>
                        <small class="form-text text-muted">Accepted formats: PDF, Excel (.xlsx, .xls)</small>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>



<script>
function toggleDropdown() {
    var dropdown = document.getElementById("nameDropdown");
    dropdown.classList.toggle("show");
    if (dropdown.classList.contains("show")) {
        dropdown.querySelector("input").focus();
    }
}

function filterFunction() {
    var input = document.querySelector(".search-box input");
    var filter = input.value.toUpperCase();
    var items = document.querySelectorAll('.dropdown-list a');
    
    items.forEach(item => {
        var text = item.textContent || item.innerText;
        item.style.display = text.toUpperCase().indexOf(filter) > -1 ? "" : "none";
    });
}

// Add event listeners
document.querySelector(".search-box input").addEventListener("keyup", filterFunction);

// Handle item selection
document.querySelectorAll('.dropdown-list a').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        const nik = this.getAttribute('data-value');
        const fullText = this.textContent;
        const name = fullText.split(' - ')[1].trim();
        
        // Set the values
        document.getElementById('searchInput').value = fullText;
        document.getElementById('name').value = name;  // Set employee name
        document.getElementById('nik').value = nik;    // Set NIK
        
        // Close dropdown
        document.getElementById("nameDropdown").classList.remove("show");
        
        // Debug log
        console.log('Selected:', {
            name: document.getElementById('name').value,
            nik: document.getElementById('nik').value,
            searchInput: document.getElementById('searchInput').value
        });
        
        // Fetch role and tenure
        fetch(`../controller/get_employee_role.php?nik=${nik}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('role').value = data.role;
                    document.getElementById('tenure').value = data.tenure;
                }
            });
    });
});

// Close dropdown when clicking outside
window.onclick = function(event) {
    if (!event.target.matches('#searchInput') && !event.target.matches('.search-box input')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        Array.from(dropdowns).forEach(dropdown => {
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });
    }
}

$(document).ready(function() {
    // Initialize Select2 with custom matcher
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true
    });

    // Update form submission handler
    $('#ccsRulesForm').on('submit', function() {
        // Check required fields
        if (!$('#name').val()) {
            alert('Please select an employee');
            return false;
        }
        // Let form submit if validation passes
        return true;
    });

    // File input handler
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
        
        // Validate file type
        var fileExtension = fileName.split('.').pop().toLowerCase();
        if (!['pdf', 'xlsx', 'xls'].includes(fileExtension)) {
            alert('Please upload only PDF or Excel files');
            $(this).val('');
            $(this).next('.custom-file-label').html('Choose file');
        }
    });
});

// Move calculateEndDate outside document.ready
function calculateEndDate() {
    const effectiveDate = $('#effective_date').val();
    const ccsRule = $('#ccs_rule').val();
    
    console.log('Calculating end date:', effectiveDate, ccsRule); // Debug log
    
    if (effectiveDate && ccsRule) {
        const startDate = new Date(effectiveDate);
        let endDate = new Date(startDate);
        
        // Calculate end date based on rule type
        if (ccsRule.startsWith('WR')) {
            // Written Reminder: 1 year
            endDate.setFullYear(endDate.getFullYear() + 1);
            endDate.setDate(endDate.getDate() - 1); // Subtract 1 day to get exactly 1 year
            console.log('Written Reminder - 1 year:', endDate); // Debug log
        } else if (ccsRule.startsWith('WL') || ccsRule === 'FLW') {
            // Warning Letter: 6 months
            endDate.setMonth(endDate.getMonth() + 6);
            endDate.setDate(endDate.getDate() - 1); // Subtract 1 day to get exactly 6 months
            console.log('Warning Letter - 6 months:', endDate); // Debug log
        }
        
        // Format date as YYYY-MM-DD for input
        const formattedDate = endDate.toISOString().split('T')[0];
        $('#end_date').val(formattedDate);
        console.log('Set end date to:', formattedDate); // Debug log
    }
}

// Add this to update debug values
function updateDebug() {
    const debugValues = {
        name: document.getElementById('name').value,
        nik: document.getElementById('nik').value,
        searchInput: document.getElementById('searchInput').value,
        role: document.getElementById('role').value,
        tenure: document.getElementById('tenure').value
    };
    document.getElementById('debugValues').textContent = JSON.stringify(debugValues, null, 2);
}

// Call this after setting values in the click handler
setInterval(updateDebug, 1000);

// Pre-fill values when editing
<?php if ($isEdit): ?>
    <script>
    $(document).ready(function() {
        // Pre-fill the form fields
        $('#project').val('<?php echo htmlspecialchars($ruleData['project']); ?>').trigger('change');
        $('#searchInput').val('<?php echo htmlspecialchars($ruleData['nik'] . " - " . $ruleData['name']); ?>');
        $('#name').val('<?php echo htmlspecialchars($ruleData['name']); ?>');
        $('#nik').val('<?php echo htmlspecialchars($ruleData['nik']); ?>');
        $('#role').val('<?php echo htmlspecialchars($ruleData['role']); ?>');
        $('#tenure').val('<?php echo htmlspecialchars($ruleData['tenure']); ?>');
        $('#case_chronology').val('<?php echo htmlspecialchars($ruleData['case_chronology']); ?>');
        $('#ccs_rule').val('<?php echo htmlspecialchars($ruleData['consequences']); ?>');
        $('#effective_date').val('<?php echo htmlspecialchars($ruleData['effective_date']); ?>');
        
        // Make document upload optional when editing
        $('#document').removeAttr('required');
    });
    </script>
<?php endif; ?>
</script>

<style>
.dropdown {
    position: relative;
    width: 100%;
}

.dropdown-content {
    display: none;
    position: absolute;
    width: 100%;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 2px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    z-index: 1050;
}

.search-box {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.search-box input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.dropdown-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 5px 0;
}

.dropdown-list a {
    padding: 8px 15px;
    display: block;
    color: #333;
    text-decoration: none;
}

.dropdown-list a:hover {
    background-color: #f8f9fa;
}

.show {
    display: block;
}

.custom-file {
    margin-bottom: 10px;
}

.custom-file-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.custom-file-label::after {
    content: "Browse";
}
</style>

<?php
$content = ob_get_clean();
require_once '../main_navbar.php';
?>

<!-- Put your JavaScript here, after main_navbar.php which includes jQuery -->
<script>
// Your JavaScript code here...
</script>
