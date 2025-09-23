<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Live Trading Dashboard - AlgoTradingResearch - Maximize Your Profits with HFT</title>
  <meta content="Real-time trading results and performance analytics for our Expert Advisors" name="description">
  <meta content="trading dashboard, MT4, MT5, forex trading, algorithmic trading, HFT" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
  <link href="assets/css/dashboard.css" rel="stylesheet">

  <!-- Chart.js for Trading Charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="dashboard-page">

   <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center">

      <div class="logo me-auto">
        <a href="/"><img src="assets/img/logo.png" alt="" class="img-fluid"></a>
      </div>

      <nav id="navbar" class="navbar order-last order-lg-0">
        <ul>
          <li><a class="nav-link scrollto" href="/#hero">Home</a></li>
          <li><a class="nav-link scrollto" href="/#about">About Us</a></li>
          <li><a class="nav-link scrollto" href="/#services">Services</a></li>
          <li><a class="nav-link scrollto" href="/#faq">FAQ</a></li>
          <li><a class="nav-link scrollto" href="/#contact">Contact</a></li>
          <li><a class="nav-link scrollto" href="partnership.php">Partnership</a></li>
          <li><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->
	</div>
  </header><!-- End Header -->

  <main id="main">

    <!-- ======= Breadcrumbs ======= -->
    <section id="breadcrumbs" class="breadcrumbs">
      <div class="container">
        <ol>
          <li><a href="/">Home</a></li>
          <li>Live Trading Dashboard</li>
        </ol>
        <h2>Live Trading Dashboard</h2>
      </div>
    </section><!-- End Breadcrumbs -->

    <!-- ======= Dashboard Section ======= -->
    <section class="dashboard-section">
      <div class="container">

        <!-- Dashboard Header -->
        <div class="dashboard-header" data-aos="fade-up">
          <h2>Real-Time Trading Performance</h2>
          <div class="status-indicator">
            <span class="badge status-online" id="mt4-status">MT4: Online</span>
            <span class="badge status-online" id="mt5-status">MT5: Online</span>
          </div>
          <div class="last-update">
            Last Update: <span id="last-update">--</span>
          </div>
        </div>

        <!-- Time Filter Tabs -->
        <ul class="time-filter-tabs" id="time-filter-tabs" data-aos="fade-up" data-aos-delay="100">
          <li><a class="nav-link active" data-period="24h" href="#">Last 24H</a></li>
          <li><a class="nav-link" data-period="7d" href="#">Last Week</a></li>
          <li><a class="nav-link" data-period="30d" href="#">Last Month</a></li>
        </ul>

        <!-- Current Stats Cards -->
        <div class="stats-grid" data-aos="fade-up" data-aos-delay="200">
          <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
            <h5>Total Balance</h5>
            <div class="stat-value" id="total-balance">$0.00</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
            <h5>Total Equity</h5>
            <div class="stat-value" id="total-equity">$0.00</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
            <h5>Total Profit</h5>
            <div class="stat-value" id="total-profit">$0.00</div>
          </div>
          <div class="stat-card drawdown">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <h5>Max Drawdown</h5>
            <div class="stat-value" id="max-drawdown">0.00%</div>
            <div class="stat-note" id="drawdown-period">Last 30 days</div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="chart-grid" data-aos="fade-up" data-aos-delay="300">
          <div class="dashboard-card chart-card">
            <div class="dashboard-card-header">
              <i class="bi bi-graph-up"></i>
              <h5>Profit Curve</h5>
            </div>
            <div class="dashboard-card-body">
              <div class="chart-container">
                <canvas id="profitChart"></canvas>
              </div>
            </div>
          </div>
          <div class="dashboard-card chart-card">
            <div class="dashboard-card-header">
              <i class="bi bi-bar-chart"></i>
              <h5>Balance &amp; Equity</h5>
            </div>
            <div class="dashboard-card-body">
              <div class="chart-container">
                <canvas id="balanceChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Account Details -->
        <div class="account-grid" data-aos="fade-up" data-aos-delay="400">
          <div class="dashboard-card account-card">
            <div class="dashboard-card-header">
              <i class="bi bi-pc-display"></i>
              <h5>MT4 Account Details</h5>
            </div>
            <div class="dashboard-card-body">
              <div class="account-details">
                <div class="account-detail">
                  <span class="label">Balance</span>
                  <span class="value">$<span id="mt4-balance">0.00</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Equity</span>
                  <span class="value">$<span id="mt4-equity">0.00</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Positions</span>
                  <span class="value"><span id="mt4-positions">0</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Profit</span>
                  <span class="value">$<span id="mt4-profit">0.00</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Margin</span>
                  <span class="value">$<span id="mt4-margin">0.00</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Free Margin</span>
                  <span class="value">$<span id="mt4-free-margin">0.00</span></span>
                </div>
              </div>
            </div>
          </div>
          <div class="dashboard-card account-card">
            <div class="dashboard-card-header">
              <i class="bi bi-laptop"></i>
              <h5>MT5 Account Details</h5>
            </div>
            <div class="dashboard-card-body">
              <div class="account-details">
                <div class="account-detail">
                  <span class="label">Balance</span>
                  <span class="value">$<span id="mt5-balance">0.00</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Equity</span>
                  <span class="value">$<span id="mt5-equity">0.00</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Positions</span>
                  <span class="value"><span id="mt5-positions">0</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Profit</span>
                  <span class="value">$<span id="mt5-profit">0.00</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Margin</span>
                  <span class="value">$<span id="mt5-margin">0.00</span></span>
                </div>
                <div class="account-detail">
                  <span class="label">Free Margin</span>
                  <span class="value">$<span id="mt5-free-margin">0.00</span></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Note -->
        <div class="info-banner" data-aos="fade-up" data-aos-delay="500">
          <i class="bi bi-info-circle"></i>
          <div>
            <strong>Note:</strong> All data is updated in real-time every 30 seconds. Performance results are based on live trading accounts and reflect actual market conditions.
          </div>
        </div>

      </div>
    </section><!-- End Dashboard Section -->

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer">
    <div class="container">
      <div class="copyright">
        &copy; <?php echo date("Y"); ?> <strong><span>AlgoTradingResearch</span></strong>. All Rights Reserved.<span><a href="privacy.php"> Privacy</a> - <a href="terms.php">T&C</a></span>
      </div>
      <div class="credits">
       AlgoTradingResearch is not associated with MetaQuotes Software Corp.
      </div>
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

  <!-- Dashboard JavaScript -->
  <script src="js/trading-dashboard.js"></script>

</body>

</html>
