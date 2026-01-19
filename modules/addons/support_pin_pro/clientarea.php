<?php

use WHMCS\Module\Addon\SupportPinPro\PinHelper;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/lib/PinHelper.php';

function support_pin_pro_clientarea($vars)
{
    $userId = $_SESSION['uid'] ?? 0;
    if (!$userId) {
        return ['status' => 'error', 'description' => 'Login required'];
    }

    $action = $_GET['action'] ?? '';
    $moduleVars = PinHelper::getModuleVars();

    // Contact limitation check
    if ($moduleVars['contact_limit'] === 'on') {
        $contactId = $_SESSION['cid'] ?? 0;
        if ($contactId) {
            return [
                'pagetitle' => 'Support PIN',
                'templatefile' => 'client_area_pin',
                'vars' => [
                    'error' => 'Only the primary account holder can manage the Support PIN.',
                ],
            ];
        }
    }

    if ($action === 'generate' && $moduleVars['enable_generate_btn'] === 'on') {
        $newPin = PinHelper::generatePin($userId);
        $_SESSION['new_support_pin'] = $newPin;
        header("Location: index.php?m=support_pin_pro&success=generated");
        exit;
    }

    $activePin = PinHelper::getActivePin($userId);
    $pinValue = 'None';
    $expiresAt = '';
    $isMasked = ($moduleVars['hide_pin'] === 'on');

    if ($activePin) {
        if (isset($_SESSION['new_support_pin'])) {
            $pinValue = $_SESSION['new_support_pin'];
            unset($_SESSION['new_support_pin']);
            $isMasked = false; // Show full pin right after generation
        } else {
            $pinValue = $activePin->plain_pin ?: 'Encrypted';
            if ($isMasked && $pinValue !== 'Encrypted') {
                $pinValue = str_repeat('*', strlen($pinValue) - 2) . substr($pinValue, -2);
            }
        }
        $expiresAt = $activePin->expires_at;
    }

    return [
        'pagetitle' => 'Support PIN',
        'templatefile' => 'templates/client_area_pin',
        'vars' => [
            'activePin' => $activePin,
            'pinValue' => $pinValue,
            'expiresAt' => $expiresAt,
            'isMasked' => $isMasked,
            'enableGenerate' => $moduleVars['enable_generate_btn'] === 'on',
            'success' => $_GET['success'] ?? '',
        ],
    ];
}
