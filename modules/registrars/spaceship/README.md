# Spaceship.com WHMCS Domain Registrar Module

A fully-featured WHMCS domain registrar module for [Spaceship.com](https://spaceship.com), providing complete integration with their domain registration API.

## Features

- **Domain Registration & Transfer**: Register new domains and process incoming transfers.
- **Domain Renewal**: Renew domains for 1-10 years.
- **WHOIS Contact Management**: View and update registrant, admin, tech, and billing contacts.
- **Nameserver Management**: View and update domain nameservers.
- **DNS Host Record Management**: Full DNS management (A, AAAA, CNAME, MX, TXT, NS, SRV records).
- **Registrar Lock**: Lock/unlock domains to prevent unauthorized transfers.
- **EPP Code Retrieval**: Request authorization codes for domain transfers out.
- **ID Protection Toggle**: Enable or disable WHOIS privacy.
- **Child Nameserver Management**: Create, modify, and delete glue records (personal nameservers).
- **Domain Sync**: Automatic synchronization of domain status and expiry dates via WHMCS cron.
- **Transfer Sync**: Monitor and sync the status of incoming domain transfers.
- **Domain Deletion & Restoration**: Request domain deletion and restore domains from redemption.
- **Premium Domain Support**: Handles premium domain pricing during availability checks.

## Requirements

- WHMCS 8.x or later
- PHP 7.4 or later
- cURL and JSON PHP extensions
- Spaceship.com API credentials

## Installation

1.  **Upload Files**: Copy the entire `spaceship` directory to `/path/to/whmcs/modules/registrars/`.
2.  **Activate**: Log in to your WHMCS Admin Area, navigate to **System Settings > Domain Registrars**, find "Spaceship.com Domain Registrar" in the list, and click **Activate**.
3.  **Configure**: Enter your Spaceship API Key and Secret, and configure the other module settings as required.

## Configuration

After activating the module, configure the following options:

| Option | Description |
| :--- | :--- |
| **API Key** | Your Spaceship API Key. |
| **API Secret** | Your Spaceship API Secret. |
| **Test Mode** | Check to use the sandbox API endpoint for testing. |
| **Default Privacy Protection**| The default privacy level to apply to new registrations with ID Protection. |
| **Default Auto Renew** | Enable this to have auto-renewal enabled by default for new registrations. |
| **Debug Mode** | Check to log all API requests and responses to the WHMCS Module Log. |
| **Enable Realtime Sync View** | Displays live data from the registrar in the admin area. |

---

## Changelog & Important Notes

### Version 1.1.0 (2026-01-19)

- **FIX**: The `GetDNS` function now correctly handles pagination, ensuring all DNS records are retrieved for domains with more than 100 records. Previously, it was limited to the first 100.
- **FIX**: Corrected a bug in SRV record handling where hostnames were formatted incorrectly (missing `_` prefixes). This ensures proper display and management of SRV records.
- **FIX**: Removed a non-functional "Privacy Settings" button from the client area. ID Protection is managed via the standard domain addons functionality.
- **IMPROVEMENT**: The data payload for creating and updating contacts has been enhanced to better handle empty optional fields, phone number formatting, and field length truncation to prevent common API validation errors.

### Developer Notes

- **EPP Code Generation**: The `GetEPPCode` function includes robust logic to first attempt to fetch an existing code (`GET`) and then, as a fallback, to request the generation of a new code (`POST`). Please be aware that the `POST` endpoint for code generation is **not documented** in the provided API specification. This functionality has been implemented based on assumed API behavior. It is recommended to confirm this endpoint with the API provider.

- **Modernization**: The module currently uses `GetNameservers` and `GetRegistrarLock`. For optimal performance on WHMCS 8.0 and later, these could be consolidated into the single `GetDomainInformation` function in a future update.

---

## Logging

When **Debug Mode** is enabled, all API interactions are logged to the WHMCS Module Log. This is essential for troubleshooting. You can view the log at **System Logs > Module Log**.

## License

This module is released under the MIT License.
