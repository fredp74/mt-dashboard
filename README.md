# 📊 MT Dashboard

MT Dashboard is a lightweight PHP + MariaDB stack that ingests trading telemetry from **MetaTrader 5** and renders it through a responsive Bootstrap interface. The project ships with an MT5 exporter (`.mq5`) so you can publish live account metrics to the dashboard in just a few steps.

<details>
<summary><strong>Table of Contents</strong></summary>

- [Overview](#-overview)
- [Project Structure](#%EF%B8%8F-project-structure)
- [MetaTrader Integration](#-metatrader-integration)
  - [Exporter Parameters](#-exporter-parameters-tradingdataexportermq5)
  - [Setup Instructions](#%EF%B8%8F-setup-instructions)
- [Backend API](#%EF%B8%8F-backend-api)
- [Frontend Dashboard](#-frontend-dashboard)
- [Database](#%EF%B8%8F-database)
- [Security Notes](#-security-notes)
- [Quick Start](#-quick-start)
- [Public Release Checklist](#-public-release-checklist)
- [Status](#-status)
- [License](#-license)

</details>

---

## 🚀 Overview

**MT Dashboard** provides:

- 📡 **MetaTrader Integration** – MT5 Expert Advisors export live account and trade data.
- 🗄️ **Backend API** – PHP endpoints (`/api/`) receive, log, and insert the data into MariaDB.
- 📊 **Frontend Dashboard** – PHP + JS dashboard for balances, equity, profit, and drawdown charts.
- 🛠️ **Bootstrap 5.2.3** styling for a clean, responsive UI.

---

## 🏗️ Project Structure

```
MT-DASHBOARD/
├── api/                  # PHP backend API
│   ├── config.php        # DB connection + API key
│   ├── get_data.php      # Fetch trading data (JSON)
│   ├── srv_aliases.php   # Map legacy MT5 fields to SRV labels
│   └── receive_data.php  # Receive POSTed data from MetaTrader
│
├── assets/               # Frontend assets (Bootstrap 5.2.3 + JS/CSS/img/vendor)
│   ├── css/
│   ├── js/
│   ├── img/
│   └── vendor/
│
├── js/
│   └── trading-dashboard.js  # Frontend logic (charts, dynamic updates)
│
├── logs/
│   └── api.log           # API log for debugging incoming requests
│
├── mql/                  # MetaTrader Exporter
│   └── TradingDataExporter.mq5
│
├── index.php             # Dashboard homepage
├── maintenance.php       # Maintenance mode page
├── mysql.txt             # Sample MySQL schema / notes
└── Test API Endpoints.txt # Example API calls for testing
```

---

## 📡 MetaTrader Integration

The system relies on an **MT5 data exporter** (`.mq5`) that pushes trading data from MetaTrader into the backend (MariaDB via PHP API).

### 🔧 Exporter Parameters (`TradingDataExporter.mq5`)

```mql5
#property version   "1.00"
#property description "Exports MT5 trading data to web server"

input int UpdateIntervalSeconds = 60;              // Interval between updates (in seconds)
input string WebServerURL = "http://localhost/mt-dashboard/api/receive_data.php";
input string APIKey = "your-secure-api-key-here";  // Must match config.php
input bool EnableWebRequests = true;               // Enable sending data to the web server
input bool EnableFileExport = true;                // Optional: also save to local JSON file
input string ExportFileName = "mt5_data.json";     // File name for local export
```

### ⚙️ Setup Instructions

1. Copy `TradingDataExporter.mq5` into your **MetaTrader 5 terminal** (`MQL5/Experts/`).
2. Restart MetaTrader, then **attach the Expert Advisor** to any chart.
3. In **MetaTrader → Tools → Options → Expert Advisors**:
   - ✅ Check **Allow WebRequest for listed URL**.
   - Add your backend URL, e.g.:
     ```
     http://localhost:8080
     ```
     or your deployed domain if hosted online.
4. The exporter now sends account + trading data every `UpdateIntervalSeconds` to your backend.

👉 Important:

- Update `WebServerURL` with the **real path** where your dashboard API is hosted (`/api/receive_data.php`).
- The **APIKey must match backend `config.php`**.
- If `EnableFileExport = true`, the exporter also writes a backup JSON file (useful for debugging).

---

## ⚙️ Backend API

- `api/config.php` → Database credentials + API key validation.
- `api/receive_data.php` → Receives POSTed JSON data from MT5 exporters, validates API keys, sanitizes payloads, and stores snapshots (including MT5 timestamps) into MariaDB.
- `api/get_data.php` → Returns stored trading/account data as JSON for frontend use.
- `logs/api.log` → Logs requests, errors, and system activity.

---

## 💻 Frontend Dashboard

- Built with **PHP + Bootstrap 5.2.3** for layout/styling.
- Uses `trading-dashboard.js` for interactive charts and live updates.
- Displays:
  - Account balances
  - Open/closed trades
  - Historical performance

Access via:

👉 `http://localhost/mt-dashboard/index.php` (or your deployed URL).

---

## 🗄️ Database

Minimal MariaDB schema required. Example (from `mysql.txt`):

```sql
CREATE TABLE trading_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket BIGINT,
  symbol VARCHAR(20),
  type VARCHAR(10),
  volume DECIMAL(10,2),
  profit DECIMAL(12,2),
  open_time DATETIME,
  close_time DATETIME,
  balance DECIMAL(12,2),
  equity DECIMAL(12,2)
);
```

---

## 🔐 Security Notes

- Always secure your API with a **strong API key** (`config.php`).
- Whitelist only your server URL in MetaTrader WebRequest settings.
- Use HTTPS if deploying online.

---

## 🏁 Quick Start

1. Clone the repository into your web root:
   ```bash
   git clone https://github.com/fredp74/mt-dashboard.git
   ```
2. Configure database credentials in `api/config.php` and set a strong API key.
3. Import the schema from `mysql.txt`.
4. Compile `TradingDataExporter.mq5` with MetaTrader Editor and deploy the `.ex5` into your terminal.
5. Serve the dashboard through Apache/Nginx or the PHP built-in server:
   ```bash
   php -S 0.0.0.0:8000
   ```
6. Visit `http://localhost:8000/index.php` to verify the dashboard is running.

---

## ✅ Public Release Checklist

Before making the repository public, confirm:

- [x] **Branding** – Update titles, alt text, and copy to reference **MT Dashboard**.
- [x] **No legacy domains** – run `rg -i "<your-old-domain>"` and confirm it returns no matches.
- [x] **Secrets scrubbed** – Remove API keys, credentials, or personally identifying data from commits.
- [x] **Logs sanitized** – Clear sensitive entries from `logs/api.log` before publishing.
- [x] **Database dumps** – Ensure any shared SQL files contain sample or anonymized data only.

---

## 📌 Status

- ✅ API + logging functional
- ✅ Dashboard frontend with Bootstrap 5.2.3
- ✅ MT5 exporter included
- 🚧 To improve: Chart.js visualizations, error handling, user auth

---

## 📜 License

MIT License – free to use and adapt.

Trading data and API keys are your responsibility – use securely.
