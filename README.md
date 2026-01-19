
# 🚀 WHMCS Spaceship Registrar Module

**WHMCS Spaceship Registrar Module** is a **free, open-source, production-ready WHMCS registrar module** for **Spaceship.com**, enabling complete domain management directly from WHMCS.

It supports domain registration, renewal, transfer, DNS management, WHOIS contacts, ID protection, EPP codes, registrar lock, child nameservers, and automated domain synchronization — all with **clean PHP and no ionCube**.

---

## 🔍 Overview

This project provides a **full-featured integration between WHMCS and Spaceship Domains**, built for real hosting companies and daily operational use.

Unlike many paid or encoded registrar modules, this module is:

* transparent
* auditable
* actively tested
* easy to extend

It follows standard WHMCS registrar APIs and is designed for long-term maintainability.

---

## ⚙️ Features

* 🌐 Domain registration & incoming transfers
* 🔁 Domain renewal (1–10 years)
* 👤 WHOIS contact management (Registrant, Admin, Tech, Billing)
* 🌍 Nameserver management
* 🧩 DNS host record management (A, AAAA, CNAME, MX, TXT, NS, SRV)
* 🔒 Registrar lock / unlock
* 🔑 EPP / Auth code retrieval
* 🛡️ ID Protection (WHOIS privacy toggle)
* 🧬 Child nameserver (glue record) management
* 🔄 Automatic domain & transfer sync via WHMCS cron
* 💎 Premium domain pricing support during availability checks

---

## 🧱 Technology Stack

* 🧩 WHMCS Registrar Module API
* 🐘 PHP 7.4+
* 🔐 cURL & JSON extensions
* 🔗 Spaceship.com Domains API

No frameworks, no encoding — just clean, reliable PHP.

---

## ✨ Why This Module?

Most WHMCS registrar modules today are:

* paid
* outdated
* abandoned
* or distributed in encoded (ionCube) form

This module was built to solve a **real production requirement** and is actively used and tested in a live hosting environment.

### Philosophy

* ✅ Free core modules for the community
* ✅ Open-source & auditable code
* ✅ No vendor lock-in
* ✅ Paid only when customization or support is needed

---

## 🎯 Use Cases

* Hosting companies using **Spaceship.com** as a registrar
* Domain resellers managing registrations via WHMCS
* Providers needing advanced DNS & WHOIS control
* Teams looking for registrar automation without paid addons

---

## 📦 Installation

1. Upload the `spaceship` directory to:

   ```
   /modules/registrars/
   ```
2. Log in to **WHMCS Admin → System Settings → Domain Registrars**
3. Activate **Spaceship.com Domain Registrar**
4. Enter your Spaceship API credentials and configure settings

---

## 🧪 Logging & Debugging

When **Debug Mode** is enabled, all API requests and responses are logged to:

**WHMCS → System Logs → Module Log**

This makes troubleshooting and integration validation straightforward.

---

## 🔗 Links & Resources

* 🌐 **GitHub Repository**
  [https://github.com/Waqasahmedwaseer/Whmcs-Spaceship-Registrar](https://github.com/Waqasahmedwaseer/Whmcs-Spaceship-Registrar)

* 🧠 **WaseerHost (Production Usage)**
  [https://waseerhost.com](https://waseerhost.com)

  * 🧠 **co2Host (Production Usage)**
  [https://co2host.com](https://co2host.com)

* 👨‍💻 **Author / Portfolio**
  [https://waqasahmedwaseer.com](https://waqasahmedwaseer.com)

* 🎥 **YouTube Channel (Automation, WHMCS, n8n)**
  [https://www.youtube.com/@WaqasAhmedWaseer](https://www.youtube.com/@WaqasAhmedWaseer)

* 💼 **LinkedIn**
  [https://www.linkedin.com/in/waqasahmedwaseer](https://www.linkedin.com/in/waqasahmedwaseer)

---

## 📞 Support & Custom Development

This module is provided **free and open-source**.

If you need:

* custom features
* registrar workflow changes
* WHMCS automation
* installation & configuration help
* long-term maintenance or enterprise support

📩 **Contact:**
**Email:** [hi@waqasahmedwaseer.com](mailto:support@waqasahmedwaseer.com)
**Website:** [https://waqasahmedwaseer.com/contact](https://waqasahmedwaseer.com/contact)

---

## 📄 License

This project is licensed under the **MIT License**.
Free to use, modify, and extend.

---

## 🏷️ SEO & Keywords

WHMCS Spaceship registrar module, WHMCS domain registrar, Spaceship domains WHMCS integration, free WHMCS registrar module, open-source WHMCS domains module, Spaceship API WHMCS, domain registration automation WHMCS.

---

## ⭐ Maintainer

**Waqas Ahmed Waseer**
Hosting automation engineer & founder of **WaseerHost**
Building open-source WHMCS modules used in real production environments.

