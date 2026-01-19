<?php
/**
 * Email Verification Pro - Helper Class
 *
 * Contains helper functions for email verification operations.
 *
 * @copyright 2026
 * @license Proprietary
 */

namespace WHMCS\Module\Addon\EmailVerificationPro;

use WHMCS\Database\Capsule;

class EmailVerificationHelper
{
    /**
     * Module configuration cache
     */
    private static $configCache = null;

    /**
     * Check if module is active
     *
     * @return bool
     */
    public static function isModuleActive()
    {
        $addon = Capsule::table('tbladdonmodules')
            ->where('module', 'email_verification_pro')
            ->where('setting', 'version')
            ->first();

        return $addon !== null;
    }

    /**
     * Get module configuration
     *
     * @return array
     */
    public static function getModuleConfig()
    {
        if (self::$configCache !== null) {
            return self::$configCache;
        }

        $settings = Capsule::table('tbladdonmodules')
            ->where('module', 'email_verification_pro')
            ->pluck('value', 'setting')
            ->toArray();

        self::$configCache = [
            'verification_type' => $settings['verification_type'] ?? 'all_pages',
            'auto_terminate_enabled' => ($settings['auto_terminate_enabled'] ?? '') === 'on',
            'auto_terminate_days' => (int) ($settings['auto_terminate_days'] ?? 7),
            'auto_delete_enabled' => ($settings['auto_delete_enabled'] ?? '') === 'on',
            'auto_delete_days' => (int) ($settings['auto_delete_days'] ?? 30),
            'resend_reminder_days' => (int) ($settings['resend_reminder_days'] ?? 3),
            'token_expiry_hours' => (int) ($settings['token_expiry_hours'] ?? 48),
            'lock_email_change' => ($settings['lock_email_change'] ?? '') === 'on',
            'allow_support_tickets' => ($settings['allow_support_tickets'] ?? '') === 'on',
            'allow_profile_edit' => ($settings['allow_profile_edit'] ?? '') === 'on',
            'captcha_type' => $settings['captcha_type'] ?? 'none',
            'recaptcha_site_key' => $settings['recaptcha_site_key'] ?? '',
            'recaptcha_secret_key' => $settings['recaptcha_secret_key'] ?? '',
            'turnstile_site_key' => $settings['turnstile_site_key'] ?? '',
            'turnstile_secret_key' => $settings['turnstile_secret_key'] ?? '',
            'send_welcome_after_verify' => ($settings['send_welcome_after_verify'] ?? '') === 'on',
            'redirect_after_verify' => $settings['redirect_after_verify'] ?? 'clientarea',
        ];

        return self::$configCache;
    }

    /**
     * Check if a client is verified
     *
     * @param int $clientId
     * @return bool
     */
    public static function isClientVerified($clientId)
    {
        $token = Capsule::table('mod_email_verification_tokens')
            ->where('client_id', $clientId)
            ->where('verified', true)
            ->first();

        return $token !== null;
    }

    /**
     * Check if an email is banned
     *
     * @param string $email
     * @return bool
     */
    public static function isEmailBanned($email)
    {
        $email = strtolower(trim($email));

        // Check exact email
        $banned = Capsule::table('mod_email_verification_banned_emails')
            ->where('email', $email)
            ->exists();

        if ($banned) {
            return true;
        }

        // Check email provider/domain
        $domain = substr(strrchr($email, "@"), 1);
        if ($domain) {
            $providerBanned = Capsule::table('mod_email_verification_banned_providers')
                ->where('domain', $domain)
                ->exists();

            if ($providerBanned) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is banned
     *
     * @param string $ip
     * @return bool
     */
    public static function isIpBanned($ip)
    {
        return Capsule::table('mod_email_verification_banned_ips')
            ->where('ip_address', $ip)
            ->exists();
    }

    /**
     * Generate a secure verification token
     *
     * @return string
     */
    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Create a verification token for a client
     *
     * @param int $clientId
     * @param string $email
     * @return string Token
     */
    public static function createVerificationToken($clientId, $email)
    {
        $config = self::getModuleConfig();
        $token = self::generateToken();
        $expiryHours = $config['token_expiry_hours'];

        // Delete any existing tokens for this client
        Capsule::table('mod_email_verification_tokens')
            ->where('client_id', $clientId)
            ->delete();

        // Create new token
        Capsule::table('mod_email_verification_tokens')->insert([
            'client_id' => $clientId,
            'email' => strtolower($email),
            'token' => $token,
            'verified' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours")),
            'resend_count' => 0,
        ]);

        return $token;
    }

    /**
     * Send verification email to a client
     *
     * @param int $clientId
     * @return bool
     */
    public static function sendVerificationEmail($clientId)
    {
        $client = Capsule::table('tblclients')->where('id', $clientId)->first();
        if (!$client) {
            self::logAction($clientId, '', 'email_failed', 'SYSTEM', 'Client not found');
            return false;
        }

        $token = Capsule::table('mod_email_verification_tokens')
            ->where('client_id', $clientId)
            ->where('verified', false)
            ->first();

        if (!$token) {
            // Create a new token
            $tokenString = self::createVerificationToken($clientId, $client->email);
        } else {
            $tokenString = $token->token;
        }

        // Get system URL and company name
        $systemUrl = \WHMCS\Config\Setting::getValue('SystemURL');
        $companyName = \WHMCS\Config\Setting::getValue('CompanyName');
        $verifyUrl = rtrim($systemUrl, '/') . '/index.php?m=email_verification_pro&action=verify&token=' . $tokenString;

        $config = self::getModuleConfig();
        $expiryHours = $config['token_expiry_hours'] ?? 48;

        // Ensure WHMCS email template exists
        self::ensureEmailTemplateExists();

        // Try using WHMCS email template first
        if (function_exists('localAPI')) {
            // First try to send using the custom template we created
            $result = localAPI('SendEmail', [
                'messagename' => 'Email Verification Pro - Verify Email',
                'id' => $clientId,
                'customvars' => base64_encode(serialize([
                    'verification_link' => $verifyUrl,
                    'expiry_hours' => $expiryHours,
                    'company_name' => $companyName,
                ])),
            ]);

            if ($result['result'] === 'success') {
                self::logAction($clientId, $client->email, 'email_sent', 'SYSTEM', 'Verification email sent successfully');
                return true;
            }

            // Fallback: Send using General type email with merge fields
            $emailContent = self::getSpamFreeEmailContent($client, $verifyUrl, $companyName, $expiryHours);

            $result = localAPI('SendEmail', [
                'messagename' => 'General Messages',
                'mergefields' => [
                    'email_subject' => 'Action Required: Verify Your Email Address - ' . $companyName,
                    'email_message' => $emailContent,
                ],
                'id' => $clientId,
            ]);

            if ($result['result'] === 'success') {
                self::logAction($clientId, $client->email, 'email_sent', 'SYSTEM', 'Verification email sent via General Messages');
                return true;
            }

            // Log the error
            self::logAction($clientId, $client->email, 'email_failed', 'SYSTEM', 'Email sending failed: ' . ($result['message'] ?? 'Unknown error'));

            return false;
        }

        return false;
    }

    /**
     * Ensure WHMCS email template exists for verification emails
     */
    public static function ensureEmailTemplateExists()
    {
        // Check if our email template exists in WHMCS
        $templateExists = Capsule::table('tblemailtemplates')
            ->where('name', 'Email Verification Pro - Verify Email')
            ->where('type', 'general')
            ->exists();

        if (!$templateExists) {
            // Get company name for template
            $companyName = \WHMCS\Config\Setting::getValue('CompanyName') ?: 'Our Company';

            // Create the email template
            Capsule::table('tblemailtemplates')->insert([
                'type' => 'general',
                'name' => 'Email Verification Pro - Verify Email',
                'subject' => 'Action Required: Please Verify Your Email Address',
                'message' => self::getWHMCSTemplateContent($companyName),
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
     * Resend verification email
     *
     * @param int $clientId
     * @return bool
     */
    public static function resendVerificationEmail($clientId)
    {
        $config = self::getModuleConfig();

        // Regenerate token
        $client = Capsule::table('tblclients')->where('id', $clientId)->first();
        if (!$client) {
            return false;
        }

        // Update existing token or create new one
        $existingToken = Capsule::table('mod_email_verification_tokens')
            ->where('client_id', $clientId)
            ->first();

        if ($existingToken && !$existingToken->verified) {
            // Update existing token
            $newToken = self::generateToken();
            $expiryHours = $config['token_expiry_hours'];

            Capsule::table('mod_email_verification_tokens')
                ->where('client_id', $clientId)
                ->update([
                    'token' => $newToken,
                    'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours")),
                    'resend_count' => $existingToken->resend_count + 1,
                    'last_resend_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            self::createVerificationToken($clientId, $client->email);
        }

        // Send email
        $result = self::sendVerificationEmail($clientId);

        // Log action
        self::logAction($clientId, $client->email, 'resend', $_SERVER['REMOTE_ADDR'] ?? 'SYSTEM', 'Verification email resent');

        return $result;
    }

    /**
     * Verify a token
     *
     * @param string $token
     * @return array|false Client data if valid, false otherwise
     */
    public static function verifyToken($token)
    {
        $tokenData = Capsule::table('mod_email_verification_tokens')
            ->where('token', $token)
            ->where('verified', false)
            ->first();

        if (!$tokenData) {
            return ['error' => 'Invalid or already used verification token.'];
        }

        // Check if expired
        if (strtotime($tokenData->expires_at) < time()) {
            return ['error' => 'This verification link has expired. Please request a new one.'];
        }

        // Mark as verified
        Capsule::table('mod_email_verification_tokens')
            ->where('id', $tokenData->id)
            ->update([
                'verified' => true,
                'verified_at' => date('Y-m-d H:i:s'),
                'verified_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);

        // Get client
        $client = Capsule::table('tblclients')->where('id', $tokenData->client_id)->first();

        // Log action
        self::logAction(
            $tokenData->client_id,
            $tokenData->email,
            'verified',
            $_SERVER['REMOTE_ADDR'] ?? '',
            'Email verified successfully'
        );

        // Send welcome email if configured
        $config = self::getModuleConfig();
        if ($config['send_welcome_after_verify'] && $client) {
            if (function_exists('localAPI')) {
                localAPI('SendEmail', [
                    'messagename' => 'Client Signup Email',
                    'id' => $tokenData->client_id,
                ]);
            }
        }

        return [
            'success' => true,
            'client_id' => $tokenData->client_id,
            'email' => $tokenData->email,
        ];
    }

    /**
     * Check if verification is required and handle redirect
     *
     * @param array $vars
     * @param string $page
     * @return array
     */
    public static function checkVerificationRequired($vars, $page)
    {
        if (!self::isModuleActive()) {
            return [];
        }

        $config = self::getModuleConfig();
        $clientId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;

        if ($clientId <= 0) {
            return [];
        }

        if (self::isClientVerified($clientId)) {
            return [];
        }

        // Client not verified, show verification notice
        return [
            'verificationRequired' => true,
            'verificationMessage' => 'Please verify your email address to continue.',
            'verificationLink' => 'index.php?m=email_verification_pro&action=verify',
        ];
    }

    /**
     * Log an action
     *
     * @param int $clientId
     * @param string $email
     * @param string $action
     * @param string $ip
     * @param string|null $details
     */
    public static function logAction($clientId, $email, $action, $ip, $details = null)
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
     * Get WHMCS Template Content (for creating template in DB)
     * Uses WHMCS merge fields for better deliverability
     *
     * @param string $companyName
     * @return string
     */
    private static function getWHMCSTemplateContent($companyName)
    {
        return '<p>Dear {$client_name},</p>

<p>Thank you for registering with ' . htmlspecialchars($companyName) . '. To complete your account setup and access all features, please verify your email address.</p>

<p><strong>Click the link below to verify your email:</strong></p>

<p><a href="{$verification_link}" style="display: inline-block; padding: 12px 24px; background-color: #0066cc; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Verify Email Address</a></p>

<p>If the button above does not work, copy and paste this link into your browser:</p>
<p>{$verification_link}</p>

<p><strong>Important:</strong> This verification link will expire in {$expiry_hours} hours.</p>

<p>If you did not create an account with us, you can safely ignore this email.</p>

<p>Best regards,<br>
' . htmlspecialchars($companyName) . ' Team</p>

<p style="font-size: 12px; color: #666666;">This is an automated message from ' . htmlspecialchars($companyName) . '. Please do not reply to this email.</p>';
    }

    /**
     * Get spam-free email content (fallback for General Messages)
     * Optimized to avoid spam filters
     *
     * @param object $client
     * @param string $verifyUrl
     * @param string $companyName
     * @param int $expiryHours
     * @return string
     */
    private static function getSpamFreeEmailContent($client, $verifyUrl, $companyName, $expiryHours)
    {
        // Clean, professional email that avoids spam triggers
        // No excessive caps, no spam words, proper text-to-link ratio
        $firstName = htmlspecialchars($client->firstname);
        $companyNameClean = htmlspecialchars($companyName);

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #333333; background-color: #f5f5f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px 40px; text-align: center; border-bottom: 1px solid #eeeeee;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: normal; color: #333333;">Email Verification</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 30px 40px;">
                            <p style="margin: 0 0 20px 0;">Dear ' . $firstName . ',</p>

                            <p style="margin: 0 0 20px 0;">Thank you for registering with ' . $companyNameClean . '. To complete your account setup and access all features, please verify your email address by clicking the button below.</p>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="' . htmlspecialchars($verifyUrl) . '" style="display: inline-block; padding: 14px 32px; background-color: #0066cc; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">Verify Email Address</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 20px 0; font-size: 13px; color: #666666;">If the button does not work, copy and paste this link into your browser:</p>
                            <p style="margin: 0 0 20px 0; padding: 12px; background-color: #f8f8f8; border-radius: 4px; word-break: break-all; font-size: 12px; color: #0066cc;">' . htmlspecialchars($verifyUrl) . '</p>

                            <p style="margin: 20px 0 0 0; padding: 15px; background-color: #fff8e6; border-left: 4px solid #ffb800; font-size: 13px;"><strong>Note:</strong> This verification link will expire in ' . (int)$expiryHours . ' hours for security purposes.</p>

                            <p style="margin: 20px 0 0 0;">If you did not create an account with ' . $companyNameClean . ', you can safely ignore this email.</p>

                            <p style="margin: 30px 0 0 0;">Best regards,<br>' . $companyNameClean . ' Team</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px; background-color: #fafafa; border-top: 1px solid #eeeeee; text-align: center; font-size: 12px; color: #999999; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0;">This is an automated message from ' . $companyNameClean . '.</p>
                            <p style="margin: 10px 0 0 0;">Please do not reply to this email.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Get verification email content (legacy - kept for compatibility)
     *
     * @param object $client
     * @param string $verifyUrl
     * @return string
     */
    private static function getVerificationEmailContent($client, $verifyUrl)
    {
        $companyName = \WHMCS\Config\Setting::getValue('CompanyName') ?: 'Our Company';
        $config = self::getModuleConfig();
        $expiryHours = $config['token_expiry_hours'] ?? 48;

        return self::getSpamFreeEmailContent($client, $verifyUrl, $companyName, $expiryHours);
    }

    /**
     * Verify reCAPTCHA v3
     *
     * @param string $token
     * @return bool
     */
    public static function verifyRecaptcha($token)
    {
        $config = self::getModuleConfig();
        
        if (empty($config['recaptcha_secret_key'])) {
            return true; // Skip if not configured
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $config['recaptcha_secret_key'],
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        return isset($result['success']) && $result['success'] === true && ($result['score'] ?? 0) >= 0.5;
    }

    /**
     * Verify Cloudflare Turnstile
     *
     * @param string $token
     * @return bool
     */
    public static function verifyTurnstile($token)
    {
        $config = self::getModuleConfig();
        
        if (empty($config['turnstile_secret_key'])) {
            return true; // Skip if not configured
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $config['turnstile_secret_key'],
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        return isset($result['success']) && $result['success'] === true;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public static function getClientIp()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
