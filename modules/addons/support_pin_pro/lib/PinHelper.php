<?php

namespace WHMCS\Module\Addon\SupportPinPro;

use WHMCS\Database\Capsule;

class PinHelper
{
    /**
     * Generate a new PIN for a client
     *
     * @param int $clientId
     * @param int $contactId
     * @return string
     */
    public static function generatePin($clientId, $contactId = 0)
    {
        $vars = self::getModuleVars();
        $length = (int)$vars['pin_length'] ?: 5;
        $expiryHours = (int)$vars['pin_expiry'] ?: 24;
        $neverExpire = $vars['never_expire'] === 'on';
        $encrypt = $vars['encrypt_pin'] === 'on';
        $multiActive = $vars['multi_active'] === 'on';

        // Expire old pins if multi-active is disabled
        if (!$multiActive) {
            Capsule::table('mod_support_pin_pro')
                ->where('client_id', $clientId)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        // Generate numeric PIN
        $pin = '';
        for ($i = 0; $i < $length; $i++) {
            $pin .= mt_rand(0, 9);
        }

        $expiresAt = $neverExpire ? null : date('Y-m-d H:i:s', strtotime("+$expiryHours hours"));
        
        $insertData = [
            'client_id' => $clientId,
            'contact_id' => $contactId,
            'is_active' => true,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($encrypt) {
            $insertData['pin'] = password_hash($pin, PASSWORD_DEFAULT);
            $insertData['plain_pin'] = null; // Don't store plain if encrypted
        } else {
            $insertData['pin'] = $pin;
            $insertData['plain_pin'] = $pin;
        }

        Capsule::table('mod_support_pin_pro')->insert($insertData);

        self::logAction($clientId, 'generation', "Generated new PIN.");

        return $pin;
    }

    /**
     * Get active PIN for a client
     *
     * @param int $clientId
     * @return object|null
     */
    public static function getActivePin($clientId)
    {
        return Capsule::table('mod_support_pin_pro')
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Validate a PIN and return client ID if valid
     *
     * @param string $inputPin
     * @return int|false
     */
    public static function validatePin($inputPin)
    {
        $vars = self::getModuleVars();
        $encrypt = $vars['encrypt_pin'] === 'on';
        $expireOnUsage = $vars['expire_on_usage'] === 'on';
        $adminId = $_SESSION['adminid'] ?? 0;

        $activePins = Capsule::table('mod_support_pin_pro')
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', date('Y-m-d H:i:s'));
            })
            ->get();

        foreach ($activePins as $row) {
            $match = false;
            if ($encrypt) {
                if (password_verify($inputPin, $row->pin)) {
                    $match = true;
                }
            } else {
                if ($inputPin === $row->pin) {
                    $match = true;
                }
            }

            if ($match) {
                // Pin is valid!
                if ($expireOnUsage) {
                    Capsule::table('mod_support_pin_pro')
                        ->where('id', $row->id)
                        ->update([
                            'is_active' => false,
                            'used_at' => date('Y-m-d H:i:s'),
                            'used_by_admin' => $adminId
                        ]);
                }

                // Grant administrative access session
                self::grantAdminAccess($adminId, $row->client_id);

                self::logAction($row->client_id, 'verification', "PIN verified by admin ID: $adminId", $adminId);
                
                return (int)$row->client_id;
            }
        }

        return false;
    }

    /**
     * Grant temporary admin access to a client
     *
     * @param int $adminId
     * @param int $clientId
     */
    public static function grantAdminAccess($adminId, $clientId)
    {
        if (!$adminId || !$clientId) return;

        $vars = self::getModuleVars();
        $accessMin = (int)$vars['admin_access_time'] ?: 30;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$accessMin minutes"));

        Capsule::table('mod_support_pin_pro_sessions')->updateOrInsert(
            ['admin_id' => $adminId, 'client_id' => $clientId],
            ['expires_at' => $expiresAt, 'created_at' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * Check if admin has valid access session for a client
     *
     * @param int $adminId
     * @param int $clientId
     * @return bool
     */
    public static function hasAdminAccess($adminId, $clientId)
    {
        if (!$adminId || !$clientId) return false;

        // Super admins always have access? Or maybe not if strict mode is on.
        // Let's check settings.
        $vars = self::getModuleVars();
        $blockExpired = $vars['block_expired_view'] === 'on';

        if (!$blockExpired) return true;

        $session = Capsule::table('mod_support_pin_pro_sessions')
            ->where('admin_id', $adminId)
            ->where('client_id', $clientId)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();

        if ($session) return true;

        // Check assigned ticket access if enabled
        if ($vars['support_team_access'] === 'on') {
            $isAssigned = Capsule::table('tbltickets')
                ->where('userid', $clientId)
                ->where('flag', $adminId)
                ->whereIn('status', ['Open', 'In Progress', 'On Hold'])
                ->exists();

            if ($isAssigned) {
                // Grant session automatically
                $accessMin = (int)$vars['support_team_time'] ?: 60;
                $expiresAt = date('Y-m-d H:i:s', strtotime("+$accessMin minutes"));
                
                Capsule::table('mod_support_pin_pro_sessions')->updateOrInsert(
                    ['admin_id' => $adminId, 'client_id' => $clientId],
                    ['expires_at' => $expiresAt, 'created_at' => date('Y-m-d H:i:s')]
                );
                return true;
            }
        }

        return false;
    }

    /**
     * Helper to get module variables
     *
     * @return array
     */
    public static function getModuleVars()
    {
        static $vars = null;
        if ($vars === null) {
            $data = Capsule::table('tbladdonmodules')->where('module', 'support_pin_pro')->get();
            $vars = [];
            foreach ($data as $row) {
                $vars[$row->setting] = $row->value;
            }
        }
        return $vars;
    }

    /**
     * Log a PIN related action
     *
     * @param int $clientId
     * @param string $action
     * @param string $details
     * @param int $adminId
     */
    public static function logAction($clientId, $action, $details = '', $adminId = null)
    {
        Capsule::table('mod_support_pin_pro_logs')->insert([
            'client_id' => $clientId,
            'admin_id' => $adminId,
            'action' => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'details' => $details,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
