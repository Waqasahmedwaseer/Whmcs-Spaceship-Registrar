<?php
/**
 * Email Verification Pro for WHMCS
 *
 * A comprehensive email verification module that ensures customers verify
 * their email addresses before accessing their account or completing checkout.
 *
 * @copyright 2026
 * @license Proprietary
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Module Configuration
 *
 * @return array
 */
function email_verification_pro_config()
{
    return [
        'name' => 'Email Verification Pro',
        'description' => 'Comprehensive email verification module that requires customers to verify their email addresses before accessing their account or completing checkout. Includes 2FA, fraud prevention, and auto-actions.',
        'version' => '1.0.0',
        'author' => '<a href="https://waqasahmedwaseer.com" target="_blank">Waqas Ahmed Waseer</a>',
        'language' => 'english',
        'fields' => [
            'verification_type' => [
                'FriendlyName' => 'Verification Type',
                'Type' => 'dropdown',
                'Options' => [
                    'all_pages' => 'All Pages (Block entire account access)',
                    'checkout' => 'Checkout Only (Block orders until verified)',
                ],
                'Default' => 'all_pages',
                'Description' => 'Choose when to require email verification',
            ],
            'auto_terminate_enabled' => [
                'FriendlyName' => 'Enable Auto-Terminate',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Automatically close accounts that don\'t verify within specified days',
            ],
            'auto_terminate_days' => [
                'FriendlyName' => 'Auto-Terminate After (Days)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '7',
                'Description' => 'Days before unverified accounts are closed',
            ],
            'auto_delete_enabled' => [
                'FriendlyName' => 'Enable Auto-Delete',
                'Type' => 'yesno',
                'Default' => 'no',
                'Description' => 'Permanently delete unverified accounts with no active orders',
            ],
            'auto_delete_days' => [
                'FriendlyName' => 'Auto-Delete After (Days)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '30',
                'Description' => 'Days before unverified accounts are deleted',
            ],
            'resend_reminder_days' => [
                'FriendlyName' => 'Resend Reminder After (Days)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '3',
                'Description' => 'Days before sending a verification reminder',
            ],
            'token_expiry_hours' => [
                'FriendlyName' => 'Verification Link Expiry (Hours)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '48',
                'Description' => 'Hours before verification links expire',
            ],
            'lock_email_change' => [
                'FriendlyName' => 'Lock Email Address',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Prevent unverified users from changing their email',
            ],
            'allow_support_tickets' => [
                'FriendlyName' => 'Allow Support Tickets',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Allow unverified users to access support tickets',
            ],
            'allow_profile_edit' => [
                'FriendlyName' => 'Allow Profile Edit',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Allow unverified users to edit their profile',
            ],
            'captcha_type' => [
                'FriendlyName' => 'Captcha Type',
                'Type' => 'dropdown',
                'Options' => [
                    'none' => 'None',
                    'recaptcha_v3' => 'Google reCAPTCHA v3',
                    'turnstile' => 'Cloudflare Turnstile',
                ],
                'Default' => 'none',
                'Description' => 'Extra security on verification page',
            ],
            'recaptcha_site_key' => [
                'FriendlyName' => 'reCAPTCHA Site Key',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Description' => 'Google reCAPTCHA v3 site key',
            ],
            'recaptcha_secret_key' => [
                'FriendlyName' => 'reCAPTCHA Secret Key',
                'Type' => 'password',
                'Size' => '50',
                'Default' => '',
                'Description' => 'Google reCAPTCHA v3 secret key',
            ],
            'turnstile_site_key' => [
                'FriendlyName' => 'Turnstile Site Key',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Description' => 'Cloudflare Turnstile site key',
            ],
            'turnstile_secret_key' => [
                'FriendlyName' => 'Turnstile Secret Key',
                'Type' => 'password',
                'Size' => '50',
                'Default' => '',
                'Description' => 'Cloudflare Turnstile secret key',
            ],
            'send_welcome_after_verify' => [
                'FriendlyName' => 'Send Welcome Email After Verification',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Send the WHMCS welcome email after successful verification',
            ],
            'redirect_after_verify' => [
                'FriendlyName' => 'Redirect After Verification',
                'Type' => 'dropdown',
                'Options' => [
                    'clientarea' => 'Client Area Home',
                    'cart' => 'Shopping Cart (if items present)',
                    'previous' => 'Previous Page',
                ],
                'Default' => 'clientarea',
                'Description' => 'Where to redirect after successful verification',
            ],
            'twofa_header' => [
                'FriendlyName' => '<hr><strong>Two-Factor Authentication (2FA)</strong>',
                'Type' => 'description',
                'Default' => 'Configure email-based 2FA for all login attempts.',
            ],
            'twofa_enabled' => [
                'FriendlyName' => 'Enable Login 2FA',
                'Type' => 'yesno',
                'Default' => 'no',
                'Description' => 'Require email code verification on every login',
            ],
            'twofa_code_length' => [
                'FriendlyName' => '2FA Code Length',
                'Type' => 'dropdown',
                'Options' => [
                    '4' => '4 Digits',
                    '6' => '6 Digits',
                    '8' => '8 Digits',
                ],
                'Default' => '6',
            ],
            'twofa_code_type' => [
                'FriendlyName' => '2FA Code Type',
                'Type' => 'dropdown',
                'Options' => [
                    'numeric' => 'Numeric Only (e.g. 123456)',
                    'alphanumeric' => 'Alphanumeric (e.g. A1B2C3)',
                    'alpha' => 'Alphabetical (e.g. ABCDEF)',
                ],
                'Default' => 'numeric',
            ],
            'twofa_code_expiry' => [
                'FriendlyName' => '2FA Code Expiry (Minutes)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '10',
            ],
            'twofa_max_attempts' => [
                'FriendlyName' => 'Max Failed Attempts',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '5',
                'Description' => 'Lockout after this many failed 2FA tries',
            ],
            'twofa_remember_device' => [
                'FriendlyName' => 'Remember Trusted Devices',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Option to trust the device for X days',
            ],
            'twofa_remember_days' => [
                'FriendlyName' => 'Device Trust Duration (Days)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '30',
            ],
        ],
    ];
}

/**
 * Module Activation
 *
 * @return array
 */
function email_verification_pro_activate()
{
    try {
        // Create verification tokens table
        if (!Capsule::schema()->hasTable('mod_email_verification_tokens')) {
            Capsule::schema()->create('mod_email_verification_tokens', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->string('email', 255);
                $table->string('token', 64)->unique();
                $table->boolean('verified')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->string('verified_ip', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('expires_at');
                $table->integer('resend_count')->default(0);
                $table->timestamp('last_resend_at')->nullable();
                $table->index(['client_id']);
                $table->index(['token']);
                $table->index(['email']);
            });
        }

        // Create banned emails table
        if (!Capsule::schema()->hasTable('mod_email_verification_banned_emails')) {
            Capsule::schema()->create('mod_email_verification_banned_emails', function ($table) {
                $table->increments('id');
                $table->string('email', 255);
                $table->string('reason', 255)->nullable();
                $table->integer('added_by_admin')->unsigned()->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['email']);
            });
        }

        // Create banned IPs table
        if (!Capsule::schema()->hasTable('mod_email_verification_banned_ips')) {
            Capsule::schema()->create('mod_email_verification_banned_ips', function ($table) {
                $table->increments('id');
                $table->string('ip_address', 45);
                $table->string('reason', 255)->nullable();
                $table->integer('added_by_admin')->unsigned()->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['ip_address']);
            });
        }

        // Create banned email providers table
        if (!Capsule::schema()->hasTable('mod_email_verification_banned_providers')) {
            Capsule::schema()->create('mod_email_verification_banned_providers', function ($table) {
                $table->increments('id');
                $table->string('domain', 255);
                $table->string('reason', 255)->nullable();
                $table->integer('added_by_admin')->unsigned()->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['domain']);
            });
        }

        // Create activity log table
        if (!Capsule::schema()->hasTable('mod_email_verification_log')) {
            Capsule::schema()->create('mod_email_verification_log', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned()->nullable();
                $table->string('email', 255);
                $table->string('ip_address', 45);
                $table->string('action', 50);
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['client_id']);
                $table->index(['action']);
                $table->index(['created_at']);
            });
        }

        // Create 2FA codes table
        if (!Capsule::schema()->hasTable('mod_evp_twofa_codes')) {
            Capsule::schema()->create('mod_evp_twofa_codes', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->string('email', 255);
                $table->string('code', 255);
                $table->string('plain_code', 20)->nullable();
                $table->integer('attempts')->default(0);
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->index(['client_id']);
                $table->index(['expires_at']);
            });
        }

        // Create trusted devices table
        if (!Capsule::schema()->hasTable('mod_evp_remembered_devices')) {
            Capsule::schema()->create('mod_evp_remembered_devices', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->string('device_token', 64);
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['client_id', 'device_token']);
            });
        }

        // Create email templates table
        require_once __DIR__ . '/lib/EmailTemplateManager.php';
        \WHMCS\Module\Addon\EmailVerificationPro\EmailTemplateManager::initialize();

        // Create WHMCS email templates for proper email delivery
        email_verification_pro_create_whmcs_templates();

        // Insert default banned disposable email providers
        $disposableProviders = [
            'tempmail.com', 'guerrillamail.com', 'mailinator.com', '10minutemail.com',
            'throwaway.email', 'fakeinbox.com', 'trashmail.com', 'getnada.com',
            'temp-mail.org', 'disposablemail.com', 'yopmail.com', 'sharklasers.com',
        ];

        foreach ($disposableProviders as $provider) {
            Capsule::table('mod_email_verification_banned_providers')->insertOrIgnore([
                'domain' => $provider,
                'reason' => 'Disposable email provider',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'status' => 'success',
            'description' => 'Email Verification Pro has been activated successfully. WHMCS email templates created.',
        ];

    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Error activating module: ' . $e->getMessage(),
        ];
    }
}

/**
 * Create WHMCS Email Templates for proper email delivery
 * These templates use WHMCS's mail system which respects your SMTP settings
 */
function email_verification_pro_create_whmcs_templates()
{
    $companyName = \WHMCS\Config\Setting::getValue('CompanyName') ?: 'Our Company';

    // Email Verification Template
    $verifyTemplateExists = Capsule::table('tblemailtemplates')
        ->where('name', 'Email Verification Pro - Verify Email')
        ->where('type', 'general')
        ->exists();

    if (!$verifyTemplateExists) {
        Capsule::table('tblemailtemplates')->insert([
            'type' => 'general',
            'name' => 'Email Verification Pro - Verify Email',
            'subject' => 'Action Required: Please Verify Your Email Address',
            'message' => '<p>Dear {$client_name},</p>

<p>Thank you for registering with ' . htmlspecialchars($companyName) . '. To complete your account setup and access all features, please verify your email address.</p>

<p><strong>Click the button below to verify your email:</strong></p>

<p style="text-align: center; margin: 30px 0;">
    <a href="{$verification_link}" style="display: inline-block; padding: 14px 32px; background-color: #0066cc; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">Verify Email Address</a>
</p>

<p>If the button does not work, copy and paste this link into your browser:</p>
<p style="background-color: #f5f5f5; padding: 10px; word-break: break-all; font-family: monospace; font-size: 12px;">{$verification_link}</p>

<p><strong>Important:</strong> This verification link will expire in {$expiry_hours} hours.</p>

<p>If you did not create an account with us, you can safely ignore this email.</p>

<p>Best regards,<br>' . htmlspecialchars($companyName) . ' Team</p>',
            'attachments' => '',
            'fromname' => '',
            'fromemail' => '',
            'disabled' => 0,
            'custom' => 0,
            'language' => '',
            'copyto' => '',
            'plaintext' => 0,
        ]);
    }

    // 2FA Code Template
    $twofaTemplateExists = Capsule::table('tblemailtemplates')
        ->where('name', 'Email Verification Pro - 2FA Code')
        ->where('type', 'general')
        ->exists();

    if (!$twofaTemplateExists) {
        Capsule::table('tblemailtemplates')->insert([
            'type' => 'general',
            'name' => 'Email Verification Pro - 2FA Code',
            'subject' => 'Your Login Verification Code',
            'message' => '<p>Dear {$client_name},</p>

<p>A login attempt was detected on your account. Use the verification code below to complete your login:</p>

<p style="text-align: center; margin: 30px 0;">
    <span style="display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 20px 40px; background-color: #f5f5f5; border-radius: 8px; font-family: monospace;">{$verification_code}</span>
</p>

<p><strong>This code will expire in {$code_expiry} minutes.</strong></p>

<p><strong>Login Details:</strong></p>
<ul>
    <li>IP Address: {$ip_address}</li>
    <li>Browser: {$browser}</li>
    <li>Time: {$timestamp}</li>
</ul>

<p style="color: #cc0000; background-color: #fff5f5; padding: 15px; border-left: 4px solid #cc0000;"><strong>Security Notice:</strong> If you did not attempt to log in, please change your password immediately and contact our support team.</p>

<p>Best regards,<br>' . htmlspecialchars($companyName) . ' Team</p>

<p style="font-size: 12px; color: #666666;">Never share this code with anyone. Our staff will never ask for your verification code.</p>',
            'attachments' => '',
            'fromname' => '',
            'fromemail' => '',
            'disabled' => 0,
            'custom' => 0,
            'language' => '',
            'copyto' => '',
            'plaintext' => 0,
        ]);
    }

    // Verification Reminder Template
    $reminderTemplateExists = Capsule::table('tblemailtemplates')
        ->where('name', 'Email Verification Pro - Reminder')
        ->where('type', 'general')
        ->exists();

    if (!$reminderTemplateExists) {
        Capsule::table('tblemailtemplates')->insert([
            'type' => 'general',
            'name' => 'Email Verification Pro - Reminder',
            'subject' => 'Reminder: Please Verify Your Email Address',
            'message' => '<p>Dear {$client_name},</p>

<p>We noticed you have not yet verified your email address for your account with ' . htmlspecialchars($companyName) . '.</p>

<p>Your account access may be limited until verification is complete. Please click the button below to verify your email:</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="{$verification_link}" style="display: inline-block; padding: 14px 32px; background-color: #0066cc; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">Verify Email Now</a>
</p>

<p style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;"><strong>Notice:</strong> If you do not verify your email within {$days_remaining} days, your account may be suspended.</p>

<p>If you did not create this account, please ignore this email or contact our support team.</p>

<p>Best regards,<br>' . htmlspecialchars($companyName) . ' Team</p>',
            'attachments' => '',
            'fromname' => '',
            'fromemail' => '',
            'disabled' => 0,
            'custom' => 0,
            'language' => '',
            'copyto' => '',
            'plaintext' => 0,
        ]);
    }
}

/**
 * Module Deactivation
 *
 * @return array
 */
function email_verification_pro_deactivate()
{
    try {
        // Optionally drop tables - commented out to preserve data
        // Capsule::schema()->dropIfExists('mod_email_verification_tokens');
        // Capsule::schema()->dropIfExists('mod_email_verification_banned_emails');
        // Capsule::schema()->dropIfExists('mod_email_verification_banned_ips');
        // Capsule::schema()->dropIfExists('mod_email_verification_banned_providers');
        // Capsule::schema()->dropIfExists('mod_email_verification_log');

        return [
            'status' => 'success',
            'description' => 'Email Verification Pro has been deactivated. Database tables preserved.',
        ];

    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Error deactivating module: ' . $e->getMessage(),
        ];
    }
}

/**
 * Module Upgrade
 *
 * @param array $vars
 * @return void
 */
function email_verification_pro_upgrade($vars)
{
    $currentVersion = $vars['version'];

    // Handle version upgrades here
    // if (version_compare($currentVersion, '1.1.0', '<')) {
    //     // Upgrade to 1.1.0
    // }
}

/**
 * Admin Area Output
 *
 * @param array $vars
 * @return string
 */
function email_verification_pro_output($vars)
{
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $LANG = $vars['_lang'];

    // Get current action
    $action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        email_verification_pro_handle_post($vars);
    }

    // Output CSS
    echo '<style>
        .evp-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .evp-card h3 { margin-top: 0; color: #333; border-bottom: 2px solid #4a90d9; padding-bottom: 10px; }
        .evp-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px; }
        .evp-stat { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; padding: 20px; text-align: center; }
        .evp-stat.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .evp-stat.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .evp-stat.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .evp-stat h4 { margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; }
        .evp-stat .number { font-size: 36px; font-weight: bold; }
        .evp-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .evp-tab { padding: 10px 20px; background: #f5f5f5; border: none; cursor: pointer; border-radius: 5px; text-decoration: none; color: #333; }
        .evp-tab.active, .evp-tab:hover { background: #4a90d9; color: white; }
        .evp-table { width: 100%; border-collapse: collapse; }
        .evp-table th, .evp-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .evp-table th { background: #f8f9fa; font-weight: 600; }
        .evp-table tr:hover { background: #f8f9fa; }
        .evp-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .evp-badge.verified { background: #d4edda; color: #155724; }
        .evp-badge.unverified { background: #f8d7da; color: #721c24; }
        .evp-badge.banned { background: #fff3cd; color: #856404; }
        .evp-btn { padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .evp-btn-primary { background: #4a90d9; color: white; }
        .evp-btn-danger { background: #dc3545; color: white; }
        .evp-btn-success { background: #28a745; color: white; }
        .evp-btn:hover { opacity: 0.9; }
        .evp-form-group { margin-bottom: 15px; }
        .evp-form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .evp-form-group input, .evp-form-group select, .evp-form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .evp-alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .evp-alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .evp-alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>';

    // Show success/error messages
    if (isset($_SESSION['evp_message'])) {
        $msgType = $_SESSION['evp_message_type'] ?? 'success';
        echo '<div class="evp-alert evp-alert-' . $msgType . '">' . $_SESSION['evp_message'] . '</div>';
        unset($_SESSION['evp_message'], $_SESSION['evp_message_type']);
    }

    // Navigation tabs
    echo '<div class="evp-tabs">
        <a href="' . $modulelink . '&action=dashboard" class="evp-tab ' . ($action === 'dashboard' ? 'active' : '') . '">Dashboard</a>
        <a href="' . $modulelink . '&action=pending" class="evp-tab ' . ($action === 'pending' ? 'active' : '') . '">Pending Verifications</a>
        <a href="' . $modulelink . '&action=verified" class="evp-tab ' . ($action === 'verified' ? 'active' : '') . '">Verified Clients</a>
        <a href="' . $modulelink . '&action=banned_emails" class="evp-tab ' . ($action === 'banned_emails' ? 'active' : '') . '">Banned Emails</a>
        <a href="' . $modulelink . '&action=banned_ips" class="evp-tab ' . ($action === 'banned_ips' ? 'active' : '') . '">Banned IPs</a>
        <a href="' . $modulelink . '&action=banned_providers" class="evp-tab ' . ($action === 'banned_providers' ? 'active' : '') . '">Banned Providers</a>
        <a href="' . $modulelink . '&action=templates" class="evp-tab ' . (in_array($action, ['templates', 'edit_template', 'preview_template']) ? 'active' : '') . '">Email Templates</a>
        <a href="' . $modulelink . '&action=logs" class="evp-tab ' . ($action === 'logs' ? 'active' : '') . '">Activity Logs</a>
        <a href="' . $modulelink . '&action=about" class="evp-tab ' . ($action === 'about' ? 'active' : '') . '">About</a>
    </div>';

    // Render appropriate page
    switch ($action) {
        case 'pending':
            email_verification_pro_render_pending($modulelink);
            break;
        case 'verified':
            email_verification_pro_render_verified($modulelink);
            break;
        case 'banned_emails':
            email_verification_pro_render_banned_emails($modulelink);
            break;
        case 'banned_ips':
            email_verification_pro_render_banned_ips($modulelink);
            break;
        case 'banned_providers':
            email_verification_pro_render_banned_providers($modulelink);
            break;
        case 'templates':
            require_once __DIR__ . '/lib/EmailTemplateManager.php';
            email_verification_pro_render_templates($modulelink);
            break;
        case 'edit_template':
            require_once __DIR__ . '/lib/EmailTemplateManager.php';
            email_verification_pro_render_edit_template($modulelink);
            break;
        case 'preview_template':
            require_once __DIR__ . '/lib/EmailTemplateManager.php';
            email_verification_pro_render_preview_template($modulelink);
            break;
        case 'logs':
            email_verification_pro_render_logs($modulelink);
            break;
        case 'about':
            email_verification_pro_render_about($modulelink);
            break;
        case 'dashboard':
        default:
            email_verification_pro_render_dashboard($modulelink);
            break;
    }
}

/**
 * Render Email Templates
 */
function email_verification_pro_render_templates($modulelink)
{
    $templates = \WHMCS\Module\Addon\EmailVerificationPro\EmailTemplateManager::getAllTemplates();

    echo '<div class="evp-card">
        <h3>Email Templates</h3>
        <p>Customize the emails sent by the module to your customers. Click "Preview" to see how the email will look with sample data.</p>
        <table class="evp-table">
            <thead>
                <tr>
                    <th>Template Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($templates as $tpl) {
        echo '<tr>
            <td><strong>' . htmlspecialchars($tpl->name) . '</strong></td>
            <td>' . htmlspecialchars($tpl->subject) . '</td>
            <td>' . ($tpl->is_active ? '<span class="evp-badge verified">Active</span>' : '<span class="evp-badge banned">Inactive</span>') . '</td>
            <td>
                <a href="' . $modulelink . '&action=preview_template&key=' . $tpl->template_key . '" class="evp-btn evp-btn-success" target="_blank">Preview</a>
                <a href="' . $modulelink . '&action=edit_template&key=' . $tpl->template_key . '" class="evp-btn evp-btn-primary">Edit</a>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="reset_template">
                    <input type="hidden" name="key" value="' . $tpl->template_key . '">
                    <button type="submit" class="evp-btn" onclick="return confirm(\'Reset to default?\')">Reset</button>
                </form>
            </td>
        </tr>';
    }

    echo '</tbody></table></div>';
}

/**
 * Render Template Preview
 */
function email_verification_pro_render_preview_template($modulelink)
{
    $key = isset($_GET['key']) ? $_GET['key'] : '';
    $preview = \WHMCS\Module\Addon\EmailVerificationPro\EmailTemplateManager::previewTemplate($key);

    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Email Preview: ' . htmlspecialchars($preview['subject']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
            .preview-container { max-width: 700px; margin: 0 auto; }
            .preview-header { background: #333; color: white; padding: 20px; border-radius: 10px 10px 0 0; }
            .preview-header h2 { margin: 0 0 10px 0; }
            .preview-subject { background: #f8f9fa; padding: 15px 20px; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
            .preview-subject strong { color: #666; }
            .preview-body { background: white; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 10px 10px; }
            .back-link { display: inline-block; margin-bottom: 20px; color: #4a90d9; text-decoration: none; }
            .back-link:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="preview-container">
            <a href="' . $modulelink . '&action=templates" class="back-link">&larr; Back to Templates</a>
            <div class="preview-header">
                <h2>Email Preview</h2>
                <small>This is how your email will appear to recipients (with sample data)</small>
            </div>
            <div class="preview-subject">
                <strong>Subject:</strong> ' . htmlspecialchars($preview['subject']) . '
            </div>
            <div class="preview-body">
                ' . $preview['body'] . '
            </div>
        </div>
    </body>
    </html>';
    exit; // End output here for clean preview
}

/**
 * Render Edit Template
 */
function email_verification_pro_render_edit_template($modulelink)
{
    $key = $_GET['key'] ?? '';
    $template = \WHMCS\Module\Addon\EmailVerificationPro\EmailTemplateManager::getTemplate($key);

    if (!$template['name']) {
        echo 'Template not found.';
        return;
    }

    echo '<div class="evp-card">
        <h3>Edit Template: ' . htmlspecialchars($template['name']) . '</h3>
        <form method="post">
            <input type="hidden" name="action" value="update_template">
            <input type="hidden" name="key" value="' . htmlspecialchars($key) . '">
            
            <div class="evp-form-group">
                <label>Subject</label>
                <input type="text" name="subject" value="' . htmlspecialchars($template['subject']) . '" required>
            </div>
            
            <div class="evp-form-group">
                <label>Email Body (HTML Supported)</label>
                <textarea name="body" rows="20" required>' . htmlspecialchars($template['body']) . '</textarea>
            </div>
            
            <div class="evp-form-group">
                <label>Available Variables</label>
                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;">
                    ' . htmlspecialchars($template['variables']) . '
                </div>
            </div>
            
            <div class="evp-form-group">
                <label><input type="checkbox" name="is_active" ' . ($template['is_active'] ? 'checked' : '') . '> Template is Active</label>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="evp-btn evp-btn-success">Save Changes</button>
                <a href="' . $modulelink . '&action=templates" class="evp-btn">Back to Templates</a>
            </div>
        </form>
    </div>';
}

/**
 * Render Dashboard
 */
function email_verification_pro_render_dashboard($modulelink)
{
    // Get statistics - including sync with existing clients
    $totalClients = Capsule::table('tblclients')->where('status', 'Active')->count();
    $clientsWithTokens = Capsule::table('mod_email_verification_tokens')->count();

    // Count clients without tokens (need sync)
    $clientsNeedingSync = $totalClients - $clientsWithTokens;

    $totalPending = Capsule::table('mod_email_verification_tokens')
        ->where('verified', false)
        ->count();

    $totalExpiredPending = Capsule::table('mod_email_verification_tokens')
        ->where('verified', false)
        ->whereRaw('expires_at < NOW()')
        ->count();

    $totalActivePending = $totalPending - $totalExpiredPending;

    $totalVerified = Capsule::table('mod_email_verification_tokens')
        ->where('verified', true)
        ->count();

    $verifiedToday = Capsule::table('mod_email_verification_tokens')
        ->where('verified', true)
        ->whereDate('verified_at', date('Y-m-d'))
        ->count();

    $verifiedThisWeek = Capsule::table('mod_email_verification_tokens')
        ->where('verified', true)
        ->whereDate('verified_at', '>=', date('Y-m-d', strtotime('-7 days')))
        ->count();

    $bannedEmails = Capsule::table('mod_email_verification_banned_emails')->count();
    $bannedIPs = Capsule::table('mod_email_verification_banned_ips')->count();
    $bannedProviders = Capsule::table('mod_email_verification_banned_providers')->count();

    echo '<div class="evp-stats">
        <div class="evp-stat warning">
            <h4>Pending Verifications</h4>
            <div class="number">' . $totalActivePending . '</div>
            <small>' . $totalExpiredPending . ' expired</small>
        </div>
        <div class="evp-stat success">
            <h4>Total Verified</h4>
            <div class="number">' . $totalVerified . '</div>
            <small>' . $verifiedThisWeek . ' this week</small>
        </div>
        <div class="evp-stat info">
            <h4>Verified Today</h4>
            <div class="number">' . $verifiedToday . '</div>
        </div>
        <div class="evp-stat">
            <h4>Blocked (Email/IP/Provider)</h4>
            <div class="number">' . $bannedEmails . ' / ' . $bannedIPs . ' / ' . $bannedProviders . '</div>
        </div>
    </div>';

    // Sync Status Card
    if ($clientsNeedingSync > 0) {
        echo '<div class="evp-card" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%); border-left: 4px solid #ffc107;">
            <h3 style="color: #856404; border-bottom-color: #ffc107;">⚠️ Sync Required</h3>
            <p style="color: #856404;"><strong>' . $clientsNeedingSync . '</strong> existing clients do not have verification records. These are likely clients registered before this module was activated.</p>
            <p>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="sync_existing_verified">
                    <button type="submit" class="evp-btn evp-btn-success" onclick="return confirm(\'This will mark all existing clients as verified. Continue?\')">
                        Mark All Existing as Verified
                    </button>
                </form>
                <form method="post" style="display: inline; margin-left: 10px;">
                    <input type="hidden" name="action" value="sync_existing_pending">
                    <button type="submit" class="evp-btn evp-btn-primary" onclick="return confirm(\'This will create pending verification tokens for all existing clients and send them verification emails. Continue?\')">
                        Send Verification to All
                    </button>
                </form>
            </p>
        </div>';
    }

    // Recent activity
    echo '<div class="evp-card">
        <h3>Recent Activity</h3>';

    $recentLogs = Capsule::table('mod_email_verification_log')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

    if ($recentLogs->isEmpty()) {
        echo '<p>No activity logged yet.</p>';
    } else {
        echo '<table class="evp-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Email</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($recentLogs as $log) {
            $badgeClass = '';
            switch ($log->action) {
                case 'verified':
                case 'admin_verified':
                    $badgeClass = 'verified';
                    break;
                case 'registration_blocked':
                case 'auto_closed':
                case 'auto_deleted':
                    $badgeClass = 'banned';
                    break;
                default:
                    $badgeClass = '';
            }
            echo '<tr>
                <td>' . date('M j, Y H:i', strtotime($log->created_at)) . '</td>
                <td>' . htmlspecialchars($log->email) . '</td>
                <td><span class="evp-badge ' . $badgeClass . '">' . htmlspecialchars($log->action) . '</span></td>
                <td>' . htmlspecialchars($log->ip_address) . '</td>
                <td>' . htmlspecialchars($log->details ?? '-') . '</td>
            </tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';

    // Quick actions
    echo '<div class="evp-card">
        <h3>Quick Actions</h3>
        <p>
            <a href="' . $modulelink . '&action=pending" class="evp-btn evp-btn-primary">View Pending Verifications</a>
            <a href="' . $modulelink . '&action=banned_emails" class="evp-btn evp-btn-danger">Manage Banned Emails</a>
            <a href="' . $modulelink . '&action=templates" class="evp-btn evp-btn-success">Email Templates</a>
            <a href="' . $modulelink . '&action=logs" class="evp-btn">View All Logs</a>
        </p>
    </div>';

    // Statistics Overview
    echo '<div class="evp-card">
        <h3>Statistics Overview</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="color: #666; margin: 0 0 10px 0;">Total Clients</h4>
                <div style="font-size: 28px; font-weight: bold; color: #333;">' . $totalClients . '</div>
            </div>
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="color: #666; margin: 0 0 10px 0;">Verification Rate</h4>
                <div style="font-size: 28px; font-weight: bold; color: #28a745;">' . ($totalClients > 0 ? round(($totalVerified / $totalClients) * 100, 1) : 0) . '%</div>
            </div>
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h4 style="color: #666; margin: 0 0 10px 0;">Tracked Clients</h4>
                <div style="font-size: 28px; font-weight: bold; color: #333;">' . $clientsWithTokens . '</div>
            </div>
        </div>
    </div>';
}

/**
 * Render Pending Verifications
 */
function email_verification_pro_render_pending($modulelink)
{
    $pending = Capsule::table('mod_email_verification_tokens')
        ->leftJoin('tblclients', 'mod_email_verification_tokens.client_id', '=', 'tblclients.id')
        ->where('mod_email_verification_tokens.verified', false)
        ->select(
            'mod_email_verification_tokens.*',
            'tblclients.firstname',
            'tblclients.lastname',
            'tblclients.status as client_status'
        )
        ->orderBy('mod_email_verification_tokens.created_at', 'desc')
        ->get();

    echo '<div class="evp-card">
        <h3>Pending Email Verifications (' . $pending->count() . ')</h3>';

    if ($pending->isEmpty()) {
        echo '<p>No pending verifications.</p>';
    } else {
        echo '<table class="evp-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th>Expires</th>
                    <th>Resends</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($pending as $row) {
            $expired = strtotime($row->expires_at) < time();
            echo '<tr>
                <td><a href="clientssummary.php?userid=' . $row->client_id . '">' . htmlspecialchars($row->firstname . ' ' . $row->lastname) . '</a></td>
                <td>' . htmlspecialchars($row->email) . '</td>
                <td>' . date('M j, Y H:i', strtotime($row->created_at)) . '</td>
                <td>' . date('M j, Y H:i', strtotime($row->expires_at)) . '</td>
                <td>' . $row->resend_count . '</td>
                <td><span class="evp-badge ' . ($expired ? 'banned' : 'unverified') . '">' . ($expired ? 'Expired' : 'Pending') . '</span></td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="verify_manual">
                        <input type="hidden" name="client_id" value="' . $row->client_id . '">
                        <button type="submit" class="evp-btn evp-btn-success" onclick="return confirm(\'Manually verify this email?\')">Verify</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="resend">
                        <input type="hidden" name="client_id" value="' . $row->client_id . '">
                        <button type="submit" class="evp-btn evp-btn-primary">Resend</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="ban_email">
                        <input type="hidden" name="email" value="' . $row->email . '">
                        <button type="submit" class="evp-btn evp-btn-danger" onclick="return confirm(\'Ban this email address?\')">Ban</button>
                    </form>
                </td>
            </tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * Render Verified Clients
 */
function email_verification_pro_render_verified($modulelink)
{
    $verified = Capsule::table('mod_email_verification_tokens')
        ->leftJoin('tblclients', 'mod_email_verification_tokens.client_id', '=', 'tblclients.id')
        ->where('mod_email_verification_tokens.verified', true)
        ->select(
            'mod_email_verification_tokens.*',
            'tblclients.firstname',
            'tblclients.lastname'
        )
        ->orderBy('mod_email_verification_tokens.verified_at', 'desc')
        ->limit(100)
        ->get();

    echo '<div class="evp-card">
        <h3>Recently Verified Clients</h3>';

    if ($verified->isEmpty()) {
        echo '<p>No verified clients yet.</p>';
    } else {
        echo '<table class="evp-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Verified At</th>
                    <th>Verified IP</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($verified as $row) {
            echo '<tr>
                <td><a href="clientssummary.php?userid=' . $row->client_id . '">' . htmlspecialchars($row->firstname . ' ' . $row->lastname) . '</a></td>
                <td>' . htmlspecialchars($row->email) . '</td>
                <td>' . date('M j, Y H:i', strtotime($row->verified_at)) . '</td>
                <td>' . htmlspecialchars($row->verified_ip ?? '-') . '</td>
            </tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * Render Banned Emails
 */
function email_verification_pro_render_banned_emails($modulelink)
{
    $banned = Capsule::table('mod_email_verification_banned_emails')
        ->orderBy('created_at', 'desc')
        ->get();

    echo '<div class="evp-card">
        <h3>Banned Email Addresses</h3>
        
        <form method="post" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="add_banned_email">
            <div style="display: flex; gap: 10px;">
                <input type="email" name="email" placeholder="Email address to ban" required style="flex: 1;">
                <input type="text" name="reason" placeholder="Reason (optional)" style="flex: 1;">
                <button type="submit" class="evp-btn evp-btn-danger">Add Ban</button>
            </div>
        </form>';

    if ($banned->isEmpty()) {
        echo '<p>No banned emails.</p>';
    } else {
        echo '<table class="evp-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Reason</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($banned as $row) {
            echo '<tr>
                <td>' . htmlspecialchars($row->email) . '</td>
                <td>' . htmlspecialchars($row->reason ?? '-') . '</td>
                <td>' . date('M j, Y', strtotime($row->created_at)) . '</td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="remove_banned_email">
                        <input type="hidden" name="id" value="' . $row->id . '">
                        <button type="submit" class="evp-btn evp-btn-danger" onclick="return confirm(\'Remove this ban?\')">Remove</button>
                    </form>
                </td>
            </tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * Render Banned IPs
 */
function email_verification_pro_render_banned_ips($modulelink)
{
    $banned = Capsule::table('mod_email_verification_banned_ips')
        ->orderBy('created_at', 'desc')
        ->get();

    echo '<div class="evp-card">
        <h3>Banned IP Addresses</h3>
        
        <form method="post" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="add_banned_ip">
            <div style="display: flex; gap: 10px;">
                <input type="text" name="ip_address" placeholder="IP address to ban" required style="flex: 1;">
                <input type="text" name="reason" placeholder="Reason (optional)" style="flex: 1;">
                <button type="submit" class="evp-btn evp-btn-danger">Add Ban</button>
            </div>
        </form>';

    if ($banned->isEmpty()) {
        echo '<p>No banned IPs.</p>';
    } else {
        echo '<table class="evp-table">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Reason</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($banned as $row) {
            echo '<tr>
                <td>' . htmlspecialchars($row->ip_address) . '</td>
                <td>' . htmlspecialchars($row->reason ?? '-') . '</td>
                <td>' . date('M j, Y', strtotime($row->created_at)) . '</td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="remove_banned_ip">
                        <input type="hidden" name="id" value="' . $row->id . '">
                        <button type="submit" class="evp-btn evp-btn-danger" onclick="return confirm(\'Remove this ban?\')">Remove</button>
                    </form>
                </td>
            </tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * Render Banned Providers
 */
function email_verification_pro_render_banned_providers($modulelink)
{
    $banned = Capsule::table('mod_email_verification_banned_providers')
        ->orderBy('domain', 'asc')
        ->get();

    echo '<div class="evp-card">
        <h3>Banned Email Providers (Disposable Email Domains)</h3>
        
        <form method="post" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="add_banned_provider">
            <div style="display: flex; gap: 10px;">
                <input type="text" name="domain" placeholder="Domain (e.g., tempmail.com)" required style="flex: 1;">
                <input type="text" name="reason" placeholder="Reason (optional)" style="flex: 1;">
                <button type="submit" class="evp-btn evp-btn-danger">Add Provider</button>
            </div>
        </form>';

    if ($banned->isEmpty()) {
        echo '<p>No banned providers.</p>';
    } else {
        echo '<table class="evp-table">
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Reason</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($banned as $row) {
            echo '<tr>
                <td>' . htmlspecialchars($row->domain) . '</td>
                <td>' . htmlspecialchars($row->reason ?? '-') . '</td>
                <td>' . date('M j, Y', strtotime($row->created_at)) . '</td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="remove_banned_provider">
                        <input type="hidden" name="id" value="' . $row->id . '">
                        <button type="submit" class="evp-btn evp-btn-danger" onclick="return confirm(\'Remove this provider?\')">Remove</button>
                    </form>
                </td>
            </tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * Render Activity Logs
 */
function email_verification_pro_render_logs($modulelink)
{
    $logs = Capsule::table('mod_email_verification_log')
        ->orderBy('created_at', 'desc')
        ->limit(200)
        ->get();

    echo '<div class="evp-card">
        <h3>Activity Logs</h3>
        
        <form method="post" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="clear_logs">
            <button type="submit" class="evp-btn evp-btn-danger" onclick="return confirm(\'Clear all logs? This cannot be undone.\')">Clear All Logs</button>
        </form>';

    if ($logs->isEmpty()) {
        echo '<p>No activity logs.</p>';
    } else {
        echo '<table class="evp-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client ID</th>
                    <th>Email</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($logs as $log) {
            echo '<tr>
                <td>' . date('M j, Y H:i:s', strtotime($log->created_at)) . '</td>
                <td>' . ($log->client_id ? '<a href="clientssummary.php?userid=' . $log->client_id . '">#' . $log->client_id . '</a>' : '-') . '</td>
                <td>' . htmlspecialchars($log->email) . '</td>
                <td><span class="evp-badge">' . htmlspecialchars($log->action) . '</span></td>
                <td>' . htmlspecialchars($log->ip_address) . '</td>
                <td>' . htmlspecialchars($log->details ?? '-') . '</td>
            </tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * Render About Page with Developer Info
 */
function email_verification_pro_render_about($modulelink)
{
    echo '<div class="evp-card">
        <div style="text-align: center; padding: 30px 0;">
            <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 48px; color: white;">✉️</span>
            </div>
            <h1 style="margin: 0 0 10px 0; color: #333;">Email Verification Pro</h1>
            <p style="color: #666; font-size: 18px; margin: 0;">Version 1.0.0</p>
        </div>
    </div>

    <div class="evp-card">
        <h3>👨‍💻 Developer Information</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 30px; border-radius: 10px; text-align: center;">
                    <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 48px; color: white;">W</div>
                    <h2 style="margin: 0 0 5px 0; color: #333;">Waqas Ahmed Waseer</h2>
                    <p style="color: #666; margin: 0 0 20px 0;">Full Stack Developer & WHMCS Expert</p>

                    <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                        <a href="https://waqasahmedwaseer.com" target="_blank" class="evp-btn evp-btn-primary" style="padding: 10px 20px;">
                            🌐 Website
                        </a>
                        <a href="https://github.com/waqasahmedwaseer" target="_blank" class="evp-btn" style="background: #333; color: white; padding: 10px 20px;">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="vertical-align: middle; margin-right: 5px;"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
                            GitHub
                        </a>
                    </div>
                </div>
            </div>

            <div>
                <h4 style="margin-top: 0; color: #333;">📱 Connect With Me</h4>
                <table class="evp-table" style="margin-bottom: 20px;">
                    <tr>
                        <td style="width: 40px;">🌐</td>
                        <td><strong>Website</strong></td>
                        <td><a href="https://waqasahmedwaseer.com" target="_blank">waqasahmedwaseer.com</a></td>
                    </tr>
                    <tr>
                        <td>💻</td>
                        <td><strong>GitHub</strong></td>
                        <td><a href="https://github.com/waqasahmedwaseer" target="_blank">@waqasahmedwaseer</a></td>
                    </tr>
                    <tr>
                        <td>💼</td>
                        <td><strong>LinkedIn</strong></td>
                        <td><a href="https://linkedin.com/in/waqasahmedwaseer" target="_blank">@waqasahmedwaseer</a></td>
                    </tr>
                    <tr>
                        <td>🐦</td>
                        <td><strong>Twitter / X</strong></td>
                        <td><a href="https://twitter.com/waqasahmedwseer" target="_blank">@waqasahmedwseer</a></td>
                    </tr>
                    <tr>
                        <td>📸</td>
                        <td><strong>Instagram</strong></td>
                        <td><a href="https://instagram.com/waqasahmedwaseer" target="_blank">@waqasahmedwaseer</a></td>
                    </tr>
                    <tr>
                        <td>📘</td>
                        <td><strong>Facebook</strong></td>
                        <td><a href="https://facebook.com/waqasahmedwaseer" target="_blank">@waqasahmedwaseer</a></td>
                    </tr>
                </table>

                <h4 style="color: #333;">💡 Need Custom Development?</h4>
                <p style="color: #666;">Looking for custom WHMCS modules, hosting automation, or web development solutions? Get in touch!</p>
                <a href="mailto:contact@waqasahmedwaseer.com" class="evp-btn evp-btn-success">📧 Contact Me</a>
            </div>
        </div>
    </div>

    <div class="evp-card">
        <h3>📋 Module Features</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h4 style="margin-top: 0; color: #667eea;">✅ Email Verification</h4>
                <ul style="color: #666; padding-left: 20px; margin-bottom: 0;">
                    <li>Require verification on registration</li>
                    <li>Customizable expiry times</li>
                    <li>Auto-resend reminders</li>
                    <li>Manual verification option</li>
                </ul>
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h4 style="margin-top: 0; color: #11998e;">🔐 Two-Factor Auth</h4>
                <ul style="color: #666; padding-left: 20px; margin-bottom: 0;">
                    <li>Email-based 2FA on login</li>
                    <li>Configurable code length/type</li>
                    <li>Remember trusted devices</li>
                    <li>Brute-force protection</li>
                </ul>
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h4 style="margin-top: 0; color: #dc3545;">🛡️ Fraud Prevention</h4>
                <ul style="color: #666; padding-left: 20px; margin-bottom: 0;">
                    <li>Block disposable emails</li>
                    <li>Ban specific emails/IPs</li>
                    <li>Ban email providers</li>
                    <li>Activity logging</li>
                </ul>
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h4 style="margin-top: 0; color: #ffc107;">⚙️ Auto-Actions</h4>
                <ul style="color: #666; padding-left: 20px; margin-bottom: 0;">
                    <li>Auto-terminate accounts</li>
                    <li>Auto-delete after X days</li>
                    <li>Auto-reminder emails</li>
                    <li>Cron job integration</li>
                </ul>
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h4 style="margin-top: 0; color: #17a2b8;">📧 Email Templates</h4>
                <ul style="color: #666; padding-left: 20px; margin-bottom: 0;">
                    <li>Customizable templates</li>
                    <li>Live preview</li>
                    <li>Variable substitution</li>
                    <li>Reset to default</li>
                </ul>
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h4 style="margin-top: 0; color: #6c757d;">🔒 Security</h4>
                <ul style="color: #666; padding-left: 20px; margin-bottom: 0;">
                    <li>reCAPTCHA v3 support</li>
                    <li>Cloudflare Turnstile</li>
                    <li>Lock email changes</li>
                    <li>IP-based tracking</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="evp-card" style="text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h3 style="color: white; border-bottom-color: rgba(255,255,255,0.3);">❤️ Thank You for Using Email Verification Pro!</h3>
        <p>If you find this module helpful, please consider leaving a review or sharing it with others.</p>
        <p style="margin-bottom: 0;">
            <a href="https://github.com/waqasahmedwaseer" target="_blank" style="color: white; text-decoration: underline;">⭐ Star on GitHub</a>
            &nbsp;|&nbsp;
            <a href="https://waqasahmedwaseer.com" target="_blank" style="color: white; text-decoration: underline;">🌐 Visit Website</a>
        </p>
    </div>';
}

/**
 * Handle POST actions
 */
function email_verification_pro_handle_post($vars)
{
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'verify_manual':
            $clientId = (int) $_POST['client_id'];
            email_verification_pro_verify_client($clientId, true);
            $_SESSION['evp_message'] = 'Client verified successfully.';
            break;

        case 'resend':
            $clientId = (int) $_POST['client_id'];
            email_verification_pro_resend_verification($clientId);
            $_SESSION['evp_message'] = 'Verification email resent.';
            break;

        case 'ban_email':
            $email = trim($_POST['email']);
            Capsule::table('mod_email_verification_banned_emails')->insertOrIgnore([
                'email' => strtolower($email),
                'reason' => 'Banned from pending list',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['evp_message'] = 'Email banned successfully.';
            break;

        case 'add_banned_email':
            $email = strtolower(trim($_POST['email']));
            $reason = trim($_POST['reason'] ?? '');
            Capsule::table('mod_email_verification_banned_emails')->insertOrIgnore([
                'email' => $email,
                'reason' => $reason ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['evp_message'] = 'Email added to ban list.';
            break;

        case 'remove_banned_email':
            $id = (int) $_POST['id'];
            Capsule::table('mod_email_verification_banned_emails')->where('id', $id)->delete();
            $_SESSION['evp_message'] = 'Email removed from ban list.';
            break;

        case 'add_banned_ip':
            $ip = trim($_POST['ip_address']);
            $reason = trim($_POST['reason'] ?? '');
            Capsule::table('mod_email_verification_banned_ips')->insertOrIgnore([
                'ip_address' => $ip,
                'reason' => $reason ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['evp_message'] = 'IP added to ban list.';
            break;

        case 'remove_banned_ip':
            $id = (int) $_POST['id'];
            Capsule::table('mod_email_verification_banned_ips')->where('id', $id)->delete();
            $_SESSION['evp_message'] = 'IP removed from ban list.';
            break;

        case 'add_banned_provider':
            $domain = strtolower(trim($_POST['domain']));
            $reason = trim($_POST['reason'] ?? '');
            Capsule::table('mod_email_verification_banned_providers')->insertOrIgnore([
                'domain' => $domain,
                'reason' => $reason ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['evp_message'] = 'Provider added to ban list.';
            break;

        case 'remove_banned_provider':
            $id = (int) $_POST['id'];
            Capsule::table('mod_email_verification_banned_providers')->where('id', $id)->delete();
            $_SESSION['evp_message'] = 'Provider removed from ban list.';
            break;

        case 'clear_logs':
            Capsule::table('mod_email_verification_log')->truncate();
            $_SESSION['evp_message'] = 'All logs cleared.';
            break;

        case 'update_template':
            require_once __DIR__ . '/lib/EmailTemplateManager.php';
            $key = $_POST['key'];
            $data = [
                'subject' => $_POST['subject'],
                'body' => $_POST['body'],
                'is_active' => isset($_POST['is_active']),
            ];
            \WHMCS\Module\Addon\EmailVerificationPro\EmailTemplateManager::updateTemplate($key, $data);
            $_SESSION['evp_message'] = 'Template updated successfully.';
            break;

        case 'reset_template':
            require_once __DIR__ . '/lib/EmailTemplateManager.php';
            \WHMCS\Module\Addon\EmailVerificationPro\EmailTemplateManager::resetTemplate($_POST['key']);
            $_SESSION['evp_message'] = 'Template reset to default.';
            break;

        case 'sync_existing_verified':
            // Mark all existing clients as verified
            $synced = email_verification_pro_sync_existing_clients(true);
            $_SESSION['evp_message'] = $synced . ' existing clients have been marked as verified.';
            break;

        case 'sync_existing_pending':
            // Create pending tokens for all existing clients
            $synced = email_verification_pro_sync_existing_clients(false);
            $_SESSION['evp_message'] = $synced . ' verification emails have been sent to existing clients.';
            break;
    }

    $_SESSION['evp_message_type'] = 'success';
}

/**
 * Sync existing clients to verification system
 *
 * @param bool $markAsVerified If true, mark as verified. If false, create pending tokens and send emails.
 * @return int Number of clients synced
 */
function email_verification_pro_sync_existing_clients($markAsVerified = true)
{
    require_once __DIR__ . '/lib/EmailVerificationHelper.php';

    // Get all active clients without tokens
    $clientsWithoutTokens = Capsule::table('tblclients')
        ->whereNotIn('id', function ($query) {
            $query->select('client_id')->from('mod_email_verification_tokens');
        })
        ->where('status', 'Active')
        ->get();

    $count = 0;
    foreach ($clientsWithoutTokens as $client) {
        if ($markAsVerified) {
            // Create verified token
            Capsule::table('mod_email_verification_tokens')->insert([
                'client_id' => $client->id,
                'email' => strtolower($client->email),
                'token' => bin2hex(random_bytes(32)),
                'verified' => true,
                'verified_at' => date('Y-m-d H:i:s'),
                'verified_ip' => 'SYNC',
                'created_at' => $client->datecreated ?? date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s'),
                'resend_count' => 0,
            ]);

            email_verification_pro_log(
                $client->id,
                $client->email,
                'sync_verified',
                'ADMIN',
                'Existing client marked as verified during sync'
            );
        } else {
            // Create pending token and send email
            \WHMCS\Module\Addon\EmailVerificationPro\EmailVerificationHelper::createVerificationToken($client->id, $client->email);
            \WHMCS\Module\Addon\EmailVerificationPro\EmailVerificationHelper::sendVerificationEmail($client->id);

            email_verification_pro_log(
                $client->id,
                $client->email,
                'sync_pending',
                'ADMIN',
                'Verification email sent during sync'
            );
        }
        $count++;
    }

    return $count;
}

/**
 * Verify a client
 *
 * @param int $clientId
 * @param bool $manual
 */
function email_verification_pro_verify_client($clientId, $manual = false)
{
    Capsule::table('mod_email_verification_tokens')
        ->where('client_id', $clientId)
        ->update([
            'verified' => true,
            'verified_at' => date('Y-m-d H:i:s'),
            'verified_ip' => $manual ? 'ADMIN' : ($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);

    // Log action
    $client = Capsule::table('tblclients')->where('id', $clientId)->first();
    email_verification_pro_log(
        $clientId,
        $client->email ?? '',
        $manual ? 'admin_verified' : 'verified',
        $manual ? 'ADMIN' : ($_SERVER['REMOTE_ADDR'] ?? ''),
        $manual ? 'Manually verified by admin' : 'Verified via link'
    );
}

/**
 * Resend verification email
 *
 * @param int $clientId
 */
function email_verification_pro_resend_verification($clientId)
{
    require_once __DIR__ . '/lib/EmailVerificationHelper.php';
    \WHMCS\Module\Addon\EmailVerificationPro\EmailVerificationHelper::resendVerificationEmail($clientId);
}

/**
 * Log activity
 */
function email_verification_pro_log($clientId, $email, $action, $ip, $details = null)
{
    Capsule::table('mod_email_verification_log')->insert([
        'client_id' => $clientId,
        'email' => $email,
        'action' => $action,
        'ip_address' => $ip,
        'details' => $details,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

/**
 * Client Area Output
 *
 * @param array $vars
 * @return array
 */
function email_verification_pro_clientarea($vars)
{
    // Load helper classes
    require_once __DIR__ . '/lib/EmailVerificationHelper.php';
    require_once __DIR__ . '/lib/TwoFactorAuthHelper.php';

    $action = $_REQUEST['action'] ?? 'verify';
    $clientId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
    $config = \WHMCS\Module\Addon\EmailVerificationPro\EmailVerificationHelper::getModuleConfig();

    $templateVars = [];
    $templateFile = '';
    $pagetitle = '';

    // --- Action: Process a verification link from an email ---
    if ($action === 'verify' && isset($_GET['token']) && !empty($_GET['token'])) {
        $token = $_GET['token'];
        $result = \WHMCS\Module\Addon\EmailVerificationPro\EmailVerificationHelper::verifyToken($token);

        if (isset($result['success']) && $result['success']) {
            $templateFile = 'verify_success';
            $templateVars['success'] = true;
            $templateVars['message'] = 'Your email has been verified successfully!';
            $templateVars['redirectUrl'] = 'clientarea.php';

            // Log the user in if they weren't already
            if (!$clientId && isset($result['client_id'])) {
                $_SESSION['uid'] = $result['client_id'];
            }
        } else {
            $templateVars['error'] = $result['error'] ?? 'An unknown error occurred.';
            $templateVars['showResend'] = true;
            $templateFile = 'verify_error';
        }
        $pagetitle = 'Email Verification';

    // --- Action: Show the verification required page ---
    } elseif ($action === 'verify') {
        if ($clientId > 0) {
            if (\WHMCS\Module\Addon\EmailVerificationPro\EmailVerificationHelper::isClientVerified($clientId)) {
                $templateFile = 'already_verified';
                $templateVars['redirectUrl'] = 'clientarea.php';
            } else {
                $templateFile = 'verify_required';

                // Get client info for template
                $client = \WHMCS\Database\Capsule::table('tblclients')->where('id', $clientId)->first();
                $token = \WHMCS\Database\Capsule::table('mod_email_verification_tokens')
                    ->where('client_id', $clientId)
                    ->first();

                $templateVars['clientId'] = $clientId;
                $templateVars['email'] = $client ? $client->email : '';
                $templateVars['maskedEmail'] = $client ? maskEmailAddress($client->email) : '';
                $templateVars['tokenExists'] = $token !== null;
                $templateVars['tokenExpired'] = $token ? strtotime($token->expires_at) < time() : false;
                $templateVars['captchaType'] = $config['captcha_type'] ?? 'none';
                $templateVars['recaptchaSiteKey'] = $config['recaptcha_site_key'] ?? '';
                $templateVars['turnstileSiteKey'] = $config['turnstile_site_key'] ?? '';
                $templateVars['allowSupportTickets'] = $config['allow_support_tickets'] ?? true;
                $templateVars['resendMessage'] = $_SESSION['evp_resend_message'] ?? '';

                // Clear session message
                unset($_SESSION['evp_resend_message']);
            }
        } else {
            // User is not logged in and is trying to access the verify page without a token
            $templateFile = 'verify_login_required';
        }
        $pagetitle = 'Email Verification';

    // --- Action: Resend the verification email ---
    } elseif ($action === 'resend' && $clientId > 0) {
        \WHMCS\Module\Addon\EmailVerificationPro\EmailVerificationHelper::resendVerificationEmail($clientId);
        $_SESSION['evp_resend_message'] = 'Verification email has been resent. Please check your inbox.';
        header('Location: index.php?m=email_verification_pro&action=verify');
        exit;

    // --- Action: Display and process the 2FA page ---
    } elseif ($action === 'twofa') {
        // For 2FA, we need to check the 2FA session, not regular session
        $twofaClientId = isset($_SESSION['evp_2fa_client_id']) ? (int) $_SESSION['evp_2fa_client_id'] : 0;

        if (!empty($_SESSION['evp_2fa_pending']) && $twofaClientId > 0) {
            // Handle 2FA resend
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
                $client = \WHMCS\Database\Capsule::table('tblclients')->where('id', $twofaClientId)->first();
                if ($client) {
                    $code = \WHMCS\Module\Addon\EmailVerificationPro\TwoFactorAuthHelper::createCode($twofaClientId, $client->email);
                    \WHMCS\Module\Addon\EmailVerificationPro\TwoFactorAuthHelper::sendCode($twofaClientId, $code);
                    $templateVars['error'] = 'A new verification code has been sent to your email.';
                }
            }
            // Handle 2FA code verification
            elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code']) && !empty($_POST['code'])) {
                $result = \WHMCS\Module\Addon\EmailVerificationPro\TwoFactorAuthHelper::verifyCode($twofaClientId, $_POST['code']);
                if ($result['success']) {
                    // Clear 2FA session
                    unset($_SESSION['evp_2fa_pending'], $_SESSION['evp_2fa_client_id'], $_SESSION['evp_2fa_error']);

                    // Ensure user is logged in
                    if (!isset($_SESSION['uid'])) {
                        $_SESSION['uid'] = $twofaClientId;
                    }

                    // Redirect to the original destination after successful 2FA
                    header("Location: clientarea.php");
                    exit;
                } else {
                    $templateVars['error'] = $result['error'];
                }
            }

            // Get client info for display
            $client = \WHMCS\Database\Capsule::table('tblclients')->where('id', $twofaClientId)->first();
            $templateVars['maskedEmail'] = $client ? maskEmailAddress($client->email) : '';
            $templateVars['captchaType'] = $config['captcha_type'] ?? 'none';
            $templateVars['recaptchaSiteKey'] = $config['recaptcha_site_key'] ?? '';
            $templateVars['turnstileSiteKey'] = $config['turnstile_site_key'] ?? '';
            $templateVars['error'] = $templateVars['error'] ?? ($_SESSION['evp_2fa_error'] ?? '');
            $templateVars['codeLength'] = $config['twofa_code_length'] ?? 6;

            // Clear error after displaying
            unset($_SESSION['evp_2fa_error']);

            $pagetitle = 'Two-Factor Authentication';
            $templateFile = 'twofa_verify';
        } else {
            // If not in a pending 2FA state, just go to the client area.
            header("Location: clientarea.php");
            exit;
        }
    }

    // Default return if no action is matched or an error occurs
    if (empty($templateFile)) {
        return [
            'pagetitle' => 'Error',
            'templatefile' => 'verify_error',
            'requirelogin' => false,
            'vars' => ['error' => 'An unexpected error occurred. Please try again.'],
        ];
    }

    return [
        'pagetitle' => $pagetitle,
        'templatefile' => $templateFile,
        'requirelogin' => false, // We handle login ourselves
        'vars' => $templateVars,
    ];
}

/**
 * Mask email address for display
 *
 * @param string $email
 * @return string
 */
function maskEmailAddress($email)
{
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return $email;
    }

    $name = $parts[0];
    $domain = $parts[1];

    if (strlen($name) <= 2) {
        $maskedName = $name[0] . '*';
    } elseif (strlen($name) <= 4) {
        $maskedName = substr($name, 0, 1) . str_repeat('*', strlen($name) - 2) . substr($name, -1);
    } else {
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 4, 2)) . substr($name, -2);
    }

    return $maskedName . '@' . $domain;
}
