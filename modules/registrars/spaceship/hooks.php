<?php
/**
 * Spaceship.com Domain Registrar Module - Hooks
 *
 * @copyright 2026
 * @license MIT
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Implements the AdminAreaClientSummaryPage hook point.
 *
 * This hook is executed when the client summary page is viewed in the
 * admin area. It's used here to inject JavaScript that fetches and
 * displays real-time domain information from the Spaceship API.
 */
add_hook('AdminAreaClientSummaryPage', 1, function($vars) {
    // This hook can be called on pages other than the domain management page.
    // We only want to run our logic when a domain ID is present.
    $domainId = isset($vars['id']) ? (int)$vars['id'] : 0;
    if ($domainId === 0) {
        return '';
    }

    // Retrieve the domain details from the WHMCS database
    $domain = \WHMCS\Domain\Domain::find($domainId);
    if (is_null($domain)) {
        return '';
    }

    // Check if the domain is registered with the 'spaceship' module
    if ($domain->registrar !== 'spaceship') {
        return '';
    }

    // Get the module configuration parameters
    $params = $domain->getRegistrarData();
    if (!$params) {
        return '';
    }

    // Always fetch and display current nameservers
    try {
        $nameservers = spaceship_GetNameservers($params);
        if (isset($nameservers['error'])) {
            throw new \Exception($nameservers['error']);
        }
    } catch (\Exception $e) {
        logModuleCall('spaceship', 'RealtimeSync_GetNameservers', $params['domain'], $e->getMessage(), null, [$params['APIKey'], $params['APISecret']]);
        $nameservers = [];
    }

    // Prepare JavaScript for nameservers
    $js_code = ' 
        <script>
        jQuery(document).ready(function() {';
    
    // Populate nameserver fields
    for ($i = 1; $i <= 5; $i++) {
        $ns_value = isset($nameservers['ns' . $i]) ? htmlspecialchars($nameservers['ns' . $i], ENT_QUOTES, 'UTF-8') : '';
        $js_code .= 'jQuery("#ns' . $i . '").val("' . $ns_value . '");';
    }

    // Check if the real-time sync view for other data is enabled
    if (($params['EnableRealtimeSync'] ?? 'off') !== 'on') {
        $js_code .= ' 
        });
        </script>';
        return $js_code;
    }

    // Real-time sync is enabled, so fetch expiry and status
    try {
        $syncData = spaceship_Sync($params);
        if (isset($syncData['error'])) {
            throw new \Exception($syncData['error']);
        }
    } catch (\Exception $e) {
        logModuleCall('spaceship', 'RealtimeSync_Sync', $params['domain'], $e->getMessage(), null, [$params['APIKey'], $params['APISecret']]);
        $syncData = [];
    }

    // Prepare JavaScript for expiry date and status sync
    if (!empty($syncData)) {
        // Expiry Date Sync
        $whmcsExpiry = fromMySQLDate($vars['expirydate']);
        $registrarExpiry = fromMySQLDate($syncData['expirydate']);

        $js_code .= ' 
            var expiryDateInput = jQuery("input[name=\'expirydate\']");
            if (expiryDateInput.length) {
                var registrarExpiry = "' . $registrarExpiry . '";
                if (expiryDateInput.val() !== registrarExpiry) {
                    expiryDateInput.css("border", "2px solid #ff6600");
                    expiryDateInput.after("<div class=\"text-warning\" style=\"font-size:12px; margin-top:5px;\"><b>Live Registrar Value:</b> " + registrarExpiry + "</div>");
                }
            }
        ';

        // Status Sync
        $whmcsStatus = $vars['status'];
        $registrarActive = $syncData['active'] ? 'Active' : 'Expired';
        if ($syncData['transferredAway']) {
            $registrarActive = 'Transferred Away';
        }

        $js_code .= ' 
            var statusSelect = jQuery("select[name=\'status\']");
            if (statusSelect.length) {
                var registrarStatus = "' . $registrarActive . '";
                if (statusSelect.val() !== registrarStatus) {
                    statusSelect.css("border", "2px solid #ff6600");
                    statusSelect.after("<div class=\"text-warning\" style=\"font-size:12px; margin-top:5px;\"><b>Live Registrar Status:</b> " + registrarStatus + "</div>");
                }
            }
        ';
    }

    $js_code .= ' 
        });
        </script>';

    return $js_code;
});