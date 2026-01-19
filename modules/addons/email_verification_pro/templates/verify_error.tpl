<div class="verification-page">
    <div class="verification-container error">
        <div class="verification-icon error">
            <i class="fas fa-times-circle fa-4x"></i>
        </div>
        
        <h1>Verification Failed</h1>
        
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> {$error}
        </div>
        
        <p>
            This can happen if:
        </p>
        <ul class="error-reasons">
            <li>The verification link has expired</li>
            <li>The link has already been used</li>
            <li>The link was copied incorrectly</li>
        </ul>
        
        {if $showResend}
            <hr>
            <h4>Request a New Verification Link</h4>
            <p>
                <a href="clientarea.php?action=login" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Login to Resend Verification
                </a>
            </p>
        {/if}
        
        <p class="mt-4">
            <small>
                Need help? <a href="submitticket.php">Contact Support</a>
            </small>
        </p>
    </div>
</div>

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
    
    .verification-icon.error {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }
    
    .verification-container h1 {
        color: #dc3545;
        margin-bottom: 20px;
    }
    
    .error-reasons {
        text-align: left;
        display: inline-block;
        margin: 20px 0;
    }
    
    .error-reasons li {
        color: #666;
        margin-bottom: 10px;
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
    }
</style>
