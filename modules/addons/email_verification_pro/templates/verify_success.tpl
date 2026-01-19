<div class="verification-page">
    <div class="verification-container success">
        <div class="verification-icon success">
            <i class="fas fa-check-circle fa-4x"></i>
        </div>
        
        <h1>Email Verified Successfully!</h1>
        
        <p class="lead">
            Your email address has been verified. You can now access all features of your account.
        </p>
        
        <div class="confetti-animation">
            <div class="confetti"></div>
            <div class="confetti"></div>
            <div class="confetti"></div>
            <div class="confetti"></div>
            <div class="confetti"></div>
        </div>
        
        <p>Redirecting you to your account...</p>
        
        <a href="{$redirectUrl}" class="btn btn-success btn-lg mt-3">
            <i class="fas fa-arrow-right"></i> Continue to My Account
        </a>
    </div>
</div>

<script>
    // Auto redirect after 3 seconds
    setTimeout(function() {
        window.location.href = '{$redirectUrl}';
    }, 3000);
</script>

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
        position: relative;
        overflow: hidden;
    }
    
    .verification-icon {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
        animation: scaleIn 0.5s ease-out;
    }
    
    .verification-icon.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }
    
    @keyframes scaleIn {
        from {
            transform: scale(0);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    .verification-container h1 {
        color: #28a745;
        margin-bottom: 20px;
    }
    
    .verification-container .lead {
        font-size: 1.1rem;
        color: #666;
    }
    
    .verification-container .btn-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        border: none;
        padding: 15px 40px;
        font-size: 1.1rem;
        border-radius: 30px;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .verification-container .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(17, 153, 142, 0.4);
    }
    
    /* Confetti Animation */
    .confetti-animation {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        overflow: hidden;
    }
    
    .confetti {
        width: 10px;
        height: 10px;
        position: absolute;
        top: -10px;
        animation: fall 3s linear infinite;
    }
    
    .confetti:nth-child(1) { left: 10%; background: #ff6b6b; animation-delay: 0s; }
    .confetti:nth-child(2) { left: 30%; background: #feca57; animation-delay: 0.5s; }
    .confetti:nth-child(3) { left: 50%; background: #48dbfb; animation-delay: 1s; }
    .confetti:nth-child(4) { left: 70%; background: #ff9ff3; animation-delay: 1.5s; }
    .confetti:nth-child(5) { left: 90%; background: #54a0ff; animation-delay: 2s; }
    
    @keyframes fall {
        0% {
            top: -10px;
            transform: rotate(0deg) scale(1);
            opacity: 1;
        }
        100% {
            top: 100%;
            transform: rotate(720deg) scale(0.5);
            opacity: 0;
        }
    }
</style>
