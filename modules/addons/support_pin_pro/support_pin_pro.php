<?php
/**
 * Support PIN Pro for WHMCS
 *
 * A robust security module that allows clients to generate verification PINs
 * for staff to verify account ownership.
 *
 * @copyright 2026
 * @license Open Source (Free)
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
function support_pin_pro_config()
{
    return [
        'name' => 'Support PIN Pro',
        'description' => 'Verify account holders with a secure, time-limited Support PIN. Enhance security and prevent unauthorized access.',
        'version' => '1.0.0',
        'author' => 'WHMCS Developer',
        'language' => 'english',
        'fields' => [
            'placement' => [
                'FriendlyName' => 'Enable PIN Show In',
                'Type' => 'dropdown',
                'Options' => [
                    'sidebar' => 'Side Bar Only',
                    'menu' => 'Support Menu Only',
                    'both' => 'Both Side Bar & Support Menu',
                ],
                'Default' => 'both',
            ],
            'enable_generate_btn' => [
                'FriendlyName' => 'Enable Generate Button',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Allow clients to generate a new PIN manually',
            ],
            'pin_expiry' => [
                'FriendlyName' => 'PIN Valid For (Hours)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '24',
                'Description' => 'PIN will automatically expire after these many hours',
            ],
            'never_expire' => [
                'FriendlyName' => 'Never Expire PIN',
                'Type' => 'yesno',
                'Default' => 'no',
                'Description' => 'If enabled, PINs will not expire automatically',
            ],
            'pin_length' => [
                'FriendlyName' => 'PIN Length',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '5',
            ],
            'multi_active' => [
                'FriendlyName' => 'Multi-Active PINs',
                'Type' => 'yesno',
                'Default' => 'no',
                'Description' => 'Allow multiple active PINs per client',
            ],
            'expire_on_usage' => [
                'FriendlyName' => 'PIN Expire On Usage',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Automatically expire PIN after staff verifies the client',
            ],
            'block_expired_view' => [
                'FriendlyName' => 'Block View if Expired',
                'Type' => 'yesno',
                'Default' => 'no',
                'Description' => 'Deny staff access to client profile if PIN is expired/invalid',
            ],
            'contact_limit' => [
                'FriendlyName' => 'Contact Limitation',
                'Type' => 'yesno',
                'Default' => 'no',
                'Description' => 'Only primary contacts can generate/view the PIN',
            ],
            'hide_pin' => [
                'FriendlyName' => 'Mask PIN',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Show only last 2 digits of the PIN by default',
            ],
            'encrypt_pin' => [
                'FriendlyName' => 'Encrypt PIN',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Save PIN encrypted in the database',
            ],
            'ajax_generate' => [
                'FriendlyName' => 'Ajax Generation',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Allow PIN generation without page refresh',
            ],
            'admin_access_time' => [
                'FriendlyName' => 'Admin Access Limit (Min)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '30',
                'Description' => 'Duration staff can access client data after verification',
            ],
            'support_team_access' => [
                'FriendlyName' => 'Ticket Auto-Access',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Allow staff access if a ticket is assigned to them',
            ],
            'support_team_time' => [
                'FriendlyName' => 'Ticket Access Limit (Min)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '60',
                'Description' => 'Duration staff can access client data when assigned a ticket',
            ],
            'hide_client_password' => [
                'FriendlyName' => 'Hide Service Passwords',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Hide client service passwords from staff even after PIN verification',
            ],
            'auto_reset_password' => [
                'FriendlyName' => 'Auto Reset Password',
                'Type' => 'yesno',
                'Default' => 'no',
                'Description' => 'Automatically reset service password after staff views it/ticket access',
            ],
            'delete_on_deactivate' => [
                'FriendlyName' => 'Clean on Deactivate',
                'Type' => 'yesno',
                'Default' => 'no',
                'Description' => 'Delete module tables upon deactivation',
            ],
        ],
    ];
}

/**
 * Module Activation
 *
 * @return array
 */
function support_pin_pro_activate()
{
    try {
        // Create Support PINs table
        if (!Capsule::schema()->hasTable('mod_support_pin_pro')) {
            Capsule::schema()->create('mod_support_pin_pro', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->integer('contact_id')->unsigned()->default(0);
                $table->string('pin', 255);
                $table->string('plain_pin', 20)->nullable(); // Used only if encryption is OFF
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('used_at')->nullable();
                $table->integer('used_by_admin')->unsigned()->nullable();
                $table->index(['client_id', 'pin']);
                $table->index(['is_active']);
                $table->index(['expires_at']);
            });
        }

        // Create Verification Logs table
        if (!Capsule::schema()->hasTable('mod_support_pin_pro_logs')) {
            Capsule::schema()->create('mod_support_pin_pro_logs', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->integer('admin_id')->unsigned()->nullable();
                $table->string('action', 50); // generation, verification, failed_attempt
                $table->string('ip_address', 45);
                $table->text('details')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['client_id']);
                $table->index(['admin_id']);
            });
        }

        // Create Admin Session permissions table (to track how long they have access)
        if (!Capsule::schema()->hasTable('mod_support_pin_pro_sessions')) {
            Capsule::schema()->create('mod_support_pin_pro_sessions', function ($table) {
                $table->increments('id');
                $table->integer('admin_id')->unsigned();
                $table->integer('client_id')->unsigned();
                $table->timestamp('expires_at');
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['admin_id', 'client_id']);
            });
        }

        return [
            'status' => 'success',
            'description' => 'Support PIN Pro has been activated successfully.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Error activating module: ' . $e->getMessage(),
        ];
    }
}

/**
 * Module Deactivation
 *
 * @return array
 */
function support_pin_pro_deactivate()
{
    try {
        $deleteOnDeactivate = Capsule::table('tbladdonmodules')
            ->where('module', 'support_pin_pro')
            ->where('setting', 'delete_on_deactivate')
            ->value('value');

        if ($deleteOnDeactivate === 'on') {
            Capsule::schema()->dropIfExists('mod_support_pin_pro');
            Capsule::schema()->dropIfExists('mod_support_pin_pro_logs');
            Capsule::schema()->dropIfExists('mod_support_pin_pro_sessions');
        }

        return [
            'status' => 'success',
            'description' => 'Support PIN Pro has been deactivated.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Error deactivating module: ' . $e->getMessage(),
        ];
    }
}

/**
 * Admin Area Output
 *
 * @param array $vars
 * @return string
 */
function support_pin_pro_output($vars)
{
    // Get current action
    $action = $_REQUEST['action'] ?? 'dashboard';

    // Handle form submissions
    if ($action === 'verify_pin') {
        $clientId = (int)$_POST['client_id'];
        $pin = $_POST['pin'];
        $result = PinHelper::validatePin($pin);

        if ($result && (int)$result === $clientId) {
            header("Location: clientssummary.php?userid=$clientId&pin_verified=1");
            exit;
        } else {
            PinHelper::logAction($clientId, 'failed_attempt', "Failed PIN verification attempt for PIN: $pin", $_SESSION['adminid']);
            header("Location: clientssummary.php?userid=$clientId&pin_error=1");
            exit;
        }
    }

    if ($action === 'quick_lookup') {
        $pin = $_POST['pin'];
        $result = PinHelper::validatePin($pin);

        if ($result) {
            header("Location: clientssummary.php?userid=$result&pin_verified=1");
            exit;
        } else {
            header("Location: addonmodules.php?module=support_pin_pro&error=invalid_pin");
            exit;
        }
    }

    $modulelink = $vars['modulelink'];

    // Output CSS
    echo '<style>
        .pin-pro-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 25px; margin-bottom: 25px; border: 1px solid #e5e7eb; }
        .pin-pro-alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .pin-pro-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #f3f4f6; padding-bottom: 15px; }
        .pin-pro-header h3 { margin: 0; color: #111827; font-weight: 700; font-size: 1.25rem; }
        .pin-pro-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .pin-pro-stat { background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 10px; padding: 20px; text-align: center; transition: transform 0.2s; }
        .pin-pro-stat:hover { transform: translateY(-2px); }
        .pin-pro-stat .label { color: #6b7280; font-size: 0.875rem; font-weight: 500; margin-bottom: 8px; display: block; }
        .pin-pro-stat .value { color: #1f2937; font-size: 1.875rem; font-weight: 800; display: block; }
        .pin-pro-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .pin-pro-table th { background: #f9fafb; color: #4b5563; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 12px 15px; border-bottom: 1px solid #e5e7eb; }
        .pin-pro-table td { padding: 15px; border-bottom: 1px solid #f3f4f6; color: #374151; font-size: 0.875rem; }
        .pin-pro-badge { padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #e0f2fe; color: #075985; }
        .pin-pro-btn { padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 0.875rem; cursor: pointer; border: none; transition: background 0.2s; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
    </style>';

    if ($_GET['error'] === 'invalid_pin') {
        echo '<div class="pin-pro-alert alert-danger"><i class="fas fa-times-circle"></i> The PIN you entered is invalid or has expired.</div>';
    }

    // Dashboard content
    $totalPins = Capsule::table('mod_support_pin_pro')->count();
    $activePins = Capsule::table('mod_support_pin_pro')->where('is_active', true)->where(function($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
    })->count();
    $totalVerifications = Capsule::table('mod_support_pin_pro_logs')->where('action', 'verification')->count();

    echo '<div class="pin-pro-card">
        <div class="pin-pro-header">
            <h3>Support PIN Pro Dashboard</h3>
            <div>
                <a href="configaddonmods.php?configure=support_pin_pro" class="pin-pro-btn btn-primary">Settings</a>
            </div>
        </div>

        <div class="pin-pro-stats">
            <div class="pin-pro-stat">
                <span class="label">Total PINs Generated</span>
                <span class="value">' . $totalPins . '</span>
            </div>
            <div class="pin-pro-stat">
                <span class="label">Currently Active PINs</span>
                <span class="value">' . $activePins . '</span>
            </div>
            <div class="pin-pro-stat">
                <span class="label">Total Verifications</span>
                <span class="value">' . $totalVerifications . '</span>
            </div>
        </div>

        <h4>Recent Verification Logs</h4>
        <table class="pin-pro-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Staff Member</th>
                    <th>Action</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>';

    $logs = Capsule::table('mod_support_pin_pro_logs')
        ->leftJoin('tblclients', 'mod_support_pin_pro_logs.client_id', '=', 'tblclients.id')
        ->leftJoin('tbladmins', 'mod_support_pin_pro_logs.admin_id', '=', 'tbladmins.id')
        ->orderBy('mod_support_pin_pro_logs.created_at', 'desc')
        ->limit(10)
        ->select('mod_support_pin_pro_logs.*', 'tblclients.firstname', 'tblclients.lastname', 'tbladmins.firstname as admin_fn', 'tbladmins.lastname as admin_ln')
        ->get();

    if ($logs->isEmpty()) {
        echo '<tr><td colspan="5" style="text-align:center;">No logs found.</td></tr>';
    } else {
        foreach ($logs as $log) {
            $badgeClass = ($log->action == 'verification') ? 'badge-success' : (($log->action == 'failed_attempt') ? 'badge-danger' : 'badge-info');
            echo '<tr>
                <td>' . date('M j, Y H:i', strtotime($log->created_at)) . '</td>
                <td><a href="clientssummary.php?userid=' . $log->client_id . '">' . htmlspecialchars($log->firstname . ' ' . $log->lastname) . '</a></td>
                <td>' . ($log->admin_id ? htmlspecialchars($log->admin_fn . ' ' . $log->admin_ln) : '-') . '</td>
                <td><span class="pin-pro-badge ' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $log->action)) . '</span></td>
                <td>' . htmlspecialchars($log->ip_address) . '</td>
            </tr>';
        }
    }

    echo '</tbody></table></div>';
}
