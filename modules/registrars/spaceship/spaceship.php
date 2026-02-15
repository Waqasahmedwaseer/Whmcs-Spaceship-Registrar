<?php
/**
 * Spaceship.com Domain Registrar Module for WHMCS
 *
 * This module integrates Spaceship.com's domain registration API with WHMCS,
 * providing full domain management capabilities including registration,
 * transfers, renewals, DNS management, and more.
 *
 * @copyright 2026
 * @license MIT
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Module\Registrar\Spaceship\ApiClient;
use WHMCS\Domain\Registrar\Domain;
use WHMCS\Carbon;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Results\ResultsList as TldResultsList;

// Require the API client
require_once __DIR__ . '/lib/ApiClient.php';

/**
 * Define module related metadata
 *
 * @return array
 */
function spaceship_MetaData()
{
    return [
        'DisplayName' => 'Spaceship.com Domain Registrar',
        'APIVersion' => '1.1',
    ];
}

/**
 * Define registrar configuration options.
 *
 * @return array
 */
function spaceship_getConfigArray()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Spaceship.com Domain Registrar',
        ],
        'APIKey' => [
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Spaceship API Key from the API Manager',
        ],
        'APISecret' => [
            'FriendlyName' => 'API Secret',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Spaceship API Secret',
        ],
        'TestMode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Enable this to use the sandbox/test API endpoint',
        ],
        'DefaultPrivacyProtection' => [
            'FriendlyName' => 'Default Privacy Protection',
            'Type' => 'dropdown',
            'Options' => [
                'high' => 'High (Full Privacy)',
                'low' => 'Low (Partial Privacy)',
                'off' => 'Off (No Privacy)',
            ],
            'Default' => 'high',
            'Description' => 'Default privacy protection level for new registrations',
        ],
        'DefaultAutoRenew' => [
            'FriendlyName' => 'Default Auto Renew',
            'Type' => 'yesno',
            'Description' => 'Enable auto-renewal by default for new registrations',
        ],
        'DebugMode' => [
            'FriendlyName' => 'Debug Mode',
            'Type' => 'yesno',
            'Description' => 'Enable detailed logging for troubleshooting',
        ],
        'EnableRealtimeSync' => [
            'FriendlyName' => 'Enable Realtime Sync View',
            'Type' => 'yesno',
            'Description' => 'When viewing a domain in the admin area, this will display the current live data from the registrar. (Does not automatically save)',
        ],
    ];
}

/**
 * Register a domain.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_RegisterDomain($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $registrationPeriod = $params['regperiod'];

    // Privacy protection settings
    $enableIdProtection = (bool) $params['idprotection'];
    $privacyLevel = $enableIdProtection ? ($params['DefaultPrivacyProtection'] ?: 'high') : 'public';

    // Auto-renew setting
    $autoRenew = (bool) $params['DefaultAutoRenew'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        // First, create contact for registrant
        $registrantContactId = spaceship_createContact($api, $params, 'registrant');
        $adminContactId = spaceship_createContact($api, $params, 'admin');
        $techContactId = spaceship_createContact($api, $params, 'tech');
        $billingContactId = spaceship_createContact($api, $params, 'billing');

        // Register the domain
        $registrationPayload = [
            'autoRenew' => $autoRenew,
            'years' => (int) $registrationPeriod,
            'privacyProtection' => [
                'level' => $privacyLevel,
                'userConsent' => true,
            ],
            'contacts' => [
                'registrant' => $registrantContactId,
                'admin' => $adminContactId,
                'tech' => $techContactId,
                'billing' => $billingContactId,
            ],
        ];

        // Set nameservers if provided directly in the registration payload
        $nameservers = array_filter([
            $params['ns1'],
            $params['ns2'],
            $params['ns3'],
            $params['ns4'],
            $params['ns5'],
        ]);

        if (!empty($nameservers)) {
            $registrationPayload['nameServers'] = [
                'provider' => 'custom',
                'hosts' => $nameservers,
            ];
        }

        $response = $api->post("/v1/domains/{$domain}", $registrationPayload);

        // Domain registration is async, check for operation ID
        $operationId = $api->getAsyncOperationId();
        if ($operationId) {
            // Poll for completion or store for later sync
            $operationResult = $api->waitForAsyncOperation($operationId);
            if ($operationResult['status'] === 'failed') {
                throw new \Exception('Domain registration failed: ' . ($operationResult['details']['message'] ?? 'Unknown error'));
            }
        }

        return ['success' => true];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Initiate domain transfer.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_TransferDomain($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $eppCode = $params['eppcode'];

    // Privacy protection settings
    $enableIdProtection = (bool) $params['idprotection'];
    $privacyLevel = $enableIdProtection ? ($params['DefaultPrivacyProtection'] ?: 'high') : 'public';

    // Auto-renew setting
    $autoRenew = (bool) $params['DefaultAutoRenew'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        // Create contacts for transfer
        $registrantContactId = spaceship_createContact($api, $params, 'registrant');
        $adminContactId = spaceship_createContact($api, $params, 'admin');
        $techContactId = spaceship_createContact($api, $params, 'tech');
        $billingContactId = spaceship_createContact($api, $params, 'billing');

        // Initiate domain transfer
        $response = $api->post("/v1/domains/{$domain}/transfer", [
            'autoRenew' => $autoRenew,
            'privacyProtection' => [
                'level' => $privacyLevel,
                'userConsent' => true,
            ],
            'contacts' => [
                'registrant' => $registrantContactId,
                'admin' => $adminContactId,
                'tech' => $techContactId,
                'billing' => $billingContactId,
            ],
            'authCode' => $eppCode,
        ]);

        $operationId = $api->getAsyncOperationId();
        if ($operationId) {
            // Transfer is async, store operation ID for later sync if needed
            logModuleCall('Spaceship', 'TransferDomain', $domain, "Async operation started: {$operationId}");
        }

        return ['success' => true];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Renew a domain.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_RenewDomain($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $registrationPeriod = $params['regperiod'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        // Fetch current expiration date as it's required for renewal
        $domainInfo = $api->get("/v1/domains/{$domain}");
        $currentExpirationDate = $domainInfo['expirationDate'] ?? '';

        if (!$currentExpirationDate) {
            throw new \Exception('Could not retrieve current expiration date for domain renewal.');
        }

        $response = $api->post("/v1/domains/{$domain}/renew", [
            'years' => (int) $registrationPeriod,
            'currentExpirationDate' => $currentExpirationDate,
        ]);

        $operationId = $api->getAsyncOperationId();
        if ($operationId) {
            $operationResult = $api->waitForAsyncOperation($operationId);
            if ($operationResult['status'] === 'failed') {
                throw new \Exception('Domain renewal failed: ' . ($operationResult['details']['message'] ?? 'Unknown error'));
            }
        }

        return ['success' => true];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Fetch current nameservers.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_GetNameservers($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);
        $response = $api->get("/v1/domains/{$domain}");

        $nameservers = [];
        // API returns lowercase 'nameservers' key
        $nsData = $response['nameservers'] ?? $response['nameServers'] ?? [];
        if (isset($nsData['hosts']) && is_array($nsData['hosts'])) {
            foreach ($nsData['hosts'] as $index => $ns) {
                $nameservers['ns' . ($index + 1)] = $ns;
            }
        }

        return $nameservers;

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save nameserver changes.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_SaveNameservers($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    $nameservers = array_filter([
        $params['ns1'],
        $params['ns2'],
        $params['ns3'],
        $params['ns4'],
        $params['ns5'],
    ]);

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        $api->put("/v1/domains/{$domain}/nameservers", [
            'provider' => 'custom',
            'hosts' => array_values($nameservers),
        ]);

        return ['success' => true];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get the current WHOIS Contact Information.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_GetContactDetails($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);
        $domainInfo = $api->get("/v1/domains/{$domain}");

        $contacts = [];
        $contactTypes = ['registrant', 'admin', 'tech', 'billing'];

        foreach ($contactTypes as $type) {
            $contactId = $domainInfo['contacts'][$type] ?? null;
            if ($contactId) {
                $contactDetails = $api->get("/v1/contacts/{$contactId}");
                $contacts[ucfirst($type)] = [
                    'First Name' => $contactDetails['firstName'] ?? '',
                    'Last Name' => $contactDetails['lastName'] ?? '',
                    'Company Name' => $contactDetails['organization'] ?? '',
                    'Email Address' => $contactDetails['email'] ?? '',
                    'Address 1' => $contactDetails['address1'] ?? '',
                    'Address 2' => $contactDetails['address2'] ?? '',
                    'City' => $contactDetails['city'] ?? '',
                    'State' => $contactDetails['stateProvince'] ?? '',
                    'Postcode' => $contactDetails['postalCode'] ?? '',
                    'Country' => $contactDetails['country'] ?? '',
                    'Phone Number' => $contactDetails['phone'] ?? '',
                    'Fax Number' => $contactDetails['fax'] ?? '',
                ];
            }
        }

        return $contacts;

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update the WHOIS Contact Information for a given domain.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_SaveContactDetails($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    $contactDetails = $params['contactdetails'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        $contactIds = [];
        $contactTypes = ['Registrant', 'Admin', 'Tech', 'Billing'];

        foreach ($contactTypes as $type) {
            if (isset($contactDetails[$type])) {
                $contact = $contactDetails[$type];
                $data = [
                    'firstName' => $contact['First Name'] ?? '',
                    'lastName' => $contact['Last Name'] ?? '',
                    'organization' => $contact['Company Name'] ?? '',
                    'email' => $contact['Email Address'] ?? '',
                    'address1' => $contact['Address 1'] ?? '',
                    'address2' => $contact['Address 2'] ?? '',
                    'city' => $contact['City'] ?? '',
                    'stateProvince' => $contact['State'] ?? '',
                    'postalCode' => $contact['Postcode'] ?? '',
                    'country' => $contact['Country'] ?? '',
                    'phone' => $contact['Phone Number'] ?? '',
                    'fax' => $contact['Fax Number'] ?? '',
                ];
                
                $formattedData = spaceship_formatContactPayload($data);
                $response = $api->put('/v1/contacts', $formattedData);
                $contactIds[strtolower($type)] = $response['contactId'] ?? '';
            }
        }

        // Update domain contacts
        $api->put("/v1/domains/{$domain}/contacts", $contactIds);

        return ['success' => true];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Check Domain Availability.
 *
 * @param array $params common module parameters
 * @return \WHMCS\Domains\DomainLookup\ResultsList
 */
function spaceship_CheckAvailability($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $searchTerm = $params['searchTerm'];
    $punyCodeSearchTerm = $params['punyCodeSearchTerm'];
    $tldsToInclude = $params['tldsToInclude'];
    $premiumEnabled = (bool) $params['premiumEnabled'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        $domains = [];
        foreach ($tldsToInclude as $tld) {
            $domains[] = $punyCodeSearchTerm . '.' . $tld;
        }

        $response = $api->post('/v1/domains/available', [
            'domains' => $domains,
        ]);

        $results = new ResultsList();

        foreach ($response['items'] ?? [] as $domainResult) {
            $domainName = $domainResult['domain'] ?? '';
            $parts = explode('.', $domainName, 2);
            $sld = $parts[0] ?? '';
            $tld = $parts[1] ?? '';

            $searchResult = new SearchResult($sld, $tld);

            $availability = $domainResult['availability'] ?? '';
            switch ($availability) {
                case 'available':
                    $status = SearchResult::STATUS_NOT_REGISTERED;
                    break;
                case 'unavailable':
                case 'registered':
                    $status = SearchResult::STATUS_REGISTERED;
                    break;
                case 'reserved':
                    $status = SearchResult::STATUS_RESERVED;
                    break;
                default:
                    $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
            }
            $searchResult->setStatus($status);

            // Handle premium domains
            if ($premiumEnabled && isset($domainResult['pricing'])) {
                $pricing = $domainResult['pricing'];
                if (isset($pricing['premium']) && $pricing['premium']) {
                    $searchResult->setPremiumDomain(true);
                    $searchResult->setPremiumCostPricing([
                        'register' => $pricing['registerPrice'] ?? 0,
                        'renew' => $pricing['renewPrice'] ?? 0,
                        'CurrencyCode' => $pricing['currency'] ?? 'USD',
                    ]);
                }
            }

            $results->append($searchResult);
        }

        return $results;

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get registrar lock status.
 *
 * @param array $params common module parameters
 * @return string|array Lock status or error message
 */
function spaceship_GetRegistrarLock($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);
        $response = $api->get("/v1/domains/{$domain}");

        $locks = $response['eppStatuses'] ?? [];
        $isLocked = in_array('clientTransferProhibited', $locks);

        return $isLocked ? 'locked' : 'unlocked';

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Set registrar lock status.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_SaveRegistrarLock($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $lockStatus = $params['lockenabled'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        $api->put("/v1/domains/{$domain}/transfer/lock", [
            'isLocked' => ($lockStatus === 'locked'),
        ]);

        return ['success' => 'success'];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get DNS Records for DNS Host Record Management.
 *
 * @param array $params common module parameters
 * @return array DNS Host Records
 */
function spaceship_GetDNS($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);
        $hostRecords = [];
        $skip = 0;
        $take = 500; // Max items per page as per API spec

        do {
            $response = $api->get("/v1/dns/records/{$domain}?take={$take}&skip={$skip}");
            $items = $response['items'] ?? [];
            
            foreach ($items as $record) {
                $hostRecord = [
                    'hostname' => $record['name'] ?? '@',
                    'type' => $record['type'] ?? 'A',
                    'address' => '',
                    'priority' => '',
                ];

                // Map different record types
                switch ($record['type']) {
                    case 'A':
                    case 'AAAA':
                        $hostRecord['address'] = $record['address'] ?? '';
                        break;
                    case 'CNAME':
                        $hostRecord['address'] = $record['cname'] ?? '';
                        break;
                    case 'MX':
                        $hostRecord['address'] = $record['exchange'] ?? '';
                        $hostRecord['priority'] = $record['preference'] ?? '';
                        break;
                    case 'TXT':
                        $hostRecord['address'] = $record['value'] ?? '';
                        break;
                    case 'NS':
                        $hostRecord['address'] = $record['nameserver'] ?? '';
                        break;
                    case 'SRV':
                        $target = $record['target'] ?? '';
                        $weight = $record['weight'] ?? 0;
                        $port = $record['port'] ?? 0;
                        $hostRecord['address'] = "{$weight} {$port} {$target}";
                        $hostRecord['priority'] = $record['priority'] ?? '';
                        
                        $service = $record['service'] ?? '';
                        $protocol = $record['protocol'] ?? '';
                        $name = $record['name'] ?? '@';
                        
                        if ($service && $protocol) {
                            $hostRecord['hostname'] = ($name === '@') ? "_{$service}._{$protocol}" : "_{$service}._{$protocol}.{$name}";
                        }
                        break;
                }

                $hostRecords[] = $hostRecord;
            }
            
            $skip += $take;
            
        } while (count($items) == $take);

        return $hostRecords;

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update DNS Host Records.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_SaveDNS($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $dnsrecords = $params['dnsrecords'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        $items = [];
        foreach ($dnsrecords as $record) {
            if (empty($record['hostname']) && empty($record['address'])) {
                continue;
            }

            $item = [
                'name' => $record['hostname'] ?: '@',
                'type' => $record['type'] ?? 'A',
                'ttl' => 3600,
            ];

            switch ($record['type']) {
                case 'A':
                case 'AAAA':
                    $item['address'] = $record['address'];
                    break;
                case 'CNAME':
                    $item['cname'] = $record['address'];
                    break;
                case 'MX':
                    $item['exchange'] = $record['address'];
                    $item['preference'] = (int) ($record['priority'] ?? 10);
                    break;
                case 'TXT':
                    $item['value'] = $record['address'];
                    break;
                case 'NS':
                    $item['nameserver'] = $record['address'];
                    break;
                case 'SRV':
                    // WHMCS hostname for SRV is usually _service._protocol.name or _service._protocol
                    $hostnameParts = explode('.', $item['name']);
                    if (count($hostnameParts) >= 2 && strpos($hostnameParts[0], '_') === 0 && strpos($hostnameParts[1], '_') === 0) {
                        $item['service'] = $hostnameParts[0];
                        $item['protocol'] = $hostnameParts[1];
                        unset($hostnameParts[0], $hostnameParts[1]);
                        $item['name'] = empty($hostnameParts) ? '@' : implode('.', $hostnameParts);
                    } else {
                        // Fallback if format doesn't match
                        $item['service'] = '_unknown';
                        $item['protocol'] = '_tcp';
                    }

                    // WHMCS address for SRV is usually "weight port target"
                    $addressParts = explode(' ', $record['address']);
                    if (count($addressParts) >= 3) {
                        $item['weight'] = (int) $addressParts[0];
                        $item['port'] = (int) $addressParts[1];
                        unset($addressParts[0], $addressParts[1]);
                        $item['target'] = implode(' ', $addressParts);
                    } else {
                        // Fallback if format doesn't match
                        $item['weight'] = 0;
                        $item['port'] = 0;
                        $item['target'] = $record['address'];
                    }

                    $item['priority'] = (int) ($record['priority'] ?? 0);
                    break;
            }

            $items[] = $item;
        }

        if (!empty($items)) {
            $api->put("/v1/dns/records/{$domain}", [
                'force' => true,
                'items' => $items,
            ]);
        }

        return ['success' => 'success'];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Enable/Disable ID Protection (Privacy Protection).
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_IDProtectToggle($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $protectEnable = (bool) $params['protectenable'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        $privacyLevel = $protectEnable ? 'high' : 'public';

        $api->put("/v1/domains/{$domain}/privacy/preference", [
            'privacyLevel' => $privacyLevel,
            'userConsent' => true,
        ]);

        return ['success' => 'success'];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Request EPP Code.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_GetEPPCode($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);
        
        // First, try to get the existing auth code.
        $response = $api->get("/v1/domains/{$domain}/transfer/auth-code");
        $eppCode = $response['authCode'] ?? '';

        if ($eppCode) {
            return ['eppcode' => $eppCode];
        }
    } catch (\Exception $e) {
        // Log the error from the GET attempt for debugging.
        logModuleCall('spaceship', __FUNCTION__ . ' (GET)', "Attempting to get EPP code failed. Error: {$e->getMessage()}. Proceeding to generate a new one.", $e);
    }

    // If we're here, either the GET failed or it returned an empty code.
    // Let's try to generate a new one.
    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);
        $api->post("/v1/domains/{$domain}/transfer/auth-code", []);

        // The POST call is async (202). We return a success message to WHMCS
        // indicating the request was submitted.
        return ['success' => 'EPP Code generation has been requested. Please try again in a few moments.'];

    } catch (\Exception $e) {
        // If generating the code also fails, then we return the error.
        logModuleCall('spaceship', __FUNCTION__ . ' (POST)', "Attempting to generate EPP code also failed. Error: {$e->getMessage()}", $e);
        return ['error' => 'Failed to generate EPP code: ' . $e->getMessage()];
    }
}

/**
 * Register a Nameserver (Child/Glue record).
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_RegisterNameserver($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $nameserver = $params['nameserver'];
    $ipAddress = $params['ipaddress'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        // Extract host from full nameserver
        $host = str_replace('.' . $domain, '', $nameserver);

        $api->post("/v1/domains/{$domain}/personal-nameservers", [
            'host' => $host,
            'ipAddresses' => [$ipAddress],
        ]);

        return ['success' => 'success'];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Modify a Nameserver (Child/Glue record).
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_ModifyNameserver($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $nameserver = $params['nameserver'];
    $newIpAddress = $params['newipaddress'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        // Extract host from full nameserver
        $host = str_replace('.' . $domain, '', $nameserver);

        $api->put("/v1/domains/{$domain}/personal-nameservers/{$host}", [
            'host' => $host,
            'ipAddresses' => [$newIpAddress],
        ]);

        return ['success' => 'success'];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete a Nameserver (Child/Glue record).
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_DeleteNameserver($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $nameserver = $params['nameserver'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        // Extract host from full nameserver
        $host = str_replace('.' . $domain, '', $nameserver);

        $api->delete("/v1/domains/{$domain}/personal-nameservers/{$host}");

        return ['success' => 'success'];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Sync Domain Status & Expiration Date.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_Sync($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);
        $response = $api->get("/v1/domains/{$domain}");

        $expiryDate = $response['expirationDate'] ?? '';
        $status = $response['lifecycleStatus'] ?? '';

        // Determine if domain is active
        $isActive = ($status === 'registered');

        // Check for transfer away or deletion if applicable
        // Note: Spaceship API doesn't explicitly have 'transferredAway' in lifecycleStatus enum, 
        // but if it's not present or has different status, handle accordingly.
        
        // Format expiry date
        if ($expiryDate) {
            $expiryDate = date('Y-m-d', strtotime($expiryDate));
        }

        return [
            'expirydate' => $expiryDate,
            'active' => $isActive,
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Incoming Domain Transfer Sync.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_TransferSync($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);
        $response = $api->get("/v1/domains/{$domain}/transfer");

        $status = $response['status'] ?? '';

        switch ($status) {
            case 'completed':
                $domainInfo = $api->get("/v1/domains/{$domain}");
                $expiryDate = $domainInfo['expirationDate'] ?? '';
                if ($expiryDate) {
                    $expiryDate = date('Y-m-d', strtotime($expiryDate));
                }
                return [
                    'completed' => true,
                    'expirydate' => $expiryDate,
                ];

            case 'cancelled':
            case 'failed':
            case 'rejected':
                return [
                    'failed' => true,
                    'reason' => $response['failureReason'] ?? 'Transfer failed or was rejected',
                ];

            default:
                // Transfer still in progress
                return [];
        }

    } catch (\Exception $e) {
        // Domain not found might mean transfer not started yet
        return [];
    }
}

/**
 * Update domain auto-renewal setting.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_SaveAutorenew($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;
    $autoRenew = (bool) $params['autorenew'];

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        $api->put("/v1/domains/{$domain}/autorenew", [
            'isEnabled' => $autoRenew,
        ]);

        return ['success' => 'success'];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Delete Domain (Request deletion).
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_RequestDelete($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        $api->delete("/v1/domains/{$domain}");

        return ['success' => 'success'];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Restore Domain from Redemption.
 *
 * @param array $params common module parameters
 * @return array
 */
function spaceship_RestoreDomain($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);

        $response = $api->post("/v1/domains/{$domain}/restore", []);

        $operationId = $api->getAsyncOperationId();
        if ($operationId) {
            $operationResult = $api->waitForAsyncOperation($operationId);
            if ($operationResult['status'] === 'failed') {
                throw new \Exception('Domain restore failed: ' . ($operationResult['details']['message'] ?? 'Unknown error'));
            }
        }

        return ['success' => 'success'];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * Client Area Custom Button Array.
 *
 * @return array
 */
function spaceship_ClientAreaCustomButtonArray()
{
    return [
        'Manage DNS' => 'manageDNS',
    ];
}

/**
 * Client Area Allowed Functions.
 *
 * @return array
 */
function spaceship_ClientAreaAllowedFunctions()
{
    return [
        'Manage DNS' => 'manageDNS',
    ];
}

/**
 * Helper function to create a contact in Spaceship.
 *
 * @param ApiClient $api
 * @param array $params
 * @param string $type (registrant, admin, tech, billing)
 * @return string Contact ID
 */
function spaceship_createContact($api, $params, $type)
{
    // Map WHMCS params to contact type prefixes
    $prefix = ($type === 'registrant') ? '' : $type;

    $firstName = $params[$prefix . 'firstname'] ?? $params['firstname'];
    $lastName = $params[$prefix . 'lastname'] ?? $params['lastname'];
    $companyName = $params[$prefix . 'companyname'] ?? $params['companyname'];
    $email = $params[$prefix . 'email'] ?? $params['email'];
    $address1 = $params[$prefix . 'address1'] ?? $params['address1'];
    $address2 = $params[$prefix . 'address2'] ?? $params['address2'];
    $city = $params[$prefix . 'city'] ?? $params['city'];
    $state = $params[$prefix . 'state'] ?? $params['state'];
    $postcode = $params[$prefix . 'postcode'] ?? $params['postcode'];
    $country = $params[$prefix . 'country'] ?? $params['countrycode'];
    $phone = $params[$prefix . 'fullphonenumber'] ?? $params['fullphonenumber'];

    $data = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'organization' => $companyName,
        'email' => $email,
        'address1' => $address1,
        'address2' => $address2,
        'city' => $city,
        'stateProvince' => $state,
        'postalCode' => $postcode,
        'country' => $country,
        'phone' => $phone,
    ];

    $formattedData = spaceship_formatContactPayload($data);
    $response = $api->put('/v1/contacts', $formattedData);

    return $response['contactId'] ?? '';
}

/**
 * Format and sanitize contact details for Spaceship API.
 * Ensures required fields are present and optional fields are omitted if empty.
 * Also handles phone number formatting.
 *
 * @param array $data
 * @return array
 */
function spaceship_formatContactPayload(array $data)
{
    // Required fields (cannot be omitted)
    $required = [
        'firstName',
        'lastName',
        'email',
        'address1',
        'city',
        'country',
        'phone'
    ];

    $payload = [];
    foreach ($data as $key => $value) {
        $value = trim((string)$value);
        
        if (in_array($key, $required)) {
            $payload[$key] = $value ?: ''; // Keep empty string if required (api will return error)
        } elseif ($value !== '') {
            $payload[$key] = $value;
        }
    }

    // Phone formatting: +CC.NNNNNNNNNN (Pattern: ^\+\d{1,3}\.\d{4,}$)
    $phoneFields = ['phone', 'fax'];
    foreach ($phoneFields as $field) {
        if (!isset($payload[$field]) || empty($payload[$field])) {
            continue;
        }

        $phone = str_replace([' ', '-', '(', ')'], '', $payload[$field]);
        
        // Ensure starting with +
        if (strpos($phone, '00') === 0) {
            $phone = '+' . substr($phone, 2);
        } elseif (strpos($phone, '+') !== 0) {
            $phone = '+' . $phone;
        }

        // Check if dot is present, if not try to insert after country code
        if (strpos($phone, '.') === false) {
            // Very basic heuristic for country code length (1-3 digits)
            // If it's a known format like +923404088841 -> +92.3404088841
            // We'll try to match +[1-9][0-9]{0,2}[digit]{4,}
            if (preg_match('/^\+([1-9]\d{0,2})(\d{4,})$/', $phone, $matches)) {
                $phone = '+' . $matches[1] . '.' . $matches[2];
            }
        }
        
        $payload[$field] = $phone;
    }

    // Truncate lengths to API limits
    $limits = [
        'firstName' => 125,
        'lastName' => 125,
        'organization' => 255,
        'email' => 255,
        'address1' => 255,
        'address2' => 255,
        'city' => 255,
        'stateProvince' => 255,
        'postalCode' => 16, // API maxLength is 16
    ];

    foreach ($limits as $field => $limit) {
        if (isset($payload[$field])) {
            $payload[$field] = substr($payload[$field], 0, $limit);
        }
    }

    // Organization must be at least 1 char or omitted (handled by loop above)
    
    return $payload;
}

/**
 * Get Domain Information.
 *
 * This is the WHMCS recommended approach (since WHMCS 7.6) for populating
 * domain details in the admin area. It replaces individual GetNameservers
 * and GetRegistrarLock calls with a single consolidated call.
 *
 * @param array $params common module parameters
 * @return \WHMCS\Domain\Registrar\Domain|array
 */
function spaceship_GetDomainInformation($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    $sld = $params['sld'];
    $tld = $params['tld'];
    $domain = $sld . '.' . $tld;

    try {
        $api = new ApiClient($apiKey, $apiSecret, $testMode);
        $response = $api->get("/v1/domains/{$domain}");

        // Parse nameservers - API returns lowercase 'nameservers'
        $nsData = $response['nameservers'] ?? $response['nameServers'] ?? [];
        $nameservers = [];
        if (isset($nsData['hosts']) && is_array($nsData['hosts'])) {
            foreach ($nsData['hosts'] as $index => $ns) {
                $nameservers['ns' . ($index + 1)] = $ns;
                if ($index >= 4) break; // WHMCS supports max 5
            }
        }

        // Parse expiry date
        $expiryDate = null;
        if (!empty($response['expirationDate'])) {
            $expiryDate = Carbon::parse($response['expirationDate']);
        }

        // Determine registration status
        $lifecycleStatus = $response['lifecycleStatus'] ?? '';
        switch ($lifecycleStatus) {
            case 'registered':
            case 'grace1':
                $registrationStatus = Domain::STATUS_ACTIVE;
                break;
            case 'grace2':
            case 'redemption':
                $registrationStatus = Domain::STATUS_EXPIRED;
                break;
            default:
                $registrationStatus = Domain::STATUS_INACTIVE;
                break;
        }

        // Determine transfer lock status from EPP statuses
        $eppStatuses = $response['eppStatuses'] ?? [];
        $transferLock = in_array('clientTransferProhibited', $eppStatuses);

        // Determine ID protection status
        $privacyLevel = $response['privacyProtection']['level'] ?? 'public';
        $idProtection = ($privacyLevel === 'high');

        // Check if domain is restorable
        $restorable = ($lifecycleStatus === 'redemption');

        $domainObj = (new Domain)
            ->setDomain($domain)
            ->setNameservers($nameservers)
            ->setRegistrationStatus($registrationStatus)
            ->setTransferLock($transferLock)
            ->setTransferLockExpiryDate(null)
            ->setIdProtectionStatus($idProtection)
            ->setDnsManagementStatus(true)
            ->setEmailForwardingStatus(false)
            ->setRestorable($restorable);

        if ($expiryDate) {
            $domainObj->setExpiryDate($expiryDate);
        }

        return $domainObj;

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get TLD Pricing for the Registrar TLD & Pricing Sync Utility.
 *
 * Since the Spaceship API does not provide a dedicated TLD pricing endpoint,
 * this function provides commonly available TLDs with standard pricing.
 * Pricing can be updated as needed to reflect current Spaceship rates.
 *
 * @param array $params common module parameters
 * @return \WHMCS\Results\ResultsList|array
 */
function spaceship_GetTldPricing($params)
{
    $apiKey = $params['APIKey'];
    $apiSecret = $params['APISecret'];
    $testMode = $params['TestMode'];

    try {
        // Spaceship API does not have a TLD pricing endpoint.
        // We provide common TLD pricing based on Spaceship's known rates.
        // These should be updated periodically to match current pricing.
        $tldPricing = [
            // Generic TLDs
            '.com'    => ['register' => 9.28,  'renew' => 11.28, 'transfer' => 9.28],
            '.net'    => ['register' => 11.28, 'renew' => 13.28, 'transfer' => 11.28],
            '.org'    => ['register' => 9.28,  'renew' => 12.28, 'transfer' => 9.28],
            '.info'   => ['register' => 4.18,  'renew' => 18.28, 'transfer' => 4.18],
            '.biz'    => ['register' => 4.98,  'renew' => 16.98, 'transfer' => 4.98],
            '.xyz'    => ['register' => 2.98,  'renew' => 11.28, 'transfer' => 2.98],
            '.online' => ['register' => 2.98,  'renew' => 30.98, 'transfer' => 2.98],
            '.store'  => ['register' => 2.98,  'renew' => 30.98, 'transfer' => 2.98],
            '.site'   => ['register' => 2.98,  'renew' => 30.98, 'transfer' => 2.98],
            '.tech'   => ['register' => 2.98,  'renew' => 40.98, 'transfer' => 2.98],
            '.me'     => ['register' => 4.98,  'renew' => 18.98, 'transfer' => 4.98],
            '.io'     => ['register' => 32.98, 'renew' => 42.98, 'transfer' => 32.98],
            '.co'     => ['register' => 11.98, 'renew' => 25.98, 'transfer' => 11.98],
            '.dev'    => ['register' => 12.98, 'renew' => 14.98, 'transfer' => 12.98],
            '.app'    => ['register' => 14.98, 'renew' => 18.98, 'transfer' => 14.98],
            '.ai'     => ['register' => 69.98, 'renew' => 89.98, 'transfer' => 69.98],
            '.cloud'  => ['register' => 4.98,  'renew' => 18.98, 'transfer' => 4.98],
            '.pro'    => ['register' => 4.98,  'renew' => 18.98, 'transfer' => 4.98],
            '.us'     => ['register' => 4.98,  'renew' => 9.98,  'transfer' => 4.98],
            '.live'   => ['register' => 4.98,  'renew' => 20.98, 'transfer' => 4.98],
            '.world'  => ['register' => 4.98,  'renew' => 25.98, 'transfer' => 4.98],
            '.club'   => ['register' => 4.98,  'renew' => 14.98, 'transfer' => 4.98],
            '.space'  => ['register' => 2.98,  'renew' => 20.98, 'transfer' => 2.98],
            '.shop'   => ['register' => 2.98,  'renew' => 30.98, 'transfer' => 2.98],
            '.fun'    => ['register' => 2.98,  'renew' => 20.98, 'transfer' => 2.98],
            '.icu'    => ['register' => 2.98,  'renew' => 8.98,  'transfer' => 2.98],
            '.top'    => ['register' => 2.98,  'renew' => 8.98,  'transfer' => 2.98],
            // Country Code TLDs
            '.uk'     => ['register' => 7.98,  'renew' => 8.98,  'transfer' => 7.98],
            '.ca'     => ['register' => 10.98, 'renew' => 14.98, 'transfer' => 10.98],
            '.de'     => ['register' => 7.98,  'renew' => 7.98,  'transfer' => 7.98],
            '.eu'     => ['register' => 4.98,  'renew' => 8.98,  'transfer' => 4.98],
            '.nl'     => ['register' => 7.98,  'renew' => 8.98,  'transfer' => 7.98],
            '.in'     => ['register' => 8.98,  'renew' => 10.98, 'transfer' => 8.98],
        ];

        $results = new TldResultsList();

        foreach ($tldPricing as $extension => $pricing) {
            $item = (new ImportItem)
                ->setExtension($extension)
                ->setMinYears(1)
                ->setMaxYears(10)
                ->setRegisterPrice($pricing['register'])
                ->setRenewPrice($pricing['renew'])
                ->setTransferPrice($pricing['transfer'])
                ->setCurrency('USD')
                ->setEppRequired(true);

            $results[] = $item;
        }

        return $results;

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Admin Area Custom Button Array.
 *
 * @return array
 */
function spaceship_AdminCustomButtonArray()
{
    return [
        'Sync Domain' => 'Sync',
        'Restore Domain' => 'RestoreDomain',
    ];
}

/**
 * Client Area Output.
 *
 * @param array $params common module parameters
 * @return string HTML Output
 */
function spaceship_ClientArea($params)
{
    $output = '
        <div class="alert alert-info">
            <strong>Spaceship.com Domain Management</strong><br>
            Your domain is registered with Spaceship.com. Use the controls above to manage your domain settings.
        </div>
    ';

    return $output;
}
