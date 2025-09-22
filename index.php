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

  <!-- Chart.js for Trading Charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Custom Dashboard Styles -->
  <style>
    .dashboard-container {
      padding: 40px 0;
      background: #f8f9fa;
      min-height: calc(100vh - 200px);
    }
    
    .stats-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 15px;
      margin-bottom: 20px;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
      transform: translateY(-5px);
    }
    
    .drawdown-card {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      border-radius: 15px;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }
    
    .drawdown-card:hover {
      transform: translateY(-5px);
    }
    
    .profit-positive { 
      color: #28a745; 
      font-weight: bold;
      text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .profit-negative { 
      color: #dc3545; 
      font-weight: bold;
      text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .chart-container {
      position: relative;
      height: 400px;
      margin: 20px 0;
    }
    
    .status-online { 
      background-color: #28a745 !important; 
      animation: pulse 2s infinite;
    }
    
    .status-offline { 
      background-color: #dc3545 !important; 
    }
    
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
      70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
      100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }
    
    .chart-card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      margin-bottom: 30px;
      overflow: hidden;
    }
    
    .chart-card .card-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      font-weight: 600;
      border: none;
      padding: 20px;
    }
    
    .account-card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    
    .account-card .card-header {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      color: white;
      font-weight: 600;
      border: none;
      padding: 20px;
    }
    
    .nav-pills .nav-link {
      border-radius: 25px;
      margin: 0 5px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .nav-pills .nav-link.active {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
    }
    
    .dashboard-header {
      text-align: center;
      margin-bottom: 40px;
    }
    
    .dashboard-header h2 {
      color: #2c3e50;
      font-weight: 700;
      margin-bottom: 15px;
    }
    
    .status-indicator {
      display: inline-flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 10px;
    }
    
    .last-update {
      color: #6c757d;
      font-size: 0.9em;
    }
  </style>
</head>

<body>

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
    <section class="dashboard-container">
      <div class="container">
        
        <!-- Dashboard Header -->
        <div class="dashboard-header" data-aos="fade-up">
          <h2>Real-Time Trading Performance</h2>
          <div class="status-indicator">
            <span class="badge status-online me-2" id="mt4-status">MT4: Online</span>
            <span class="badge status-online me-2" id="mt5-status">MT5: Online</span>
          </div>
          <div class="last-update">
            Last Update: <span id="last-update">--</span>
          </div>
        </div>

        <!-- Time Filter Tabs -->
        <div class="row mb-4" data-aos="fade-up" data-aos-delay="100">
          <div class="col-12">
            <ul class="nav nav-pills justify-content-center" id="time-filter-tabs">
              <li class="nav-item">
                <a class="nav-link active" data-period="24h" href="#">Last 24H</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-period="7d" href="#">Last Week</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-period="30d" href="#">Last Month</a>
              </li>
            </ul>
          </div>
        </div>

        <!-- Current Stats Cards -->
        <div class="row mb-4" data-aos="fade-up" data-aos-delay="200">
          <div class="col-lg-3 col-md-6">
            <div class="card stats-card">
              <div class="card-body text-center">
                <i class="bi bi-wallet2 mb-3" style="font-size: 2.5rem;"></i>
                <h5 class="card-title">Total Balance</h5>
                <h3 id="total-balance">$0.00</h3>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="card stats-card">
              <div class="card-body text-center">
                <i class="bi bi-graph-up mb-3" style="font-size: 2.5rem;"></i>
                <h5 class="card-title">Total Equity</h5>
                <h3 id="total-equity">$0.00</h3>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="card stats-card">
              <div class="card-body text-center">
                <i class="bi bi-currency-dollar mb-3" style="font-size: 2.5rem;"></i>
                <h5 class="card-title">Total Profit</h5>
                <h3 id="total-profit">$0.00</h3>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="card drawdown-card">
              <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle mb-3" style="font-size: 2.5rem;"></i>
                <h5 class="card-title">Max Drawdown</h5>
                <h3 id="max-drawdown">0.00%</h3>
                <small id="drawdown-period">Last 30 days</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-4" data-aos="fade-up" data-aos-delay="300">
          <div class="col-lg-6">
            <div class="card chart-card">
              <div class="card-header">
                <h5><i class="bi bi-graph-up me-2"></i>Profit Curve</h5>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="profitChart"></canvas>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card chart-card">
              <div class="card-header">
                <h5><i class="bi bi-bar-chart me-2"></i>Balance & Equity</h5>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="balanceChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Account Details -->
        <div class="row" data-aos="fade-up" data-aos-delay="400">
          <div class="col-lg-6">
            <div class="card account-card">
              <div class="card-header">
                <h5><i class="bi bi-pc-display me-2"></i>MT4 Account Details</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-6">
                    <p><strong>Balance:</strong> $<span id="mt4-balance">0.00</span></p>
                    <p><strong>Equity:</strong> $<span id="mt4-equity">0.00</span></p>
                    <p><strong>Positions:</strong> <span id="mt4-positions">0</span></p>
                  </div>
                  <div class="col-6">
                    <p><strong>Profit:</strong> $<span id="mt4-profit">0.00</span></p>
                    <p><strong>Margin:</strong> $<span id="mt4-margin">0.00</span></p>
                    <p><strong>Free Margin:</strong> $<span id="mt4-free-margin">0.00</span></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card account-card">
              <div class="card-header">
                <h5><i class="bi bi-laptop me-2"></i>MT5 Account Details</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-6">
                    <p><strong>Balance:</strong> $<span id="mt5-balance">0.00</span></p>
                    <p><strong>Equity:</strong> $<span id="mt5-equity">0.00</span></p>
                    <p><strong>Positions:</strong> <span id="mt5-positions">0</span></p>
                  </div>
                  <div class="col-6">
                    <p><strong>Profit:</strong> $<span id="mt5-profit">0.00</span></p>
                    <p><strong>Margin:</strong> $<span id="mt5-margin">0.00</span></p>
                    <p><strong>Free Margin:</strong> $<span id="mt5-free-margin">0.00</span></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Note -->
        <div class="row mt-4" data-aos="fade-up" data-aos-delay="500">
          <div class="col-12">
            <div class="alert alert-info" role="alert">
              <i class="bi bi-info-circle me-2"></i>
              <strong>Note:</strong> All data is updated in real-time every 30 seconds. 
              Performance results are based on live trading accounts and reflect actual market conditions.
            </div>
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
