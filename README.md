# MT Dashboard

A lightweight PHP dashboard project connected to trading data exporters (MetaTrader 4/5).  
It provides a simple API + frontend interface to visualize and manage trading-related information.

---

## 📂 Project Structure

```
mt-dashboard/
│
├── api/                     # Backend API endpoints (PHP)
│   ├── config.php            # Database connection settings
│   ├── get_data.php          # Endpoint to fetch data from DB
│   └── receive_data.php      # Endpoint to insert/receive external data
│
├── assets/                   # Static assets
│   ├── css/                  # Stylesheets
│   ├── img/                  # Images (logos, UI assets)
│   ├── js/                   # Frontend scripts
│   └── vendor/               # External libs (Bootstrap, AOS, etc.)
│
├── js/                       # Custom frontend scripts
│   └── trading-dashboard.js  # Dashboard-specific logic
│
├── logs/                     # Logs
│   └── api.log               # API activity log
│
├── mql/                      # MetaTrader Expert Advisors (EA)
│   ├── TradingDataExporter.mq4   # MT4 script (data exporter)
│   └── TradingDataExporter.mq5   # MT5 script (data exporter)
│
├── index.php                 # Main dashboard page
├── maintenance.php           # Maintenance page
├── mysql.txt                 # SQL instructions (DB/tables setup)
├── Test API Endpoints.txt    # API testing notes
└── README.md
```

---

## ⚙️ Requirements

- PHP >= 7.4  
- MySQL / MariaDB  
- Web server (Apache, Nginx, or PHP built-in server)  
- (Optional) MetaTrader 4/5 for running the `.mq4`/`.mq5` exporters

---

## 🚀 Setup

1. Clone this repository:

   ```bash
   git clone https://github.com/fredp74/mt-dashboard.git
   cd mt-dashboard
   ```

2. Configure database connection inside `api/config.php`.

3. Initialize the database using `mysql.txt` (contains schema/tables).

4. Serve the project locally:

   ```bash
   php -S localhost:8080
   ```

   Then open: [http://localhost:8080](http://localhost:8080)

---

## 📌 API Endpoints

- `GET /api/get_data.php` → Fetch data from the database  
- `POST /api/receive_data.php` → Submit trading data to the dashboard  

---

## 📊 Trading Integration (MT4/MT5)

- `mql/TradingDataExporter.mq4` → Expert Advisor for MetaTrader 4  
- `mql/TradingDataExporter.mq5` → Expert Advisor for MetaTrader 5  

These scripts push trading data into the dashboard via `receive_data.php`.

---

## 🛠 Notes

- `logs/api.log` stores API requests & errors.  
- `Test API Endpoints.txt` includes manual test cases for endpoints.  
- Frontend uses Bootstrap and AOS for styling & animations.  

---

## 📜 License

MIT
