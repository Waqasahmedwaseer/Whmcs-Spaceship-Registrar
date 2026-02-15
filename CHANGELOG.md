# Changelog

All notable changes to the Spaceship.com WHMCS Domain Registrar Module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-02-15

### Added
- **GetDomainInformation**: Implemented the WHMCS-recommended `GetDomainInformation` function (WHMCS 7.6+) which consolidates domain data retrieval into a single API call. This replaces individual `GetNameservers` and `GetRegistrarLock` calls for better performance and ensures nameservers, expiry date, transfer lock, ID protection, and registration status are all correctly populated in the admin area.
- **TLD Pricing Import & Sync**: Implemented `GetTldPricing` function for the WHMCS Registrar TLD & Pricing Sync Utility. Supports 33 popular TLDs (gTLDs and ccTLDs) with registration, renewal, and transfer pricing in USD. The Spaceship API does not have a dedicated TLD pricing endpoint, so pricing is maintained as a configurable list within the module.

### Fixed
- **Nameserver Display**: Fixed nameservers not appearing in the WHMCS admin client profile. The Spaceship API returns the `nameservers` key in lowercase, but the module was checking for camelCase `nameServers`. Now checks both formats for full compatibility.
- **TLD Pricing Error**: Resolved "Invalid GetTldPricing Response from Module" error that occurred when clicking the Spaceship option on the WHMCS TLD Sync/Import page. The previous implementation returned an empty array instead of the required `ResultsList` object.
- **Privacy Level Values**: Corrected privacy level values from `off` to `public` in domain registration and transfer functions, aligning with the Spaceship API specification which only accepts `public` or `high`.
- **Domain Renewal**: Fixed renewal requests by fetching the `currentExpirationDate` from the API before making renewal calls, as required by the Spaceship API specification.
- **Transfer Sync**: Added handling for the `cancelled` transfer status in the TransferSync function.

### Improved
- **SRV Record Handling**: Enhanced DNS record management for SRV records in both `GetDNS` and `SaveDNS` functions, correctly mapping service, protocol, weight, port, and target fields between WHMCS and the Spaceship API.
- **Contact Data Formatting**: Enhanced the `spaceship_formatContactPayload` helper to better handle phone number formatting (E.164 to dot notation), field length truncation, and optional field omission.

## [1.1.0] - 2026-01-19

### Fixed
- **DNS Pagination**: The `GetDNS` function now correctly handles pagination, ensuring all DNS records are retrieved for domains with more than 100 records.
- **SRV Record Hostnames**: Corrected a bug where SRV record hostnames were formatted incorrectly (missing `_` prefixes).
- **Client Area Cleanup**: Removed a non-functional "Privacy Settings" button from the client area. ID Protection is managed via standard domain addons.

### Improved
- **Contact Payload**: Enhanced the data payload for creating and updating contacts to better handle empty optional fields, phone number formatting, and field length truncation.

## [1.0.0] - 2026-01-15

### Added
- Initial release of the Spaceship.com WHMCS Domain Registrar Module.
- Domain Registration with full contact management.
- Domain Transfer with EPP code support.
- Domain Renewal (1-10 years).
- Nameserver Management (view & update).
- Full DNS Record Management (A, AAAA, CNAME, MX, TXT, NS, SRV).
- WHOIS Contact Management (registrant, admin, tech, billing).
- Privacy Protection / ID Protection toggle.
- Registrar Lock (transfer lock/unlock).
- EPP Code Retrieval.
- Child Nameserver / Glue Record management.
- Auto-Renewal Management.
- Domain Sync (status & expiry date synchronization via cron).
- Transfer Sync (incoming transfer monitoring).
- Domain Deletion & Restoration from redemption.
- Premium Domain Support during availability checks.
- Async Operation Handling.
- Domain Availability Checking with WHMCS integration.
- Debug mode with full API request/response logging.
