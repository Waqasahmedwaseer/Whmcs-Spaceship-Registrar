<?php
/**
 * Email Verification Pro - Two-Factor Authentication Helper
 *
 * Handles email-based 2FA for login verification.
 *
 * @copyright 2026
 * @license Proprietary
 */

namespace WHMCS\Module\Addon\EmailVerificationPro;

use WHMCS\Database\Capsule;

class TwoFactorAuthHelper
{
    /**
     * Configuration cache
     */
    private static $configCache = null;

    /**
     * Clear the configuration cache
     * Call this when you need fresh settings from database
     */
    public static function clearConfigCache()
    {
        self::$configCache = null;
    }

    /**
     * Get 2FA configuration
     *
     * @return array
     */
    public static function getConfig()
    {
        if (self::$configCache !== null) {
            return self::$configCache;
        }

        $settings = Capsule::table('tbladdonmodules')
            ->where('module', 'email_verification_pro')
            ->pluck('value', 'setting')
            ->toArray();

        self::$configCache = [
            'twofa_enabled' => ($settings['twofa_enabled'] ?? '') === 'on',
            'twofa_code_length' => (int) ($settings['twofa_code_length'] ?? 6),
            'twofa_code_expiry' => (int) ($settings['twofa_code_expiry'] ?? 10),
            'twofa_max_attempts' => (int) ($settings['twofa_max_attempts'] ?? 5),
            'twofa_lockout_duration' => (int) ($settings['twofa_lockout_duration'] ?? 30),
            'twofa_remember_device' => ($settings['twofa_remember_device'] ?? '') === 'on',
            'twofa_remember_days' => (int) ($settings['twofa_remember_days'] ?? 30),
            'twofa_require_for_admin' => ($settings['twofa_require_for_admin'] ?? '') === 'on',
            'twofa_exempt_ips' => $settings['twofa_exempt_ips'] ?? '',
            'twofa_code_type' => $settings['twofa_code_type'] ?? 'numeric',
        ];

        return self::$configCache;
    }

    /**
     * Check if 2FA is required for a client
     *
     * @param int $clientId
     * @return bool
     */
    public static function isRequired($clientId)
    {
        $config = self::getConfig();

        if (!$config['twofa_enabled']) {
            return false;
        }

        // Check if IP is exempt
        $ip = self::getClientIp();
        $exemptIps = array_filter(array_map('trim', explode("\n", $config['twofa_exempt_ips'])));
        if (in_array($ip, $exemptIps)) {
            return false;
        }

        // Check if device is remembered
        if ($config['twofa_remember_device'] && self::isDeviceRemembered($clientId)) {
            return false;
        }

        return true;
    }

    /**
     * Generate a verification code
     *
     * @param int $length
     * @param string $type 'numeric', 'alphanumeric', 'alpha'
     * @return string
     */
    public static function generateCode($length = null, $type = null)
    {
        $config = self::getConfig();
        $length = $length ?? $config['twofa_code_length'];
        $type = $type ?? $config['twofa_code_type'];

        switch ($type) {
            case 'alphanumeric':
                $chars = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                break;
            case 'alpha':
                $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                break;
            case 'numeric':
            default:
                $chars = '0123456789';
                break;
        }

        $code = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Create a 2FA code for a client
     *
     * @param int $clientId
     * @param string $email
     * @return string The generated code
     */
    public static function createCode($clientId, $email)
    {
        $config = self::getConfig();
        $code = self::generateCode();
        $expiryMinutes = $config['twofa_code_expiry'];

        // Delete any existing codes for this client
        Capsule::table('mod_evp_twofa_codes')
            ->where('client_id', $clientId)
            ->delete();

        // Create new code
        Capsule::table('mod_evp_twofa_codes')->insert([
            'client_id' => $clientId,
            'email' => strtolower($email),
            'code' => password_hash($code, PASSWORD_DEFAULT),
            'plain_code' => $code, // For display in admin if needed
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes")),
            'ip_address' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        // Log action
        EmailVerificationHelper::logAction($clientId, $email, '2fa_code_created', self::getClientIp());

        return $code;
    }

    /**
     * Send 2FA code via email
     *
     * @param int $clientId
     * @param string $code
     * @return bool
     */
    public static function sendCode($clientId, $code)
    {
        $client = Capsule::table('tblclients')->where('id', $clientId)->first();
        if (!$client) {
            EmailVerificationHelper::logAction($clientId, '', 'email_failed', 'SYSTEM', '2FA: Client not found');
            return false;
        }

        $config = self::getConfig();
        $expiryMinutes = $config['twofa_code_expiry'];
        $companyName = \WHMCS\Config\Setting::getValue('CompanyName') ?: 'Our Company';

        // Ensure 2FA email template exists
        self::ensure2FATemplateExists();

        if (function_exists('localAPI')) {
            // Try WHMCS template first
            $result = localAPI('SendEmail', [
                'messagename' => 'Email Verification Pro - 2FA Code',
                'id' => $clientId,
                'customvars' => base64_encode(serialize([
                    'verification_code' => $code,
                    'code_expiry' => $expiryMinutes,
                    'ip_address' => self::getClientIp(),
                    'browser' => self::getBrowserName(),
                    'timestamp' => date('M j, Y H:i:s'),
                ])),
            ]);

            if ($result['result'] === 'success') {
                EmailVerificationHelper::logAction($clientId, $client->email, '2fa_email_sent', self::getClientIp(), '2FA code sent');
                return true;
            }

            // Fallback: Use General Messages with proper formatting
            $emailContent = self::get2FAEmailContent($client, $code, $expiryMinutes, $companyName);

            $result = localAPI('SendEmail', [
                'messagename' => 'General Messages',
                'mergefields' => [
                    'email_subject' => 'Your Login Verification Code - ' . $companyName,
                    'email_message' => $emailContent,
                ],
                'id' => $clientId,
            ]);

            if ($result['result'] === 'success') {
                EmailVerificationHelper::logAction($clientId, $client->email, '2fa_email_sent', self::getClientIp(), '2FA code sent via General Messages');
                return true;
            }

            // Log error
            EmailVerificationHelper::logAction($clientId, $client->email, 'email_failed', self::getClientIp(), '2FA email failed: ' . ($result['message'] ?? 'Unknown error'));
            return false;
        }

        return false;
    }

    /**
     * Ensure 2FA email template exists in WHMCS
     */
    public static function ensure2FATemplateExists()
    {
        $templateExists = Capsule::table('tblemailtemplates')
            ->where('name', 'Email Verification Pro - 2FA Code')
            ->where('type', 'general')
            ->exists();

        if (!$templateExists) {
            $companyName = \WHMCS\Config\Setting::getValue('CompanyName') ?: 'Our Company';

            Capsule::table('tblemailtemplates')->insert([
                'type' => 'general',
                'name' => 'Email Verification Pro - 2FA Code',
                'subject' => 'Your Login Verification Code - ' . $companyName,
                'message' => self::get2FATemplateContent($companyName),
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
     * Get 2FA WHMCS template content
     *
     * @param string $companyName
     * @return string
     */
    private static function get2FATemplateContent($companyName)
    {
        return '<p>Dear {$client_name},</p>

<p>A login attempt was detected on your account. Use the verification code below to complete your login.</p>

<p style="text-align: center; margin: 30px 0;">
    <span style="display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 20px 40px; background-color: #f5f5f5; border-radius: 8px; font-family: monospace;">{$verification_code}</span>
</p>

<p><strong>This code will expire in {$code_expiry} minutes.</strong></p>

<p>Login Details:</p>
<ul>
    <li>IP Address: {$ip_address}</li>
    <li>Browser: {$browser}</li>
    <li>Time: {$timestamp}</li>
</ul>

<p style="color: #cc0000;"><strong>Security Notice:</strong> If you did not attempt to log in, please change your password immediately and contact our support team.</p>

<p>Best regards,<br>' . htmlspecialchars($companyName) . ' Team</p>

<p style="font-size: 12px; color: #666666;">Never share this code with anyone. Our staff will never ask for your verification code.</p>';
    }

    /**
     * Get spam-free 2FA email content
     *
     * @param object $client
     * @param string $code
     * @param int $expiryMinutes
     * @param string $companyName
     * @return string
     */
    private static function get2FAEmailContent($client, $code, $expiryMinutes, $companyName)
    {
        $firstName = htmlspecialchars($client->firstname);
        $companyNameClean = htmlspecialchars($companyName);
        $ip = self::getClientIp();
        $browser = self::getBrowserName();
        $timestamp = date('M j, Y H:i:s');

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #333333; background-color: #f5f5f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px 40px; text-align: center; border-bottom: 1px solid #eeeeee;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: normal; color: #333333;">Login Verification</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 30px 40px;">
                            <p style="margin: 0 0 20px 0;">Dear ' . $firstName . ',</p>

                            <p style="margin: 0 0 20px 0;">A login attempt was detected on your account with ' . $companyNameClean . '. Please use the verification code below to complete your login.</p>

                            <!-- Code Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <div style="display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 20px 40px; background-color: #f8f8f8; border-radius: 8px; font-family: Courier New, monospace; border: 2px dashed #dddddd;">' . htmlspecialchars($code) . '</div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 20px 0; text-align: center; font-size: 13px; color: #666666;">This code will expire in <strong>' . (int)$expiryMinutes . ' minutes</strong></p>

                            <!-- Login Details -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f8f8f8; border-radius: 4px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 15px;">
                                        <p style="margin: 0 0 5px 0; font-weight: bold; color: #333333;">Login Details:</p>
                                        <p style="margin: 5px 0; color: #666666; font-size: 13px;">IP Address: ' . htmlspecialchars($ip) . '</p>
                                        <p style="margin: 5px 0; color: #666666; font-size: 13px;">Browser: ' . htmlspecialchars($browser) . '</p>
                                        <p style="margin: 5px 0; color: #666666; font-size: 13px;">Time: ' . htmlspecialchars($timestamp) . '</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 20px 0 0 0; padding: 15px; background-color: #fff0f0; border-left: 4px solid #cc0000; font-size: 13px;"><strong>Security Notice:</strong> If you did not attempt to log in, please change your password immediately and contact our support team.</p>

                            <p style="margin: 30px 0 0 0;">Best regards,<br>' . $companyNameClean . ' Team</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px; background-color: #fafafa; border-top: 1px solid #eeeeee; text-align: center; font-size: 12px; color: #999999; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0;">Never share this code with anyone.</p>
                            <p style="margin: 10px 0 0 0;">Our staff will never ask for your verification code.</p>
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
     * Verify a 2FA code
     *
     * @param int $clientId
     * @param string $inputCode
     * @return array
     */
    public static function verifyCode($clientId, $inputCode)
    {
        $config = self::getConfig();
        $maxAttempts = $config['twofa_max_attempts'];
        $lockoutDuration = $config['twofa_lockout_duration'];

        $record = Capsule::table('mod_evp_twofa_codes')
            ->where('client_id', $clientId)
            ->first();

        if (!$record) {
            return ['success' => false, 'error' => 'No verification code found. Please request a new one.'];
        }

        // Check if expired
        if (strtotime($record->expires_at) < time()) {
            return ['success' => false, 'error' => 'Verification code has expired. Please request a new one.', 'expired' => true];
        }

        // Check if locked out
        if ($record->attempts >= $maxAttempts) {
            $lockedUntil = strtotime($record->updated_at ?? $record->created_at) + ($lockoutDuration * 60);
            if (time() < $lockedUntil) {
                $waitMinutes = ceil(($lockedUntil - time()) / 60);
                return ['success' => false, 'error' => "Too many attempts. Please wait {$waitMinutes} minutes.", 'locked' => true];
            } else {
                // Reset attempts after lockout
                Capsule::table('mod_evp_twofa_codes')
                    ->where('client_id', $clientId)
                    ->update(['attempts' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
                $record->attempts = 0;
            }
        }

        // Verify code
        if (password_verify(strtoupper($inputCode), $record->code) || strtoupper($inputCode) === strtoupper($record->plain_code)) {
            // Success - delete the code
            Capsule::table('mod_evp_twofa_codes')
                ->where('client_id', $clientId)
                ->delete();

            // Remember device if enabled
            if ($config['twofa_remember_device']) {
                self::rememberDevice($clientId);
            }

            // Log success
            EmailVerificationHelper::logAction($clientId, $record->email, '2fa_verified', self::getClientIp());

            return ['success' => true];
        }

        // Increment attempts
        Capsule::table('mod_evp_twofa_codes')
            ->where('client_id', $clientId)
            ->update([
                'attempts' => $record->attempts + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $remainingAttempts = $maxAttempts - $record->attempts - 1;

        // Log failed attempt
        EmailVerificationHelper::logAction($clientId, $record->email, '2fa_failed', self::getClientIp(), "Attempt " . ($record->attempts + 1));

        return [
            'success' => false,
            'error' => 'Invalid verification code. ' . ($remainingAttempts > 0 ? "{$remainingAttempts} attempts remaining." : 'You will be locked out.'),
            'remaining' => $remainingAttempts,
        ];
    }

    /**
     * Remember a device for future logins
     *
     * @param int $clientId
     */
    public static function rememberDevice($clientId)
    {
        $config = self::getConfig();
        $days = $config['twofa_remember_days'];
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        // Store in database
        Capsule::table('mod_evp_remembered_devices')->insert([
            'client_id' => $clientId,
            'device_token' => $hashedToken,
            'ip_address' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$days} days")),
        ]);

        // Set cookie
        $expiry = time() + ($days * 24 * 60 * 60);
        setcookie('evp_device_token', $token, $expiry, '/', '', true, true);
    }

    /**
     * Check if device is remembered
     *
     * @param int $clientId
     * @return bool
     */
    public static function isDeviceRemembered($clientId)
    {
        if (!isset($_COOKIE['evp_device_token'])) {
            return false;
        }

        $token = $_COOKIE['evp_device_token'];
        $hashedToken = hash('sha256', $token);

        $device = Capsule::table('mod_evp_remembered_devices')
            ->where('client_id', $clientId)
            ->where('device_token', $hashedToken)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();

        return $device !== null;
    }

    /**
     * Clear remembered devices for a client
     *
     * @param int $clientId
     */
    public static function clearRememberedDevices($clientId)
    {
        Capsule::table('mod_evp_remembered_devices')
            ->where('client_id', $clientId)
            ->delete();
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public static function getClientIp()
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                    return trim($ip);
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get browser name from user agent
     *
     * @return string
     */
    public static function getBrowserName()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        if (strpos($userAgent, 'Opera') !== false) return 'Opera';
        if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) return 'Internet Explorer';
        
        return 'Unknown Browser';
    }

    /**
     * Clean up expired codes and devices
     */
    public static function cleanup()
    {
        // Delete expired codes
        Capsule::table('mod_evp_twofa_codes')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();

        // Delete expired remembered devices
        Capsule::table('mod_evp_remembered_devices')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }

    /**
     * Get 2FA statistics
     *
     * @return array
     */
    public static function getStatistics()
    {
        $today = date('Y-m-d');
        $week = date('Y-m-d', strtotime('-7 days'));

        return [
            'codes_sent_today' => Capsule::table('mod_email_verification_log')
                ->where('action', '2fa_code_created')
                ->whereDate('created_at', $today)
                ->count(),
            'codes_verified_today' => Capsule::table('mod_email_verification_log')
                ->where('action', '2fa_verified')
                ->whereDate('created_at', $today)
                ->count(),
            'failed_attempts_today' => Capsule::table('mod_email_verification_log')
                ->where('action', '2fa_failed')
                ->whereDate('created_at', $today)
                ->count(),
            'remembered_devices' => Capsule::table('mod_evp_remembered_devices')
                ->where('expires_at', '>', date('Y-m-d H:i:s'))
                ->count(),
            'pending_codes' => Capsule::table('mod_evp_twofa_codes')
                ->where('expires_at', '>', date('Y-m-d H:i:s'))
                ->count(),
        ];
    }
}
