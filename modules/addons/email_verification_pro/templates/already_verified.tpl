<div class="verification-page">
    <div class="verification-container">
        <div class="verification-icon success">
            <i class="fas fa-check-double fa-4x"></i>
        </div>
        
        <h1>Already Verified</h1>
        
        <p class="lead">
            Your email address has already been verified. You have full access to your account.
        </p>
        
        <a href="{$redirectUrl}" class="btn btn-primary btn-lg mt-3">
            <i class="fas fa-arrow-right"></i> Go to My Account
        </a>
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
    
    .verification-icon.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }
    
    .verification-container h1 {
        color: #28a745;
        margin-bottom: 20px;
    }
    
    .verification-container .lead {
        font-size: 1.1rem;
        color: #666;
    }
    
    .verification-container .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 15px 40px;
        font-size: 1.1rem;
        border-radius: 30px;
    }
</style>
