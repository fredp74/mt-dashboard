# 📊 MT-Dashboard

A lightweight PHP + MariaDB dashboard for visualizing trading data exported from **MetaTrader 5**.
Includes an exporter (`.mq5`) that pushes trading activity to a backend API, which feeds a responsive Bootstrap-based frontend.

---

## 🚀 Overview

**MT-Dashboard** provides:

- 📡 **MetaTrader Integration**: MT5 Expert Advisors export live account + trading data.
- 🗄️ **Backend API**: PHP endpoints (`/api/`) receive, log, and insert trading data into MariaDB.  
- 📊 **Frontend Dashboard**: PHP + JS dashboard for charts, balances, and trade history.  
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

The system relies on an **MT5 data exporter** (`.mq5`) that pushes trading data from the trading terminal into the backend (`MariaDB` via PHP API).

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
     http://localhost/mt-dashboard/api/receive_data.php
     ```
     or your deployed domain if hosted online.
4. The exporter will now send account + trading data every `UpdateIntervalSeconds` to your backend.

👉 Important:
- Update `WebServerURL` with the **real path** where your dashboard API is hosted (`/api/receive_data.php`).
- The **APIKey must match backend `config.php`**.
- If `EnableFileExport = true`, the exporter also writes a backup JSON file (useful for debugging).

---

## ⚙️ Backend API

- `api/config.php` → Database credentials + API key validation.  
- `api/receive_data.php` → Receives POSTed JSON data from MT5 exporters, validates API key, sanitizes payloads, and stores snapshots (including MT5 timestamps) into MariaDB.
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
CREATE TABLE trades (
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

1. Clone repo into your web root:
   ```bash
   git clone https://github.com/fredp74/mt-dashboard.git
   ```
2. Configure database in `api/config.php`.  
3. Import schema from `mysql.txt`.  
4. Compile `TradingDataExporter.mq5` with your MetaTrader editor and deploy the ex5 into your terminal.
5. Start dashboard:
   ```
   http://localhost/mt-dashboard/
   ```

---

## 📌 Status

✅ API + logging functional  
✅ Dashboard frontend with Bootstrap 5.2.3  
✅ MT5 exporter included
🚧 To improve: Chart.js visualizations, error handling, user auth  

---

## 📜 License

MIT License – free to use and adapt.  
Trading data and API keys are your responsibility – use securely.
