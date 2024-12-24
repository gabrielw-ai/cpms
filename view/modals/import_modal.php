<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Employee Data</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="../controller/c_import_employee.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Excel File (.xlsx)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file" accept=".xlsx" required>
                            <label class="custom-file-label">Choose file</label>
                        </div>
                        <small class="form-text text-muted">
                            Download the template <a href="../controller/c_export_employee.php?template=1">here</a>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div> 