# Email Verification Pro for WHMCS

A comprehensive email verification addon module for WHMCS that requires customers to verify their email addresses before accessing their account or completing checkout.

## Features

### Core Functionality
- **Two Verification Modes**:
  - **All Pages**: Block entire account access until email is verified (except support tickets and profile)
  - **Checkout Only**: Allow account access but require verification before placing orders

### Account Management
- **Auto-Terminate**: Automatically close accounts that don't verify within X days
- **Auto-Delete**: Permanently delete unverified accounts with no active orders after X days
- **Reminder Emails**: Automatically resend verification emails after X days

### Security Features
- **Ban Email Addresses**: Block specific email addresses from registering
- **Ban IP Addresses**: Block specific IPs from registering
- **Block Disposable Emails**: Pre-configured list of disposable email providers
- **Google reCAPTCHA v3**: Extra security on verification page
- **Cloudflare Turnstile**: Alternative CAPTCHA option
- **Lock Email Changes**: Prevent unverified users from changing their email

### Admin Features
- **Dashboard**: Overview statistics of verification status
- **Pending Verifications**: Manage and manually verify pending accounts
- **Verified Clients**: View list of verified clients
- **Ban Management**: Easy interface to manage email, IP, and provider bans
- **Activity Logs**: Track all verification-related activities
- **Client IP Display**: See client IPs in admin area

### Compatibility
- WHMCS 7.10+ to 8.x
- Works with Six and Twenty-One templates
- Multi-language support
- PHP 7.4+

## Installation

1. **Upload Files**
   
   Upload the `email_verification_pro` folder to:
   ```
   /path/to/whmcs/modules/addons/email_verification_pro/
   ```

2. **Activate the Module**
   
   - Log in to WHMCS Admin Area
   - Navigate to **Setup > Addon Modules**
   - Find "Email Verification Pro" and click **Activate**

3. **Configure the Module**
   
   Click **Configure** and set up:
   - Verification Type (All Pages or Checkout)
   - Auto-terminate settings
   - Auto-delete settings
   - Reminder email frequency
   - Captcha settings (if desired)
   - Access permissions

4. **Set Access Control**
   
   Configure which admin roles can access the module.

## Module Structure

```
email_verification_pro/
├── email_verification_pro.php   # Main addon file
├── hooks.php                    # WHMCS hooks
├── clientarea.php               # Client area controller
├── whmcs.json                   # Module configuration
├── logo.png                     # Module logo
├── README.md                    # Documentation
├── lib/
│   └── EmailVerificationHelper.php  # Helper class
├── lang/
│   └── english.php              # Language strings
└── templates/
    ├── verify_required.tpl      # Verification pending page
    ├── verify_success.tpl       # Verification success page
    ├── verify_error.tpl         # Verification error page
    ├── already_verified.tpl     # Already verified page
    └── verify_login_required.tpl # Login required page
```

## Configuration Options

| Option | Description |
|--------|-------------|
| **Verification Type** | All Pages or Checkout Only |
| **Auto-Terminate** | Enable/disable auto account closure |
| **Auto-Terminate Days** | Days before closing unverified accounts |
| **Auto-Delete** | Enable/disable auto account deletion |
| **Auto-Delete Days** | Days before deleting unverified accounts |
| **Resend Reminder Days** | Days before sending reminder emails |
| **Token Expiry Hours** | Hours before verification links expire |
| **Lock Email Change** | Prevent email changes for unverified users |
| **Allow Support Tickets** | Let unverified users access support |
| **Allow Profile Edit** | Let unverified users edit profile |
| **Captcha Type** | None, reCAPTCHA v3, or Turnstile |
| **Send Welcome After Verify** | Send WHMCS welcome email after verification |
| **Redirect After Verify** | Where to redirect after verification |

## How It Works

### For New Registrations

1. Customer registers a new account
2. Module creates a verification token and sends email
3. Customer clicks verification link
4. Account is marked as verified
5. Customer can access all account features

### All Pages Mode

- Unverified customers are redirected to verification page
- Only support tickets and profile are accessible (if configured)
- All other pages show verification required message

### Checkout Mode

- Unverified customers can browse and add to cart
- At checkout, verification is required
- Order is not processed until email is verified

## Database Tables

The module creates the following tables:

- `mod_email_verification_tokens` - Stores verification tokens
- `mod_email_verification_banned_emails` - Banned email addresses
- `mod_email_verification_banned_ips` - Banned IP addresses
- `mod_email_verification_banned_providers` - Banned email providers
- `mod_email_verification_log` - Activity log

## Customization

### Templates

Templates are located in `templates/` folder and use WHMCS Smarty templating. You can customize the look and feel by editing these files.

### Language

Add new language files by copying `lang/english.php` to `lang/yourlanguage.php` and translating the strings.

### Email Template

The verification email content can be customized in `lib/EmailVerificationHelper.php` in the `getVerificationEmailContent()` method.

## Cron Job

The module hooks into WHMCS's daily cron job to:
- Send reminder emails to unverified accounts
- Auto-close accounts past the termination threshold
- Auto-delete accounts past the deletion threshold

Ensure your WHMCS cron is running daily for these features to work.

## Troubleshooting

### Verification Emails Not Sending

- Check WHMCS mail queue for errors
- Verify SMTP settings are correct
- Check spam folders

### Clients Can Still Access Account

- Verify the module is activated
- Check the verification type setting
- Clear WHMCS cache

### Captcha Not Working

- Verify API keys are correct
- Check browser console for errors
- Ensure scripts are loading

## Changelog

### Version 1.0.0
- Initial release
- Two verification modes (All Pages / Checkout)
- Auto-terminate and auto-delete functionality
- Ban management (email, IP, provider)
- reCAPTCHA v3 and Turnstile support
- Activity logging
- Admin dashboard
- Multi-language support

## License

This module is proprietary software. Unauthorized copying, modification, or distribution is prohibited.

## Support

For support, please contact:
- Email: support@example.com
- Website: https://example.com/support

---

**Email Verification Pro** - Protect your business from fraudulent registrations! 🛡️
