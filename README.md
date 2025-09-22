# MT Dashboard

## Overview

MT Dashboard is a lightweight PHP + JS project designed to visualize and manage trading data exported from MetaTrader 4/5 terminals.  
It connects MetaTrader (via custom `.mq4` / `.mq5` exporters) with a backend API (PHP) and a web dashboard.

## Features

- 📊 Real-time trading data visualization
- 🔌 API endpoints for receiving and fetching data (`api/` folder)
- 🖥️ Dashboard UI (`index.php`, assets, and JS logic)
- 📂 Log system (`logs/api.log`)
- ⚙️ MetaTrader exporters (`TradingDataExporter.mq4` / `.mq5`) for data feed
- 🔒 Configurable database connection (`api/config.php`)

## Project Structure

```
.
├── api/                  # PHP API endpoints
│   ├── config.php        # Database configuration
│   ├── get_data.php      # Returns trading data (JSON)
│   └── receive_data.php  # Receives trading data from MT4/MT5
│
├── assets/               # Frontend assets (CSS, JS, vendor libs, images)
│   └── ...
│
├── js/
│   └── trading-dashboard.js  # Core dashboard logic
│
├── logs/
│   └── api.log           # Log file for API requests/errors
│
├── mql/
│   ├── TradingDataExporter.mq4  # MT4 exporter script
│   └── TradingDataExporter.mq5  # MT5 exporter script
│
├── index.php             # Main dashboard entry point
├── maintenance.php       # Maintenance mode page
├── mysql.txt             # Database schema (SQL)
└── Test API Endpoints.txt # Notes for testing API endpoints
```

## Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/fredp74/mt-dashboard.git
   cd mt-dashboard
   ```

2. Import the database schema from `mysql.txt` into your MySQL/MariaDB server.

3. Configure the database connection in `api/config.php`.

4. Deploy the `.mq4` (MetaTrader 4) or `.mq5` (MetaTrader 5) file from `/mql` into your MetaTrader `Experts` or `Scripts` folder.  
   **⚠️ MetaTrader 4/5 is mandatory — without it, the exporters cannot push trading data to the dashboard.**

5. Serve the project with Apache/Nginx + PHP 8.x.

## API Endpoints

- `POST /api/receive_data.php` → Receives trading data from MetaTrader exporters.  
- `GET /api/get_data.php` → Fetches trading data in JSON for the dashboard.

See `Test API Endpoints.txt` for usage examples.

## Requirements

- PHP 8.x + Apache/Nginx
- MySQL/MariaDB
- MetaTrader 4 or 5 (for running the `.mq4` / `.mq5` exporters)
- Web browser (for dashboard visualization)

## Logs

All incoming API requests and errors are logged in:

```
logs/api.log
```

## Status

✅ Core PHP API implemented  
✅ Database schema ready  
✅ MetaTrader exporters included  
🚧 Dashboard UI improvements possible (CSS/UX)  
⚠️ Future improvement: authentication + role-based access
