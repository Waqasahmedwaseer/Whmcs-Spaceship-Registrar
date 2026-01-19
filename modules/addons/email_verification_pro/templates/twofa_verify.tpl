<div class="verification-page">
    <div class="verification-container twofa">
        <div class="verification-icon twofa">
            <i class="fas fa-shield-alt fa-4x"></i>
        </div>
        
        <h1>Two-Factor Authentication</h1>
        
        {if $error}
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> {$error}
            </div>
        {/if}
        
        <p class="lead">
            A verification code has been sent to <strong>{$maskedEmail}</strong>
        </p>
        
        <p>Please enter the {$codeLength}-digit code to verify your identity and complete your login.</p>
        
        <form method="post" action="index.php?m=email_verification_pro&action=twofa">
            <div class="twofa-code-input">
                <input type="text" name="code" maxlength="{if $codeLength}{$codeLength}{else}6{/if}" placeholder="Enter code" required autocomplete="one-time-code" autofocus>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">
                <i class="fas fa-lock-open"></i> Verify & Login
            </button>
        </form>

        <hr>

        <div class="twofa-footer">
            <p>Didn't receive the code?</p>
            <form method="post" action="index.php?m=email_verification_pro&action=twofa">
                <input type="hidden" name="resend" value="1">
                <button type="submit" class="btn btn-link">Resend Verification Code</button>
            </form>
            
            <p class="mt-3">
                <a href="logout.php" class="text-muted">
                    <i class="fas fa-sign-out-alt"></i> Cancel & Logout
                </a>
            </p>
        </div>
    </div>
</div>

<style>
    .verification-page {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    
    .verification-container {
        max-width: 500px;
        width: 100%;
        text-align: center;
        background: #fff;
        padding: 50px 40px;
        border-radius: 20px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.1);
    }
    
    .verification-icon.twofa {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
    }
    
    .twofa-code-input input {
        width: 100%;
        font-size: 32px;
        text-align: center;
        letter-spacing: 15px;
        padding: 15px;
        border: 2px solid #eee;
        border-radius: 12px;
        outline: none;
        transition: border-color 0.3s;
        font-family: 'Courier New', Courier, monospace;
        font-weight: bold;
    }
    
    .twofa-code-input input:focus {
        border-color: #4facfe;
        box-shadow: 0 0 15px rgba(79, 172, 254, 0.1);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        border: none;
        padding: 15px;
        font-weight: bold;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(79, 172, 254, 0.4);
    }
</style>
