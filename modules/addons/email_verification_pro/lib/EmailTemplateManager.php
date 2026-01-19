<?php
/**
 * Email Verification Pro - Email Template Manager
 *
 * Manages customizable email templates for the module.
 * Templates are optimized for spam-free delivery.
 *
 * @copyright 2026 Waqas Ahmed Waseer
 * @license Proprietary
 */

namespace WHMCS\Module\Addon\EmailVerificationPro;

use WHMCS\Database\Capsule;

class EmailTemplateManager
{
    /**
     * Default email templates - Optimized for spam-free delivery
     * - No excessive caps or exclamation marks
     * - No spam trigger words (FREE, URGENT, ACT NOW, etc.)
     * - Proper text-to-image ratio
     * - Clean HTML table structure for email clients
     * - No emojis in subjects (can trigger spam filters)
     */
    private static $defaultTemplates = [
        'verification_email' => [
            'name' => 'Email Verification',
            'subject' => 'Action Required: Verify Your Email Address',
            'body' => '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f5f5f5;">
    <tr>
        <td align="center" style="padding: 40px 20px;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px;">
                <tr>
                    <td style="padding: 40px 40px 20px 40px; text-align: center; border-bottom: 1px solid #eeeeee;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: normal; color: #333333;">Email Verification</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px 40px;">
                        <p style="margin: 0 0 20px 0; color: #333333;">Dear {client_name},</p>
                        <p style="margin: 0 0 20px 0; color: #333333;">Thank you for registering. To complete your account setup and access all features, please verify your email address by clicking the button below.</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="center" style="padding: 20px 0;">
                                    <a href="{verification_link}" style="display: inline-block; padding: 14px 32px; background-color: #0066cc; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Verify Email Address</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin: 20px 0; font-size: 13px; color: #666666;">If the button does not work, copy and paste this link:</p>
                        <p style="margin: 0 0 20px 0; padding: 12px; background-color: #f8f8f8; border-radius: 4px; word-break: break-all; font-size: 12px; color: #0066cc;">{verification_link}</p>
                        <p style="margin: 0; padding: 15px; background-color: #fff8e6; border-left: 4px solid #e6a700; font-size: 13px; color: #333333;"><strong>Note:</strong> This link will expire in {link_expiry} hours.</p>
                        <p style="margin: 20px 0 0 0; color: #666666; font-size: 13px;">If you did not create an account, you can safely ignore this email.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px 40px; background-color: #fafafa; text-align: center; font-size: 12px; color: #999999; border-radius: 0 0 8px 8px;">
                        <p style="margin: 0;">This is an automated message. Please do not reply.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>',
            'variables' => '{client_name}, {client_email}, {verification_link}, {link_expiry}',
        ],

        '2fa_code' => [
            'name' => 'Two-Factor Authentication Code',
            'subject' => 'Your Login Verification Code',
            'body' => '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f5f5f5;">
    <tr>
        <td align="center" style="padding: 40px 20px;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px;">
                <tr>
                    <td style="padding: 40px 40px 20px 40px; text-align: center; border-bottom: 1px solid #eeeeee;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: normal; color: #333333;">Login Verification</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px 40px;">
                        <p style="margin: 0 0 20px 0; color: #333333;">Dear {client_name},</p>
                        <p style="margin: 0 0 20px 0; color: #333333;">A login attempt was detected on your account. Use the code below to complete your login:</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="center" style="padding: 20px 0;">
                                    <div style="display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 20px 40px; background-color: #f8f8f8; border-radius: 8px; font-family: monospace; border: 2px dashed #dddddd;">{verification_code}</div>
                                </td>
                            </tr>
                        </table>
                        <p style="margin: 20px 0; text-align: center; font-size: 13px; color: #666666;">This code will expire in <strong>{code_expiry} minutes</strong></p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f8f8f8; border-radius: 4px; margin: 20px 0;">
                            <tr>
                                <td style="padding: 15px;">
                                    <p style="margin: 0 0 5px 0; font-weight: bold; color: #333333;">Login Details:</p>
                                    <p style="margin: 5px 0; color: #666666; font-size: 13px;">IP Address: {ip_address}</p>
                                    <p style="margin: 5px 0; color: #666666; font-size: 13px;">Browser: {browser}</p>
                                    <p style="margin: 5px 0; color: #666666; font-size: 13px;">Time: {timestamp}</p>
                                </td>
                            </tr>
                        </table>
                        <p style="margin: 0; padding: 15px; background-color: #fff0f0; border-left: 4px solid #cc0000; font-size: 13px; color: #333333;"><strong>Security Notice:</strong> If you did not attempt to log in, please change your password immediately.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px 40px; background-color: #fafafa; text-align: center; font-size: 12px; color: #999999; border-radius: 0 0 8px 8px;">
                        <p style="margin: 0;">Never share this code with anyone. Our staff will never ask for your code.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>',
            'variables' => '{client_name}, {verification_code}, {code_expiry}, {ip_address}, {browser}, {timestamp}',
        ],

        'verification_reminder' => [
            'name' => 'Verification Reminder',
            'subject' => 'Reminder: Please Verify Your Email Address',
            'body' => '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f5f5f5;">
    <tr>
        <td align="center" style="padding: 40px 20px;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px;">
                <tr>
                    <td style="padding: 40px 40px 20px 40px; text-align: center; border-bottom: 1px solid #eeeeee;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: normal; color: #333333;">Verification Reminder</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px 40px;">
                        <p style="margin: 0 0 20px 0; color: #333333;">Dear {client_name},</p>
                        <p style="margin: 0 0 20px 0; color: #333333;">We noticed you have not yet verified your email address. Your account access may be limited until verification is complete.</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="center" style="padding: 20px 0;">
                                    <a href="{verification_link}" style="display: inline-block; padding: 14px 32px; background-color: #0066cc; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Verify Email Now</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin: 0; padding: 15px; background-color: #fff8e6; border-left: 4px solid #e6a700; font-size: 13px; color: #333333;"><strong>Notice:</strong> If you do not verify within {days_remaining} days, your account may be suspended.</p>
                        <p style="margin: 20px 0 0 0; color: #666666; font-size: 13px;">If you did not create this account, please contact our support team.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px 40px; background-color: #fafafa; text-align: center; font-size: 12px; color: #999999; border-radius: 0 0 8px 8px;">
                        <p style="margin: 0;">Need help? Contact our support team.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>',
            'variables' => '{client_name}, {verification_link}, {days_remaining}',
        ],

        'account_termination_warning' => [
            'name' => 'Account Termination Warning',
            'subject' => 'Important: Action Required for Your Account',
            'body' => '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f5f5f5;">
    <tr>
        <td align="center" style="padding: 40px 20px;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px;">
                <tr>
                    <td style="padding: 40px 40px 20px 40px; text-align: center; border-bottom: 1px solid #eeeeee;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: normal; color: #cc0000;">Account Action Required</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px 40px;">
                        <p style="margin: 0 0 20px 0; color: #333333;">Dear {client_name},</p>
                        <p style="margin: 0 0 20px 0; color: #333333;"><strong>Your account will be closed in {hours_remaining} hours</strong> due to an unverified email address.</p>
                        <p style="margin: 0 0 20px 0; color: #333333;">Please verify your email immediately to keep your account active:</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="center" style="padding: 20px 0;">
                                    <a href="{verification_link}" style="display: inline-block; padding: 14px 32px; background-color: #cc0000; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Verify Email Now</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin: 0; padding: 15px; background-color: #fff0f0; border-left: 4px solid #cc0000; font-size: 13px; color: #333333;"><strong>Important:</strong> After closure, your data may be permanently deleted and cannot be recovered.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px 40px; background-color: #fafafa; text-align: center; font-size: 12px; color: #999999; border-radius: 0 0 8px 8px;">
                        <p style="margin: 0;">If you believe this is an error, please contact support immediately.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>',
            'variables' => '{client_name}, {verification_link}, {hours_remaining}',
        ],

        'verification_success' => [
            'name' => 'Verification Success',
            'subject' => 'Email Verified Successfully',
            'body' => '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f5f5f5;">
    <tr>
        <td align="center" style="padding: 40px 20px;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px;">
                <tr>
                    <td style="padding: 40px 40px 20px 40px; text-align: center; border-bottom: 1px solid #eeeeee;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: normal; color: #28a745;">Email Verified</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px 40px;">
                        <p style="margin: 0 0 20px 0; color: #333333;">Dear {client_name},</p>
                        <p style="margin: 0 0 20px 0; color: #333333;">Your email address has been successfully verified. You now have full access to your account and all its features.</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="center" style="padding: 20px 0;">
                                    <a href="{client_area_link}" style="display: inline-block; padding: 14px 32px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Go to My Account</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin: 0; padding: 15px; background-color: #e8f5e9; border-left: 4px solid #28a745; font-size: 13px; color: #333333;">Welcome aboard! Thank you for choosing us.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px 40px; background-color: #fafafa; text-align: center; font-size: 12px; color: #999999; border-radius: 0 0 8px 8px;">
                        <p style="margin: 0;">Thank you for your business!</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>',
            'variables' => '{client_name}, {client_area_link}',
        ],

        'new_device_login' => [
            'name' => 'New Device Login Alert',
            'subject' => 'New Login Detected on Your Account',
            'body' => '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f5f5f5;">
    <tr>
        <td align="center" style="padding: 40px 20px;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px;">
                <tr>
                    <td style="padding: 40px 40px 20px 40px; text-align: center; border-bottom: 1px solid #eeeeee;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: normal; color: #333333;">New Login Detected</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 30px 40px;">
                        <p style="margin: 0 0 20px 0; color: #333333;">Dear {client_name},</p>
                        <p style="margin: 0 0 20px 0; color: #333333;">A new login was detected on your account from a new device or location:</p>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f8f8f8; border-radius: 4px; margin: 20px 0;">
                            <tr>
                                <td style="padding: 15px;">
                                    <p style="margin: 5px 0; color: #333333;"><strong>IP Address:</strong> {ip_address}</p>
                                    <p style="margin: 5px 0; color: #333333;"><strong>Browser:</strong> {browser}</p>
                                    <p style="margin: 5px 0; color: #333333;"><strong>Location:</strong> {location}</p>
                                    <p style="margin: 5px 0; color: #333333;"><strong>Time:</strong> {timestamp}</p>
                                </td>
                            </tr>
                        </table>
                        <p style="margin: 0 0 10px 0; color: #333333;"><strong>Was this you?</strong></p>
                        <p style="margin: 0 0 20px 0; color: #666666;">If this was you, you can safely ignore this email. If you do not recognize this activity, please change your password immediately and contact our support team.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 20px 40px; background-color: #fafafa; text-align: center; font-size: 12px; color: #999999; border-radius: 0 0 8px 8px;">
                        <p style="margin: 0;">This is a security alert from your account.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>',
            'variables' => '{client_name}, {ip_address}, {browser}, {location}, {timestamp}',
        ],
    ];

    /**
     * Initialize email templates table
     */
    public static function initialize()
    {
        // Create table if not exists
        if (!Capsule::schema()->hasTable('mod_evp_email_templates')) {
            Capsule::schema()->create('mod_evp_email_templates', function ($table) {
                $table->increments('id');
                $table->string('template_key', 50)->unique();
                $table->string('name', 100);
                $table->string('subject', 255);
                $table->text('body');
                $table->text('variables')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });

            // Insert default templates
            foreach (self::$defaultTemplates as $key => $template) {
                Capsule::table('mod_evp_email_templates')->insert([
                    'template_key' => $key,
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'variables' => $template['variables'],
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * Get an email template
     *
     * @param string $key
     * @return array
     */
    public static function getTemplate($key)
    {
        $template = Capsule::table('mod_evp_email_templates')
            ->where('template_key', $key)
            ->first();

        if ($template) {
            return [
                'name' => $template->name,
                'subject' => $template->subject,
                'body' => $template->body,
                'variables' => $template->variables,
                'is_active' => $template->is_active,
            ];
        }

        // Return default if not found
        if (isset(self::$defaultTemplates[$key])) {
            return self::$defaultTemplates[$key];
        }

        return [
            'name' => 'Unknown Template',
            'subject' => 'Notification',
            'body' => 'No template content.',
            'variables' => '',
            'is_active' => true,
        ];
    }

    /**
     * Get all templates
     *
     * @return array
     */
    public static function getAllTemplates()
    {
        return Capsule::table('mod_evp_email_templates')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Update a template
     *
     * @param string $key
     * @param array $data
     * @return bool
     */
    public static function updateTemplate($key, $data)
    {
        return Capsule::table('mod_evp_email_templates')
            ->where('template_key', $key)
            ->update([
                'name' => $data['name'] ?? '',
                'subject' => $data['subject'] ?? '',
                'body' => $data['body'] ?? '',
                'is_active' => $data['is_active'] ?? true,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Reset a template to default
     *
     * @param string $key
     * @return bool
     */
    public static function resetTemplate($key)
    {
        if (!isset(self::$defaultTemplates[$key])) {
            return false;
        }

        $default = self::$defaultTemplates[$key];

        return Capsule::table('mod_evp_email_templates')
            ->where('template_key', $key)
            ->update([
                'name' => $default['name'],
                'subject' => $default['subject'],
                'body' => $default['body'],
                'variables' => $default['variables'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Reset all templates to default
     *
     * @return int Number of templates reset
     */
    public static function resetAllTemplates()
    {
        $count = 0;
        foreach (self::$defaultTemplates as $key => $template) {
            if (self::resetTemplate($key)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Send an email using a template
     *
     * @param string $templateKey
     * @param int $clientId
     * @param array $replacements Key-value pairs for variable replacement
     * @return bool
     */
    public static function sendEmail($templateKey, $clientId, $replacements = [])
    {
        $template = self::getTemplate($templateKey);

        if (!$template['is_active']) {
            return false;
        }

        // Replace variables
        $subject = $template['subject'];
        $body = $template['body'];

        foreach ($replacements as $key => $value) {
            $subject = str_replace($key, $value, $subject);
            $body = str_replace($key, $value, $body);
        }

        // Send email via WHMCS
        if (function_exists('localAPI')) {
            $result = localAPI('SendEmail', [
                'messagename' => 'General Messages',
                'id' => $clientId,
                'mergefields' => [
                    'email_subject' => $subject,
                    'email_message' => $body,
                ],
            ]);
            return $result['result'] === 'success';
        }

        return false;
    }

    /**
     * Preview a template with sample data
     *
     * @param string $key
     * @return array
     */
    public static function previewTemplate($key)
    {
        $template = self::getTemplate($key);

        $sampleData = [
            '{client_name}' => 'John Doe',
            '{client_email}' => 'john@example.com',
            '{verification_link}' => 'https://example.com/verify?token=abc123xyz789def456',
            '{verification_code}' => '847291',
            '{link_expiry}' => '48',
            '{code_expiry}' => '10',
            '{ip_address}' => '192.168.1.100',
            '{browser}' => 'Chrome on Windows',
            '{location}' => 'New York, United States',
            '{timestamp}' => date('M j, Y H:i:s'),
            '{days_remaining}' => '3',
            '{hours_remaining}' => '24',
            '{client_area_link}' => 'https://example.com/clientarea.php',
            '{activity_type}' => 'Multiple failed login attempts',
            '{activity_details}' => '5 failed login attempts from different IP addresses',
        ];

        $subject = $template['subject'];
        $body = $template['body'];

        foreach ($sampleData as $varKey => $value) {
            $subject = str_replace($varKey, $value, $subject);
            $body = str_replace($varKey, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
