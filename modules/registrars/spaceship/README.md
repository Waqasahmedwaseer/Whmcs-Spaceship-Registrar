# Spaceship.com WHMCS Domain Registrar Module

A fully-featured WHMCS domain registrar module for [Spaceship.com](https://spaceship.com), providing complete integration with their domain registration API.

**Current Version: 1.2.0**

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
- **TLD Pricing Import & Sync**: Import TLD pricing for 33+ popular extensions via the WHMCS TLD Sync Utility.
- **GetDomainInformation**: Uses the WHMCS-recommended consolidated domain info function for optimal performance.

## Requirements

- WHMCS 7.10 or later (8.x recommended)
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

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a full list of changes.

### Latest — v1.2.0 (2026-02-15)

- **NEW**: `GetDomainInformation` — Consolidated domain data retrieval for better admin area display.
- **NEW**: `GetTldPricing` — TLD pricing import/sync for 33+ TLDs via the WHMCS Registrar TLD Sync Utility.
- **FIX**: Nameservers now correctly display in the WHMCS admin (fixed API key casing issue).
- **FIX**: Resolved "Invalid GetTldPricing Response" error on the TLD Sync/Import page.
- **FIX**: Corrected privacy level values and renewal request parameters for API compliance.
- **FIX**: Improved SRV record handling in DNS management.

---

## Logging

When **Debug Mode** is enabled, all API interactions are logged to the WHMCS Module Log. This is essential for troubleshooting. You can view the log at **System Logs > Module Log**.

## Author

**Waqas Ahmed Waseer**
- GitHub: [@Waqasahmedwaseer](https://github.com/Waqasahmedwaseer)

## License

This module is released under the MIT License.
