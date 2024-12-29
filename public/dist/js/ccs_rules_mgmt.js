// Wait for document ready and ensure jQuery is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Function to initialize everything once jQuery is ready
    function initializeComponents() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initializeComponents, 100);
            return;
        }

        $(function() {
            // Initialize Select2 for project
            $("#project").select2({
                theme: "bootstrap4",
                width: "100%",
                placeholder: "Select a project",
                allowClear: true
            });

            // Project change handler
            $("#project").on("change", function() {
                const selectedProject = $(this).val();
                console.log('Selected project:', selectedProject);
                
                if (selectedProject) {
                    // Your existing code...
                }
            });

            // Function to get today's date in YYYY-MM-DD format
            function getTodayDate() {
                const today = new Date();
                return today.toISOString().split('T')[0];
            }

            // Set initial max date
            $('#effective_date').attr('max', getTodayDate());

            // Validate effective date on change
            $('#effective_date').on('change', function() {
                var selectedDate = new Date($(this).val());
                var today = new Date();
                today.setHours(0, 0, 0, 0);

                if (selectedDate > today) {
                    alert('Effective date cannot be later than today');
                    $(this).val(getTodayDate());
                }
            });

            // Calculate end date based on CCS rule selection
            $('#ccs_rule').on('change', function() {
                const effectiveDate = $('#effective_date').val();
                if (effectiveDate) {
                    calculateEndDate();
                }
            });

            $('#effective_date').on('change', function() {
                const ccsRule = $('#ccs_rule').val();
                if (ccsRule) {
                    calculateEndDate();
                }
            });

            function calculateEndDate() {
                const effectiveDate = $('#effective_date').val();
                const ccsRule = $('#ccs_rule').val();
                
                if (!effectiveDate || !ccsRule) return;

                const date = new Date(effectiveDate);
                
                if (ccsRule.startsWith('WL')) {
                    // Warning Letters: Add 6 months
                    date.setMonth(date.getMonth() + 6);
                } else if (ccsRule.startsWith('WR')) {
                    // Written Reminders: Add 1 year
                    date.setFullYear(date.getFullYear() + 1);
                } else if (ccsRule === 'FLW') {
                    // First & Last Warning: Add 6 months
                    date.setMonth(date.getMonth() + 6);
                }

                // Add hidden input for end date if it doesn't exist
                if ($('#end_date').length === 0) {
                    $('<input>').attr({
                        type: 'hidden',
                        id: 'end_date',
                        name: 'end_date'
                    }).appendTo('#ccsRulesForm');
                }

                $('#end_date').val(date.toISOString().split('T')[0]);
            }

            // Initialize other Select2 elements
            $('.select2').select2({
                theme: 'bootstrap4'
            });

            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

            // Initialize custom file input
            if (typeof bsCustomFileInput !== 'undefined') {
                bsCustomFileInput.init();
            }
        });
    }

    // Start initialization
    initializeComponents();
}); 