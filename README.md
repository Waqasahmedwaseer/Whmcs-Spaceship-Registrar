# Spaceship.com WHMCS Registrar Module

Production-ready **WHMCS Domain Registrar Module** for [Spaceship.com](https://spaceship.com), with support for registration, transfers, renewals, DNS management, contacts, registrar lock, EPP, domain sync, and more.

![Spaceship Logo](modules/registrars/spaceship/logo.png)

## What This Module Does

This module connects WHMCS to the Spaceship domain API so domain operations can be managed directly from your WHMCS admin/client workflows.

### Core Capabilities

- Domain registration
- Incoming transfer initiation
- Domain renewals
- Nameserver get/save
- WHOIS contact get/save
- Registrar lock get/save
- DNS records get/save (A, AAAA, CNAME, MX, TXT, NS, SRV)
- EPP/Auth code retrieval
- Child nameserver (glue) register/modify/delete
- ID protection toggle
- Domain sync + transfer sync
- Auto-renew save
- Request delete + restore domain
- Premium pricing lookup (`GetTldPricing`)
- Realtime sync hints in admin (via hook)

## Repository Structure

```text
modules/registrars/spaceship/
├── spaceship.php              # Main registrar module functions
├── hooks.php                  # Admin realtime sync view hook
├── lib/
│   ├── ApiClient.php          # HTTP client + auth + async handling
│   └── TldRequirements.php    # TLD-specific requirement helpers
├── whmcs.json                 # WHMCS module metadata
├── logo.png
└── README.md
```

## Requirements

- WHMCS registrar module environment
- PHP 7.4+
- PHP extensions: `curl`, `json`
- Valid Spaceship API credentials

From `whmcs.json`:
- Minimum WHMCS version: `7.10.0`
- Maximum WHMCS version: `8.99.99`

## Installation

1. Copy `spaceship` directory to your WHMCS registrar modules path:
   - `/path/to/whmcs/modules/registrars/spaceship`
2. In WHMCS Admin:
   - `System Settings` -> `Domain Registrars`
   - Activate **Spaceship.com Domain Registrar**
3. Enter module configuration values (API credentials, mode, defaults).
4. Assign registrar `spaceship` to supported TLDs in WHMCS.
5. Enable domain sync cron in WHMCS if not already enabled.

## Configuration Options

Available options from `spaceship_getConfigArray()`:

| Setting | Type | Purpose |
|---|---|---|
| `APIKey` | text | Spaceship API key |
| `APISecret` | password | Spaceship API secret |
| `TestMode` | yes/no | Toggle sandbox endpoint mode |
| `DefaultPrivacyProtection` | dropdown | Default privacy level (`high`, `low`, `off`) |
| `DefaultAutoRenew` | yes/no | Enable auto-renew by default on new registrations |
| `DebugMode` | yes/no | Enable detailed module logging |
| `EnableRealtimeSync` | yes/no | Show live registrar values in admin domain view |

## WHMCS Registrar Functions Implemented

| Function Group | Implemented |
|---|---|
| Registration | `RegisterDomain` |
| Transfer | `TransferDomain`, `TransferSync` |
| Renewal | `RenewDomain` |
| Nameservers | `GetNameservers`, `SaveNameservers` |
| Contacts | `GetContactDetails`, `SaveContactDetails` |
| Availability | `CheckAvailability` |
| Locking | `GetRegistrarLock`, `SaveRegistrarLock` |
| DNS | `GetDNS`, `SaveDNS` |
| Privacy | `IDProtectToggle` |
| EPP | `GetEPPCode` |
| Child NS | `RegisterNameserver`, `ModifyNameserver`, `DeleteNameserver` |
| Sync | `Sync`, `TransferSync` |
| Auto Renew | `SaveAutorenew` |
| Lifecycle | `RequestDelete`, `RestoreDomain` |
| Pricing | `GetTldPricing` |

## Logging and Troubleshooting

When `DebugMode` is enabled:
- API requests/responses are logged through `logModuleCall`.
- Useful in WHMCS: `System Logs` -> `Module Log`.

If you face issues:
1. Confirm API credentials and test/production mode.
2. Check module log output for endpoint and payload errors.
3. Verify TLD support and required fields.
4. Re-test with a single domain in a staging WHMCS environment.

## Notes

- Some operations are asynchronous; module includes async operation handling in the API client.
- Admin realtime sync highlighting is provided through `hooks.php` for domains using registrar `spaceship`.

## License

MIT License (as declared in module headers and `whmcs.json`).
