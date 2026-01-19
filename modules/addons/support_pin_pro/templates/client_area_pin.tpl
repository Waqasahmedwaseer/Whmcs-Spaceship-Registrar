<div class="support-pin-container">
    <div class="card shadow-sm border-0 rounded-lg overflow-hidden">
        <div class="card-header bg-primary text-white p-4">
            <h3 class="m-0 font-weight-bold"><i class="fas fa-shield-alt mr-2"></i> Support Verification PIN</h3>
            <p class="mb-0 opacity-75 mt-1">Use this PIN to verify your identity when contacting our support team.</p>
        </div>
        <div class="card-body p-5 text-center">
            {if $success === 'generated'}
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fas fa-check-circle mr-1"></i> New Support PIN has been generated successfully!
                </div>
            {/if}

            {if $error}
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {$error}
                </div>
            {elseif $activePin}
                <div class="pin-display-wrapper mb-4">
                    <div class="pin-label text-muted text-uppercase small font-weight-bold mb-2">Active Support PIN</div>
                    <div class="pin-code display-4 font-weight-bold text-dark p-3 bg-light rounded border">
                        {$pinValue}
                    </div>
                    {if $expiresAt}
                        <div class="pin-expiry mt-3 text-muted">
                            <i class="far fa-clock mr-1"></i> Expires on: <strong>{$expiresAt|date_format:"%b %e, %Y at %H:%M"}</strong>
                        </div>
                    {else}
                        <div class="pin-expiry mt-3 text-success font-weight-bold">
                            <i class="fas fa-infinity mr-1"></i> Never Expires
                        </div>
                    {/if}
                </div>
            {else}
                <div class="no-pin-wrapper mb-4">
                    <i class="fas fa-lock-open fa-4x text-light mb-3"></i>
                    <h4 class="text-muted">No Active PIN Found</h4>
                    <p>Generate a PIN to easily verify your account with our staff.</p>
                </div>
            {/if}

            {if $enableGenerate}
                <hr class="my-4">
                <a href="index.php?m=support_pin_pro&action=generate" class="btn btn-primary btn-lg px-5 shadow-sm rounded-pill">
                    <i class="fas fa-sync-alt mr-2"></i> {if $activePin}Generate New PIN{else}Generate Support PIN{/if}
                </a>
            {/if}
        </div>
        <div class="card-footer bg-light p-4 text-muted small">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <i class="fas fa-info-circle mr-1"></i> <strong>Security Tip:</strong> Never share your Support PIN with anyone other than our verified staff members via official support channels.
                </div>
                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    Module v1.0.0
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.support-pin-container .card {
    transition: all 0.3s ease;
}
.support-pin-container .pin-code {
    letter-spacing: 5px;
    background: linear-gradient(145deg, #f8f9fa, #e9ecef) !important;
    text-shadow: 1px 1px 0px #fff;
}
.support-pin-container .display-4 {
    font-size: 3.5rem;
}
.support-pin-container .opacity-75 {
    opacity: 0.85;
}
</style>
