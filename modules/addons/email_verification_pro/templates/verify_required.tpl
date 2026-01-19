<div class="verification-page">
    <div class="verification-container">
        <div class="verification-icon pending">
            <i class="fas fa-envelope-open-text fa-4x"></i>
        </div>
        
        <h1>Verify Your Email Address</h1>
        
        {if $resendMessage}
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> {$resendMessage}
            </div>
        {/if}
        
        <p class="lead">
            We've sent a verification email to <strong>{$maskedEmail}</strong>
        </p>
        
        <p>
            Please check your inbox and click the verification link to activate your account.
            {if $tokenExpired}
                <br><span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Your verification link has expired. Please request a new one below.</span>
            {/if}
        </p>
        
        <div class="verification-steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-text">Check your email inbox</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-text">Click the verification link</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">Start using your account</div>
            </div>
        </div>
        
        <hr>
        
        <h4>Didn't receive the email?</h4>
        <p>Check your spam folder, or request a new verification email:</p>
        
        <form method="post" id="resendForm">
            <input type="hidden" name="action" value="resend">
            
            {if $captchaType == 'turnstile' && $turnstileSiteKey}
                <div class="cf-turnstile" data-sitekey="{$turnstileSiteKey}" data-callback="onTurnstileSuccess"></div>
            {/if}
            
            <button type="submit" class="btn btn-primary btn-lg" id="resendBtn">
                <i class="fas fa-paper-plane"></i> Resend Verification Email
            </button>
        </form>
        
        {if $allowSupportTickets}
            <p class="mt-4">
                <small>
                    Need help? <a href="submitticket.php">Contact Support</a>
                </small>
            </p>
        {/if}
        
        <p class="mt-3">
            <a href="logout.php" class="text-muted">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </p>
    </div>
</div>

{if $captchaType == 'recaptcha_v3' && $recaptchaSiteKey}
<script>
    document.getElementById('resendForm').addEventListener('submit', function(e) {
        e.preventDefault();
        grecaptcha.ready(function() {
            grecaptcha.execute('{$recaptchaSiteKey}', {literal}{action: 'resend'}{/literal}).then(function(token) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'g-recaptcha-response';
                input.value = token;
                document.getElementById('resendForm').appendChild(input);
                document.getElementById('resendForm').submit();
            });
        });
    });
</script>
{/if}

{if $captchaType == 'turnstile' && $turnstileSiteKey}
<script>
    document.getElementById('resendBtn').disabled = true;
    function onTurnstileSuccess(token) {
        document.getElementById('resendBtn').disabled = false;
    }
</script>
{/if}

<style>
    .verification-page {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    
    .verification-container {
        max-width: 600px;
        text-align: center;
        background: #fff;
        padding: 50px;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    .verification-icon {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
    }
    
    .verification-icon.pending {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .verification-container h1 {
        color: #333;
        margin-bottom: 20px;
    }
    
    .verification-container .lead {
        font-size: 1.1rem;
        color: #666;
    }
    
    .verification-steps {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin: 30px 0;
        flex-wrap: wrap;
    }
    
    .step {
        text-align: center;
    }
    
    .step-number {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
    }
    
    .step-text {
        font-size: 0.9rem;
        color: #666;
    }
    
    .verification-container hr {
        margin: 30px 0;
        border-color: #eee;
    }
    
    .verification-container .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 15px 40px;
        font-size: 1.1rem;
        border-radius: 30px;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .verification-container .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }
    
    .cf-turnstile {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }
</style>
