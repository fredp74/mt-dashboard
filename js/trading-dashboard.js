// Enhanced version with additional visual effects for your website
class TradingDashboard {
    constructor() {
        this.profitChart = null;
        this.balanceChart = null;
        this.currentPeriod = '24h';
        this.updateInterval = null;
        this.demoToastShown = false;
        
        // Wait for AOS animations to initialize
        setTimeout(() => {
            this.initializeCharts();
            this.initializeEventListeners();
            this.fetchData();
            this.startRealTimeUpdates();
        }, 500);
    }

    initializeCharts() {
        // Enhanced Profit Chart with your brand colors
        const profitCtx = document.getElementById('profitChart').getContext('2d');
        this.profitChart = new Chart(profitCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Total Profit ($)',
                    data: [],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: '#764ba2',
                    pointHoverBorderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 14, weight: 'bold' },
                            color: '#2c3e50'
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time',
                            font: { size: 14, weight: 'bold' },
                            color: '#2c3e50'
                        },
                        grid: { color: 'rgba(0,0,0,0.1)' },
                        ticks: { color: '#6c757d' }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Profit ($)',
                            font: { size: 14, weight: 'bold' },
                            color: '#2c3e50'
                        },
                        grid: { color: 'rgba(0,0,0,0.1)' },
                        ticks: { 
                            color: '#6c757d',
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Enhanced Balance Chart
        const balanceCtx = document.getElementById('balanceChart').getContext('2d');
        this.balanceChart = new Chart(balanceCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'SRV Balance',
                        data: [],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'SRV Equity',
                        data: [],
                        borderColor: '#764ba2',
                        backgroundColor: 'rgba(118, 75, 162, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 12, weight: 'bold' },
                            color: '#2c3e50'
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time',
                            font: { size: 14, weight: 'bold' },
                            color: '#2c3e50'
                        },
                        grid: { color: 'rgba(0,0,0,0.1)' },
                        ticks: { color: '#6c757d' }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Amount ($)',
                            font: { size: 14, weight: 'bold' },
                            color: '#2c3e50'
                        },
                        grid: { color: 'rgba(0,0,0,0.1)' },
                        ticks: { 
                            color: '#6c757d',
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    initializeEventListeners() {
        // Time filter tabs with enhanced animations
        document.querySelectorAll('#time-filter-tabs a').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Update active tab with animation
                document.querySelectorAll('#time-filter-tabs a').forEach(t => {
                    t.classList.remove('active');
                    t.style.transform = 'scale(1)';
                });
                
                e.target.classList.add('active');
                e.target.style.transform = 'scale(1.05)';
                
                // Reset scale after animation
                setTimeout(() => {
                    e.target.style.transform = 'scale(1)';
                }, 150);
                
                // Update current period and fetch data
                this.currentPeriod = e.target.getAttribute('data-period');
                this.updateDrawdownPeriodLabel();
                this.showLoadingIndicator();
                this.fetchData();
            });
        });
    }

    showLoadingIndicator() {
        const chartContainers = document.querySelectorAll('.chart-container');
        chartContainers.forEach(container => {
            if (!container.querySelector('.loading-spinner')) {
                const spinner = document.createElement('div');
                spinner.className = 'loading-spinner d-flex justify-content-center align-items-center position-absolute w-100 h-100';
                spinner.style.top = '0';
                spinner.style.left = '0';
                spinner.style.backgroundColor = 'rgba(255,255,255,0.8)';
                spinner.style.zIndex = '1000';
                spinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
                container.style.position = 'relative';
                container.appendChild(spinner);
            }
        });
    }

    hideLoadingIndicator() {
        document.querySelectorAll('.loading-spinner').forEach(spinner => {
            spinner.remove();
        });
    }

    updateDrawdownPeriodLabel() {
        const periodLabels = {
            '24h': 'Last 24 hours',
            '7d': 'Last 7 days',
            '30d': 'Last 30 days'
        };
        const element = document.getElementById('drawdown-period');
        if (element) {
            element.textContent = periodLabels[this.currentPeriod] || 'Last 30 days';
        }
    }

    async fetchData() {
        try {
            const response = await fetch(`api/get_data.php?period=${this.currentPeriod}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                console.error('API Error:', data.error);
                this.updateConnectionStatus(false);
                this.showErrorMessage('Failed to fetch data from server');
                this.updateCurrentStats({}, false);
                this.updateDrawdown(null, false);
                return;
            }

            const current = data.current || {};
            const isDemoMode = data.status === 'demo';
            const isOnline = !isDemoMode && Boolean(current.is_online);

            if (isDemoMode) {
                if (!this.demoToastShown) {
                    this.showDemoNotice(data.message || 'Showing demo data while the live feed is unavailable.');
                    this.demoToastShown = true;
                }
                this.updateConnectionStatus('demo');
            } else {
                this.demoToastShown = false;
                const demoToast = document.getElementById('demo-toast');
                if (demoToast) {
                    bootstrap.Toast.getOrCreateInstance(demoToast).hide();
                }
                this.updateConnectionStatus(isOnline);
            }

            this.updateCurrentStats(current, isOnline);

            if (Array.isArray(data.history) && data.history.length > 0) {
                this.updateCharts(data.history);
            }

            const drawdownMode = isDemoMode ? 'demo' : isOnline;
            this.updateDrawdown(data.drawdown || null, drawdownMode);

            if (!isOnline) {
                console.warn('SRV is offline - awaiting live data.');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            this.updateConnectionStatus(false);
            this.showErrorMessage('Network connection error');
            this.updateCurrentStats({}, false);
            this.updateDrawdown(null, false);
        } finally {
            this.hideLoadingIndicator();
        }
    }

    showErrorMessage(message) {
        let toast = document.getElementById('error-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'error-toast';
            toast.className = 'toast position-fixed top-0 end-0 m-3';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="toast-header bg-danger text-white">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong class="me-auto">Connection Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            document.body.appendChild(toast);
        } else {
            toast.querySelector('.toast-body').textContent = message;
        }
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    showDemoNotice(message) {
        let toast = document.getElementById('demo-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'demo-toast';
            toast.className = 'toast position-fixed top-0 start-50 translate-middle-x mt-3';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="toast-header bg-primary text-white">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong class="me-auto">Demo Mode</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            document.body.appendChild(toast);
        } else {
            toast.querySelector('.toast-body').textContent = message;
        }

        const bsToast = bootstrap.Toast.getOrCreateInstance(toast, { autohide: false });
        bsToast.show();
    }

    updateCurrentStats(current, isOnline) {
        const totals = current || {};

        this.animateNumber('total-balance', totals.total_balance ?? 0, true);
        this.animateNumber('total-equity', totals.total_equity ?? 0, true);

        const profitElement = document.getElementById('total-profit');
        const totalProfitValue = totals.period_profit ?? totals.total_profit ?? 0;
        this.animateNumber('total-profit', totalProfitValue, true);

        if (profitElement) {
            const numericProfit = Number(totalProfitValue) || 0;
            profitElement.classList.remove('profit-positive', 'profit-negative');
            profitElement.classList.add(numericProfit >= 0 ? 'profit-positive' : 'profit-negative');
        }

        const srvData = totals.srv || totals.mt5;
        if (srvData) {
            this.animateNumber('srv-balance', srvData.balance ?? 0);
            this.animateNumber('srv-equity', srvData.equity ?? 0);
            this.animateNumber('srv-profit', srvData.profit ?? 0);
            this.animateNumber('srv-margin', srvData.margin ?? 0);
            this.animateNumber('srv-free-margin', srvData.free_margin ?? 0);

            const positionsEl = document.getElementById('srv-positions');
            if (positionsEl) {
                positionsEl.textContent = srvData.open_positions ?? 0;
            }
        } else {
            this.animateNumber('srv-balance', 0);
            this.animateNumber('srv-equity', 0);
            this.animateNumber('srv-profit', 0);
            this.animateNumber('srv-margin', 0);
            this.animateNumber('srv-free-margin', 0);

            const positionsEl = document.getElementById('srv-positions');
            if (positionsEl) {
                positionsEl.textContent = 0;
            }
        }

        const lastUpdate = document.getElementById('last-update');
        if (lastUpdate) {
            const rawTimestamp = totals.last_update;

            if (rawTimestamp) {
                const parsedTimestamp = new Date(rawTimestamp);
                if (!Number.isNaN(parsedTimestamp.getTime())) {
                    lastUpdate.textContent = parsedTimestamp.toLocaleString();
                } else {
                    lastUpdate.textContent = rawTimestamp;
                }
            } else {
                lastUpdate.textContent = '--';
            }

            lastUpdate.classList.toggle('text-warning', !isOnline);
        }
    }

    updateDrawdown(drawdown, mode) {
        const maxDrawdownElement = document.getElementById('max-drawdown');
        if (!maxDrawdownElement) return;

        const isDemoMode = mode === 'demo';
        const isOnline = mode === true;

        if (!drawdown || (!isOnline && !isDemoMode)) {
            maxDrawdownElement.textContent = '--';
            maxDrawdownElement.style.color = '';
            return;
        }

        //  FIX: ensure numeric conversion
        const drawdownValue = Number(drawdown.max_drawdown ?? 0);
        maxDrawdownElement.textContent = `${drawdownValue.toFixed(2)}%`;

        maxDrawdownElement.style.transition = 'color 0.5s ease';

        if (isDemoMode) {
            maxDrawdownElement.style.color = '#0d6efd';
        } else if (drawdownValue > 20) {
            maxDrawdownElement.style.color = '#dc3545';
        } else if (drawdownValue > 10) {
            maxDrawdownElement.style.color = '#ffc107';
        } else {
            maxDrawdownElement.style.color = '#28a745';
        }
    }

    updateCharts(historyData) {
        if (!historyData || historyData.length === 0) {
            console.log('No historical data available');
            return;
        }

        const timeLabels = [];
        const profitData = [];
        const srvBalanceData = [];
        const srvEquityData = [];

        const groupedData = {};
        historyData.forEach(row => {
            const timestamp = new Date(row.timestamp).toISOString();
            if (!groupedData[timestamp]) {
                groupedData[timestamp] = { srv: {}, legacy: {} };
            }

            if (row.account_type === 'SRV') {
                groupedData[timestamp].srv = row;
            } else if (row.account_type === 'MT5') {
                groupedData[timestamp].legacy = row;
            }
        });

        Object.keys(groupedData).sort().forEach(timestamp => {
            const data = groupedData[timestamp];
            const date = new Date(timestamp);
            
            let timeLabel;
            if (this.currentPeriod === '24h') {
                timeLabel = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (this.currentPeriod === '7d') {
                timeLabel = date.toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit' });
            } else {
                timeLabel = date.toLocaleDateString([], { month: 'short', day: 'numeric' });
            }
            
            timeLabels.push(timeLabel);
            
            const accountSnapshot = Object.keys(data.srv || {}).length ? data.srv : data.legacy || {};
            const totalProfit = accountSnapshot.profit || 0;
            profitData.push(totalProfit);

            srvBalanceData.push(accountSnapshot.balance || null);
            srvEquityData.push(accountSnapshot.equity || null);
        });

        this.profitChart.data.labels = timeLabels;
        this.profitChart.data.datasets[0].data = profitData;
        
        const lastProfit = profitData[profitData.length - 1] || 0;
        const firstProfit = profitData[0] || 0;
        const isPositiveTrend = lastProfit >= firstProfit;
        
        if (isPositiveTrend) {
            this.profitChart.data.datasets[0].borderColor = '#28a745';
            this.profitChart.data.datasets[0].backgroundColor = 'rgba(40, 167, 69, 0.1)';
            this.profitChart.data.datasets[0].pointBackgroundColor = '#28a745';
        } else {
            this.profitChart.data.datasets[0].borderColor = '#dc3545';
            this.profitChart.data.datasets[0].backgroundColor = 'rgba(220, 53, 69, 0.1)';
            this.profitChart.data.datasets[0].pointBackgroundColor = '#dc3545';
        }
        
        this.profitChart.update('none');

        this.balanceChart.data.labels = timeLabels;
        this.balanceChart.data.datasets[0].data = srvBalanceData;
        this.balanceChart.data.datasets[1].data = srvEquityData;
        this.balanceChart.update('none');
    }

    updateConnectionStatus(status) {
        const srvStatus = document.getElementById('srv-status');

        if (!srvStatus) {
            return;
        }

        let statusText = 'SRV: Offline';
        let statusClass = 'status-offline';
        let dataStatus = 'offline';

        if (status === 'demo') {
            statusText = 'SRV: Demo Mode';
            statusClass = 'status-demo';
            dataStatus = 'demo';
        } else if (status) {
            statusText = 'SRV: Online';
            statusClass = 'status-online';
            dataStatus = 'online';
        }

        srvStatus.textContent = statusText;
        srvStatus.classList.add('badge');
        srvStatus.classList.remove('status-online', 'status-offline', 'status-demo');
        srvStatus.classList.add(statusClass);
        srvStatus.setAttribute('data-status', dataStatus);
    }

    startRealTimeUpdates() {
        this.updateInterval = setInterval(() => {
            this.fetchData();
        }, 30000);
    }

    stopRealTimeUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }

    animateNumber(elementId, targetValue, isCurrency = false) {
        const element = document.getElementById(elementId);
        if (!element) return;

        let sanitizedTarget = typeof targetValue === 'number' ? targetValue : parseFloat(targetValue);
        if (!Number.isFinite(sanitizedTarget)) {
            sanitizedTarget = 0;
        }

        const currentValue = parseFloat(element.textContent.replace(/[$,]/g, '')) || 0;
        const increment = (sanitizedTarget - currentValue) / 30;
        let currentStep = 0;

        const timer = setInterval(() => {
            currentStep++;
            const newValue = currentValue + (increment * currentStep);

            if (isCurrency) {
                element.textContent = this.formatCurrency(newValue);
            } else {
                element.textContent = this.formatNumber(newValue);
            }
            
            if (currentStep >= 30) {
                clearInterval(timer);
                if (isCurrency) {
                    element.textContent = this.formatCurrency(sanitizedTarget);
                } else {
                    element.textContent = this.formatNumber(sanitizedTarget);
                }
            }
        }, 33);
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2
        }).format(amount || 0);
    }

    formatNumber(amount) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount || 0);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        window.tradingDashboard = new TradingDashboard();
    }, 100);
});

window.addEventListener('beforeunload', function() {
    if (window.tradingDashboard) {
        window.tradingDashboard.stopRealTimeUpdates();
    }
});
