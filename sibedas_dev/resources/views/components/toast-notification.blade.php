<div class="toast-container position-fixed end-0 top-0 p-3">
    <div id="toastNotification" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <div class="auth-logo me-auto">
            </div>
            <small class="text-muted">{{now()->format("Y-m-d H:i:s")}}</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <p id="toast-message"></p>
        </div>
    </div>
</div>