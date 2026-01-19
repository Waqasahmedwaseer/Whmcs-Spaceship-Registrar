<div class="verification-page">
    <div class="verification-container">
        <div class="verification-icon">
            <i class="fas fa-user-lock fa-4x"></i>
        </div>
        
        <h1>Login Required</h1>
        
        <p class="lead">
            Please log in to your account to verify your email address.
        </p>
        
        <a href="clientarea.php?action=login" class="btn btn-primary btn-lg mt-3">
            <i class="fas fa-sign-in-alt"></i> Login to Your Account
        </a>
        
        <p class="mt-4">
            <small>
                Don't have an account? <a href="register.php">Register Now</a>
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
    }
    
    .verification-container h1 {
        color: #333;
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
