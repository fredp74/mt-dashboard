# 📊 MT Dashboard

A PHP-based dashboard for displaying and analyzing trading data from MetaTrader (MT4/MT5).  
It provides a lightweight web interface for monitoring performance, with database persistence and a responsive frontend.

---

## 🚀 Features

- 📈 Display trading results and account metrics.  
- 🔄 Integration with MetaTrader 4/5 exporters (via database).  
- 🗄️ MariaDB/MySQL persistence for account and trade history.  
- 🎨 Responsive UI with **Bootstrap 5.2.3**.  
- ⚡ Lightweight, no heavy frameworks – pure PHP + MySQL + Bootstrap.  

---

## 🏗️ Project Structure

```
mt-dashboard/
├── assets/               # Frontend assets (Bootstrap 5.2.3, custom CSS/JS, vendor libs, images)
│   └── ...
├── config/               # Database and app configuration
│   └── config.php
├── includes/             # PHP includes (header, footer, helpers)
│   └── ...
├── pages/                # Dashboard pages (overview, accounts, trades, etc.)
│   └── ...
├── sql/                  # SQL schema and migrations
│   └── schema.sql
├── index.php             # Main entry point (router / dashboard home)
├── .gitignore
└── README.md
```

---

## ⚙️ Tech Stack

- **Backend**: PHP 8+  
- **Database**: MariaDB 11 / MySQL 8  
- **Frontend**: Bootstrap 5.2.3 + custom CSS/JS  
- **MetaTrader Export**: Scripts push trading data into MariaDB via `.sql` inserts  

---

## 🔧 Setup

### 1. Clone Repository

```bash
git clone https://github.com/fredp74/mt-dashboard.git
cd mt-dashboard
```

### 2. Database

- Create a database in MariaDB/MySQL:  

```sql
CREATE DATABASE mtdashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

- Import schema:

```bash
mysql -u youruser -p mtdashboard < sql/schema.sql
```

### 3. Configuration

Edit `config/config.php` with your DB credentials:

```php
<?php
$host = "localhost";
$db   = "mtdashboard";
$user = "youruser";
$pass = "yourpassword";
?>
```

### 4. Run Locally

- Place the repo inside your Apache/Nginx document root (or use Docker/LAMP stack).  
- Access via `http://localhost/mt-dashboard`.

---

## 📡 MetaTrader Integration

- Exporters from **MT4/MT5** push trading data (orders, balance, history) into the MariaDB database.  
- The dashboard queries this database to display charts, account stats, and trade logs.  
- Without MetaTrader → no live data.  

---

## ✅ Status

- ✅ Database schema + PHP dashboard scaffold  
- ✅ Bootstrap 5.2.3 responsive frontend  
- 🚧 To add: extended charts & KPIs  
- ⚠️ Note: MetaTrader exporters required for live data  
