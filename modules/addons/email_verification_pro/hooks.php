<?php
/**
 * Email Verification Pro - Hooks
 *
 * This file contains all WHMCS hooks for email verification functionality.
 *
 * @copyright 2026
 * @license Proprietary
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

// Include the helper classes
require_once __DIR__ . '/lib/EmailVerificationHelper.php';
require_once __DIR__ . '/lib/TwoFactorAuthHelper.php';

use WHMCS\Module\Addon\EmailVerificationPro\EmailVerificationHelper;
use WHMCS\Module\Addon\EmailVerificationPro\TwoFactorAuthHelper;

/**
 * Hook: ClientAdd
 * Triggered when a new client is created
 */
add_hook('ClientAdd', 1, function($vars) {
    $clientId = $vars['userid'];
    $email = $vars['email'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // Check if module is active
    if (!EmailVerificationHelper::isModuleActive()) {
        return;
    }

    // Check if email domain is banned
    if (EmailVerificationHelper::isEmailBanned($email)) {
        // Log and potentially close the account
        EmailVerificationHelper::logAction($clientId, $email, 'registration_blocked', $ip, 'Banned email address');
        return;
    }

    // Check if IP is banned
    if (EmailVerificationHelper::isIpBanned($ip)) {
        EmailVerificationHelper::logAction($clientId, $email, 'registration_blocked', $ip, 'Banned IP address');
        return;
    }

    // Create verification token and send email
    EmailVerificationHelper::createVerificationToken($clientId, $email);
    EmailVerificationHelper::sendVerificationEmail($clientId);

    // Log registration
    EmailVerificationHelper::logAction($clientId, $email, 'registration', $ip, 'New account created, verification email sent');
});

/**
 * Hook: ClientAreaPageHome
 * Block access to client area home if not verified
 */
add_hook('ClientAreaPageHome', 1, function($vars) {
    return EmailVerificationHelper::checkVerificationRequired($vars, 'home');
});

/**
 * Hook: ClientAreaPageCart
 * Handle checkout verification
 */
add_hook('ClientAreaPageCart', 1, function($vars) {
    $config = EmailVerificationHelper::getModuleConfig();
    
    if ($config['verification_type'] === 'checkout' || $config['verification_type'] === 'all_pages') {
        // Check at checkout steps
        $step = $_GET['a'] ?? '';
        if (in_array($step, ['checkout', 'complete', 'view'])) {
            return EmailVerificationHelper::checkVerificationRequired($vars, 'cart');
        }
    }
    
    return [];
});

/**
 * Hook: ClientAreaPage
 * Generic hook for all client area pages
 * Handles both email verification blocking AND 2FA enforcement
 */
add_hook('ClientAreaPage', 1, function($vars) {
    // Prevent hook from running multiple times in same request
    static $hookRan = false;
    if ($hookRan) {
        return;
    }

    if (!EmailVerificationHelper::isModuleActive()) {
        return;
    }

    $config = EmailVerificationHelper::getModuleConfig();
    $clientId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;

    if ($clientId <= 0) {
        return; // User is not logged in.
    }

    $filename = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $module = isset($_GET['m']) ? $_GET['m'] : '';

    // Always allow these pages without any checks
    $allowedPages = ['logout.php', 'dologout.php'];
    $allowedActions = ['logout'];

    if (in_array($filename, $allowedPages) || in_array($action, $allowedActions)) {
        return;
    }

    // Check if we're on the email verification pro module pages - ALWAYS allow these
    if ($module === 'email_verification_pro') {
        $hookRan = true; // Mark as ran to prevent re-entry
        return;
    }

    $isVerified = EmailVerificationHelper::isClientVerified($clientId);
    $is2faPending = !empty($_SESSION['evp_2fa_pending']);

    // --- Priority 1: Handle 2FA FIRST (this runs regardless of verification_type) ---
    // If 2FA is pending, user MUST complete 2FA before accessing anything else
    if ($is2faPending) {
        $hookRan = true;
        // Redirect to 2FA page
        if (headers_sent()) {
            echo '<script>window.location.href="index.php?m=email_verification_pro&action=twofa";</script>';
            exit;
        } else {
            header('Location: index.php?m=email_verification_pro&action=twofa');
            exit;
        }
    }

    // --- Priority 2: Handle email verification (only for all_pages mode) ---
    if (($config['verification_type'] ?? 'checkout') === 'all_pages') {
        if (!$isVerified) {
            $hookRan = true;
            // Redirect to verification page
            if (headers_sent()) {
                echo '<script>window.location.href="index.php?m=email_verification_pro&action=verify";</script>';
                exit;
            } else {
                header('Location: index.php?m=email_verification_pro&action=verify');
                exit;
            }
        }
    }

    // If the user is verified and has no pending 2FA, all good.
    return;
});

/**
 * Hook: ShoppingCartValidateCheckout
 * Validate checkout - prevent if not verified (checkout mode)
 */
add_hook('ShoppingCartValidateCheckout', 1, function($vars) {
    if (!EmailVerificationHelper::isModuleActive()) {
        return [];
    }

    $config = EmailVerificationHelper::getModuleConfig();
    
    if ($config['verification_type'] !== 'checkout') {
        return [];
    }

    $clientId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
    
    if ($clientId <= 0) {
        return [];
    }

    if (!EmailVerificationHelper::isClientVerified($clientId)) {
        return [
            'Please verify your email address before placing an order. <a href="index.php?m=email_verification_pro&action=verify">Click here to verify</a>.'
        ];
    }

    return [];
});

/**
 * Hook: ClientAreaHeadOutput
 * Add CSS/JS to client area
 */
add_hook('ClientAreaHeadOutput', 1, function($vars) {
    if (!EmailVerificationHelper::isModuleActive()) {
        return '';
    }

    $config = EmailVerificationHelper::getModuleConfig();
    $output = '';

    // Add reCAPTCHA script if configured
    if ($config['captcha_type'] === 'recaptcha_v3' && !empty($config['recaptcha_site_key'])) {
        $output .= '<script src="https://www.google.com/recaptcha/api.js?render=' . htmlspecialchars($config['recaptcha_site_key']) . '"></script>';
    }

    // Add Turnstile script if configured
    if ($config['captcha_type'] === 'turnstile' && !empty($config['turnstile_site_key'])) {
        $output .= '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    return $output;
});

/**
 * Hook: ClientDetailsValidation
 * Prevent email change if not verified and lock is enabled
 */
add_hook('ClientDetailsValidation', 1, function($vars) {
    if (!EmailVerificationHelper::isModuleActive()) {
        return [];
    }

    $config = EmailVerificationHelper::getModuleConfig();
    
    if (!$config['lock_email_change']) {
        return [];
    }

    $clientId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
    
    if ($clientId <= 0) {
        return [];
    }

    // Check if client is verified
    if (EmailVerificationHelper::isClientVerified($clientId)) {
        return [];
    }

    // Check if email is being changed
    $client = Capsule::table('tblclients')->where('id', $clientId)->first();
    if ($client && isset($vars['email']) && strtolower($vars['email']) !== strtolower($client->email)) {
        return ['You cannot change your email address until your current email is verified.'];
    }

    return [];
});

/**
 * Hook: DailyCronJob
 * Handle auto-terminate and reminder emails
 */
add_hook('DailyCronJob', 1, function($vars) {
    if (!EmailVerificationHelper::isModuleActive()) {
        return;
    }

    $config = EmailVerificationHelper::getModuleConfig();

    // Get unverified tokens
    $unverified = Capsule::table('mod_email_verification_tokens')
        ->where('verified', false)
        ->get();

    foreach ($unverified as $token) {
        $daysSinceCreation = (time() - strtotime($token->created_at)) / 86400;

        // Send reminder
        $reminderDays = (int) $config['resend_reminder_days'];
        if ($reminderDays > 0 && $daysSinceCreation >= $reminderDays) {
            $lastResend = $token->last_resend_at ? strtotime($token->last_resend_at) : 0;
            $daysSinceReminder = (time() - $lastResend) / 86400;
            
            if ($daysSinceReminder >= $reminderDays) {
                EmailVerificationHelper::resendVerificationEmail($token->client_id);
            }
        }

        // Auto-terminate
        if ($config['auto_terminate_enabled']) {
            $terminateDays = (int) $config['auto_terminate_days'];
            if ($terminateDays > 0 && $daysSinceCreation >= $terminateDays) {
                // Close the account
                Capsule::table('tblclients')
                    ->where('id', $token->client_id)
                    ->update(['status' => 'Closed']);

                EmailVerificationHelper::logAction(
                    $token->client_id,
                    $token->email,
                    'auto_closed',
                    'CRON',
                    "Account closed after {$terminateDays} days without verification"
                );
            }
        }

        // Auto-delete
        if ($config['auto_delete_enabled']) {
            $deleteDays = (int) $config['auto_delete_days'];
            if ($deleteDays > 0 && $daysSinceCreation >= $deleteDays) {
                // Check if client has any active orders
                $hasOrders = Capsule::table('tblorders')
                    ->where('userid', $token->client_id)
                    ->where('status', 'Active')
                    ->exists();

                if (!$hasOrders) {
                    // Delete the client
                    EmailVerificationHelper::logAction(
                        $token->client_id,
                        $token->email,
                        'auto_deleted',
                        'CRON',
                        "Account deleted after {$deleteDays} days without verification"
                    );

                    // Delete verification token
                    Capsule::table('mod_email_verification_tokens')
                        ->where('client_id', $token->client_id)
                        ->delete();

                    // Delete the client (this is permanent!)
                    Capsule::table('tblclients')
                        ->where('id', $token->client_id)
                        ->delete();
                }
            }
        }
    }

    logActivity('Email Verification Pro: Daily cron job completed');
});

/**
 * Hook: ClientLogin
 * Check verification status on login and trigger 2FA if required
 * Priority 1 to run early
 */
add_hook('ClientLogin', 1, function($vars) {
    if (!EmailVerificationHelper::isModuleActive()) {
        return;
    }

    $clientId = $vars['userid'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // Log login attempt
    $client = Capsule::table('tblclients')->where('id', $clientId)->first();
    if ($client) {
        EmailVerificationHelper::logAction($clientId, $client->email, 'login', $ip);
    }

    // Clear any existing 2FA state first
    unset($_SESSION['evp_2fa_pending'], $_SESSION['evp_2fa_client_id']);

    // Clear the TwoFactorAuthHelper config cache to get fresh settings
    TwoFactorAuthHelper::clearConfigCache();

    // Only trigger 2FA if the client is already email verified
    $isVerified = EmailVerificationHelper::isClientVerified($clientId);
    $is2faRequired = TwoFactorAuthHelper::isRequired($clientId);

    // Log for debugging
    EmailVerificationHelper::logAction(
        $clientId,
        $client->email ?? '',
        '2fa_check',
        $ip,
        "Verified: " . ($isVerified ? 'yes' : 'no') . ", 2FA Required: " . ($is2faRequired ? 'yes' : 'no')
    );

    if ($isVerified && $is2faRequired) {
        // Generate and send code
        $code = TwoFactorAuthHelper::createCode($clientId, $client->email);
        $sent = TwoFactorAuthHelper::sendCode($clientId, $code);

        if ($sent) {
            // Store status in session
            $_SESSION['evp_2fa_pending'] = true;
            $_SESSION['evp_2fa_client_id'] = $clientId;

            // Log that 2FA is initiated
            EmailVerificationHelper::logAction($clientId, $client->email, '2fa_initiated', $ip, 'Code sent, awaiting verification');

            // Use JavaScript redirect if headers already sent, otherwise use header
            if (headers_sent()) {
                echo '<script>window.location.href="index.php?m=email_verification_pro&action=twofa";</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=index.php?m=email_verification_pro&action=twofa"></noscript>';
                exit;
            } else {
                header('Location: index.php?m=email_verification_pro&action=twofa');
                exit;
            }
        } else {
            // Email failed to send - log but allow login (fallback)
            EmailVerificationHelper::logAction($clientId, $client->email ?? '', '2fa_email_failed', $ip, 'Failed to send 2FA code, login allowed');
        }
    }
});

/**
 * Hook: PreRegistrarRegisterDomain
 * For checkout mode - prevent domain registration without verification
 */
add_hook('PreRegistrarRegisterDomain', 1, function($vars) {
    if (!EmailVerificationHelper::isModuleActive()) {
        return [];
    }

    $config = EmailVerificationHelper::getModuleConfig();
    
    if ($config['verification_type'] !== 'checkout') {
        return [];
    }

    $clientId = $vars['params']['userid'] ?? 0;
    
    if ($clientId > 0 && !EmailVerificationHelper::isClientVerified($clientId)) {
        return ['abortWithError' => 'Email verification required before this action.'];
    }

    return [];
});

/**
 * Hook: AfterModuleCreate
 * For checkout mode - prevent service activation without verification
 */
add_hook('AfterModuleCreate', 1, function($vars) {
    if (!EmailVerificationHelper::isModuleActive()) {
        return;
    }

    $config = EmailVerificationHelper::getModuleConfig();
    
    if ($config['verification_type'] !== 'checkout') {
        return;
    }

    // Service creation is allowed, but we log it
    $serviceId = $vars['params']['serviceid'] ?? 0;
    if ($serviceId) {
        $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
        if ($service && !EmailVerificationHelper::isClientVerified($service->userid)) {
            EmailVerificationHelper::logAction(
                $service->userid,
                '',
                'service_created_unverified',
                'SYSTEM',
                "Service #{$serviceId} created for unverified client"
            );
        }
    }
});

/**
 * Hook: ClientAreaRegister
 * Intercept registration for immediate verification requirement
 */
add_hook('ClientAreaRegister', 1, function($vars) {
    // This hook runs after successful registration
    // The ClientAdd hook handles the verification email
});

/**
 * Hook: AdminClientServicesTabFields
 * Show verification status in admin client summary
 */
add_hook('AdminClientServicesTabFields', 1, function($vars) {
    $clientId = $vars['userid'];
    
    $token = Capsule::table('mod_email_verification_tokens')
        ->where('client_id', $clientId)
        ->first();

    $status = 'N/A';
    if ($token) {
        if ($token->verified) {
            $status = '<span style="color: green; font-weight: bold;">✓ Verified</span> on ' . date('M j, Y H:i', strtotime($token->verified_at));
        } else {
            $expired = strtotime($token->expires_at) < time();
            $status = '<span style="color: red; font-weight: bold;">✗ Not Verified</span>';
            if ($expired) {
                $status .= ' <span style="color: orange;">(Token Expired)</span>';
            }
        }
    }

    return [
        'Email Verification Status' => $status,
    ];
});
