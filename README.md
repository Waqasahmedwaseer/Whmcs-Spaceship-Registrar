<p align="center">
  <img src="modules/registrars/spaceship/logo.png" alt="Spaceship WHMCS Registrar Module" width="120" />
</p>

<h1 align="center">Spaceship.com WHMCS Registrar Module</h1>
<h3 align="center">Complete WHMCS Domain Registrar Integration for Spaceship API</h3>

<p align="center">
  <a href="https://github.com/Waqasahmedwaseer/Whmcs-Spaceship-Registrar"><img src="https://github.com/Waqasahmedwaseer/Whmcs-Spaceship-Registrar/blob/main/spaceship%20module.png" alt="Status" /></a>
  <a href="https://www.whmcs.com"><img src="https://img.shields.io/badge/WHMCS-7.10%2B%20to%208.x-blue?style=flat-square" alt="WHMCS" /></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP" /></a>
  <a href="./modules/registrars/spaceship/whmcs.json"><img src="https://img.shields.io/badge/Category-Registrar-orange?style=flat-square" alt="Category" /></a>
  <a href="./modules/registrars/spaceship/whmcs.json"><img src="https://img.shields.io/badge/License-MIT-yellow?style=flat-square" alt="License" /></a>
</p>

<p align="center">
  A production-focused <strong>WHMCS registrar module</strong> for Spaceship.com that supports registration,
  transfers, renewals, DNS, contacts, EPP, lock/unlock, sync workflows, TLD pricing import, and async operation handling.
  <br/><br/>
  <strong>Current Version: 1.2.0</strong>
</p>

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [WHMCS Registrar Functions](#whmcs-registrar-functions)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Repository Structure](#repository-structure)
- [Logging & Debugging](#logging--debugging)
- [Changelog](#changelog)
- [SEO Keywords](#seo-keywords)
- [License](#license)

---

## Overview

This module integrates the **Spaceship domain API** with WHMCS registrar workflows so you can manage domain operations directly from WHMCS admin and client areas.

It is designed for hosting businesses, domain resellers, and WHMCS developers who need reliable registrar automation and clean operational visibility.

---

## Key Features

- Domain registration
- Incoming domain transfer
- Domain renewal
- Nameserver get/save
- WHOIS contact get/save
- Registrar lock get/save
- DNS records get/save (A, AAAA, CNAME, MX, TXT, NS, SRV)
- EPP/Auth code retrieval
- Child nameserver (glue records) create/modify/delete
- ID protection toggle
- Domain sync + transfer sync
- Auto-renew management
- Request delete + restore domain
- Premium TLD pricing support (`GetTldPricing`)
- Async operation handling for registrar actions
- Optional realtime sync hints in admin domain view

---

## WHMCS Registrar Functions

Implemented in `modules/registrars/spaceship/spaceship.php`:

| Group | Functions |
|---|---|
| Registration | `spaceship_RegisterDomain` |
| Transfer | `spaceship_TransferDomain`, `spaceship_TransferSync` |
| Renewal | `spaceship_RenewDomain` |
| Domain Info | `spaceship_GetDomainInformation` |
| Nameservers | `spaceship_GetNameservers`, `spaceship_SaveNameservers` |
| Contacts | `spaceship_GetContactDetails`, `spaceship_SaveContactDetails` |
| Availability | `spaceship_CheckAvailability` |
| Registrar Lock | `spaceship_GetRegistrarLock`, `spaceship_SaveRegistrarLock` |
| DNS | `spaceship_GetDNS`, `spaceship_SaveDNS` |
| ID Protection | `spaceship_IDProtectToggle` |
| EPP | `spaceship_GetEPPCode` |
| Child Nameservers | `spaceship_RegisterNameserver`, `spaceship_ModifyNameserver`, `spaceship_DeleteNameserver` |
| Sync | `spaceship_Sync`, `spaceship_TransferSync` |
| Auto-Renew | `spaceship_SaveAutorenew` |
| Domain Lifecycle | `spaceship_RequestDelete`, `spaceship_RestoreDomain` |
| Pricing | `spaceship_GetTldPricing` |

---

## Requirements

From module metadata and implementation:

- WHMCS: `7.10.0` to `8.x`
- PHP: `7.4+`
- Extensions: `curl`, `json`
- Valid Spaceship API credentials

Metadata source: `modules/registrars/spaceship/whmcs.json`.

---

## Installation

1. Upload module folder:

```bash
# Place the folder exactly here:
/path/to/whmcs/modules/registrars/spaceship
```

2. In WHMCS Admin:
- Go to `System Settings` -> `Domain Registrars`
- Activate `Spaceship.com Domain Registrar`

3. Configure API credentials and defaults.

4. Assign registrar `spaceship` to your TLDs in WHMCS domain pricing settings.

5. Ensure WHMCS cron and domain sync are enabled.

---

## Configuration

Module settings from `spaceship_getConfigArray()`:

| Setting | Type | Description |
|---|---|---|
| `APIKey` | text | Spaceship API key |
| `APISecret` | password | Spaceship API secret |
| `TestMode` | yes/no | Enable test mode endpoint |
| `DefaultPrivacyProtection` | dropdown | Privacy level: `high`, `low`, `off` |
| `DefaultAutoRenew` | yes/no | Enable auto-renew by default |
| `DebugMode` | yes/no | Enable module-level request/response logging |
| `EnableRealtimeSync` | yes/no | Show live registrar values in WHMCS admin domain view |

---

## Repository Structure

```text
modules/registrars/spaceship/
├── spaceship.php              # Main registrar module implementation
├── hooks.php                  # Admin area realtime sync display hook
├── lib/
│   ├── ApiClient.php          # API client, auth headers, async handling
│   └── TldRequirements.php    # TLD-specific requirements logic
├── whmcs.json                 # WHMCS module metadata (v1.2.0)
├── logo.png
└── README.md
```

---

## Logging & Debugging

When `DebugMode` is enabled, module calls are written via `logModuleCall`.

WHMCS path:
- `System Logs` -> `Module Log`

Recommended debugging flow:
1. Verify API key/secret and mode.
2. Reproduce one action (register/transfer/renew).
3. Inspect request and response payloads in Module Log.
4. Confirm TLD eligibility and contact payload requirements.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a complete version history.

### v1.2.0 (2026-02-15)
- **NEW**: `GetDomainInformation` — consolidated domain info function for better admin display
- **NEW**: `GetTldPricing` — TLD pricing import/sync for 33+ TLDs
- **FIX**: Nameservers correctly display in admin (API key casing fix)
- **FIX**: "Invalid GetTldPricing Response" error resolved
- **FIX**: Privacy level values corrected for API compliance
- **FIX**: Renewal requests now fetch current expiration date
- **IMPROVED**: SRV record handling and contact data formatting

### v1.1.0 (2026-01-19)
- DNS pagination fix for large record sets
- SRV record formatting fix
- Contact payload improvements
- Client area privacy button cleanup

### v1.0.0 (2026-01-15)
- Initial release with full domain lifecycle management

---

## SEO Keywords

WHMCS registrar module, Spaceship WHMCS module, Spaceship domain API integration, WHMCS domain registrar integration, WHMCS domain transfer module, WHMCS DNS management module, WHMCS EPP code module, WHMCS nameserver management, WHMCS domain sync, WHMCS premium domain pricing, WHMCS registrar lock, WHMCS domain automation.

---

## License

MIT License, as declared in:
- `modules/registrars/spaceship/whmcs.json`
- Module source headers in `modules/registrars/spaceship/spaceship.php`

---

## Author

**Waqas Ahmed Waseer**

- GitHub: https://github.com/Waqasahmedwaseer
