<?php
/**
 * HP Personal Expense Tracker — Footer Include
 * includes/footer.php
 * ============================================================
 * Closes the HTML document, loads all JS assets.
 *
 * Expected variables (optional, set by including page):
 *   $include_charts (bool)  — true to load Chart.js CDN
 *   $page_scripts   (array) — additional JS files to load
 */
?>
    </div><!-- /.page-content -->
</div><!-- /.main-wrapper -->

<!-- ── Global Confirm Delete Modal ──────────────────────── -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4 px-3">
                <div class="mb-3">
                    <div style="width:56px;height:56px;background:var(--danger-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:1.5rem;"></i>
                    </div>
                </div>
                <h6 class="fw-bold mb-2">Confirm Delete</h6>
                <p class="text-muted mb-0" id="confirmModalBody" style="font-size:0.875rem;">
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer justify-content-center gap-2 border-0 pt-0 pb-4">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm px-4"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="button"
                        class="btn btn-danger btn-sm px-4"
                        id="confirmModalOk">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Toast Container ───────────────────────────────────── -->
<div id="toast-container"></div>

<!-- ── jQuery ───────────────────────────────────────────── -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>

<!-- ── Bootstrap 5 JS Bundle ────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmLRFLgOeJw9Y+OYQbG9c5OP2CA6+"
        crossorigin="anonymous"></script>

<?php if (!empty($include_charts)): ?>
<!-- ── Chart.js ──────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?php endif; ?>

<!-- ── HP Global Application JS ─────────────────────────── -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<?php
// Page-specific JS files
if (!empty($page_scripts) && is_array($page_scripts)):
    foreach ($page_scripts as $js):
?>
<script src="<?= BASE_URL . '/' . e($js) ?>"></script>
<?php
    endforeach;
endif;
?>

<!-- Inline: update sidebar toggle icon direction based on state -->
<script>
(function() {
    const sidebar  = document.getElementById('appSidebar');
    const icon     = document.getElementById('sidebarToggleIcon');
    if (!sidebar || !icon) return;

    function updateIcon() {
        const isCollapsed = sidebar.classList.contains('collapsed');
        icon.className = isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
    }
    updateIcon();

    // Re-check when toggle is clicked (app.js handles the class toggle)
    document.getElementById('sidebarToggleBtn')?.addEventListener('click', updateIcon);
})();
</script>

</body>
</html>
