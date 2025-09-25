// Enhanced version with additional visual effects for your website
class TradingDashboard {
    constructor() {
        this.profitChart = null;
        this.balanceChart = null;
        this.currentPeriod = '24h';
        this.updateInterval = null;
        
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
                        label: 'MT5 Balance',
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
                        label: 'MT5 Equity',
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
        // Add loading spinner to charts
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
            const data = await response.json();
            
            if (data.error) {
                console.error('API Error:', data.error);
                this.updateConnectionStatus(false);
                this.showErrorMessage('Failed to fetch data from server');
                return;
            }

            this.updateConnectionStatus(true);
            this.updateCurrentStats(data.current);
            
            if (data.history) {
                this.updateCharts(data.history);
            }
            
            if (data.drawdown) {
                this.updateDrawdown(data.drawdown);
            }

            this.hideLoadingIndicator();
            
        } catch (error) {
            console.error('Fetch error:', error);
            this.updateConnectionStatus(false);
            this.showErrorMessage('Network connection error');
            this.hideLoadingIndicator();
        }
    }

    showErrorMessage(message) {
        // Create or update error toast
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

    updateCurrentStats(current) {
        // Update totals with enhanced animations
        this.animateNumber('total-balance', current.total_balance, true);
        this.animateNumber('total-equity', current.total_equity, true);
        
        const profitElement = document.getElementById('total-profit');
        this.animateNumber('total-profit', current.total_profit, true);
        
        // Update profit color with animation
        setTimeout(() => {
            profitElement.className = current.total_profit >= 0 ? 'profit-positive' : 'profit-negative';
        }, 500);

        // Update MT5 data
        if (current.mt5) {
            this.animateNumber('mt5-balance', current.mt5.balance);
            this.animateNumber('mt5-equity', current.mt5.equity);
            this.animateNumber('mt5-profit', current.mt5.profit);
            this.animateNumber('mt5-margin', current.mt5.margin || 0);
            this.animateNumber('mt5-free-margin', current.mt5.free_margin || 0);
            
            const positionsEl = document.getElementById('mt5-positions');
            if (positionsEl) positionsEl.textContent = current.mt5.open_positions || 0;
        }

        // Update last update time
        const lastUpdate = document.getElementById('last-update');
        if (lastUpdate) {
            lastUpdate.textContent = new Date(current.last_update).toLocaleString();
        }
    }

    updateDrawdown(drawdown) {
        const maxDrawdownElement = document.getElementById('max-drawdown');
        if (!maxDrawdownElement) return;
        
        const drawdownValue = Math.abs(drawdown.max_drawdown);
        maxDrawdownElement.textContent = `${drawdownValue.toFixed(2)}%`;
        
        // Enhanced color coding with smooth transitions
        maxDrawdownElement.style.transition = 'color 0.5s ease';
        
        if (drawdownValue > 20) {
            maxDrawdownElement.style.color = '#dc3545'; // Red for high drawdown
        } else if (drawdownValue > 10) {
            maxDrawdownElement.style.color = '#ffc107'; // Yellow for medium drawdown
        } else {
            maxDrawdownElement.style.color = '#28a745'; // Green for low drawdown
        }
    }

    updateCharts(historyData) {
        if (!historyData || historyData.length === 0) {
            console.log('No historical data available');
            return;
        }

        // Process data for charts
        const timeLabels = [];
        const profitData = [];
        const mt5BalanceData = [];
        const mt5EquityData = [];

        // Group data by timestamp
        const groupedData = {};
        historyData.forEach(row => {
            const timestamp = new Date(row.timestamp).toISOString();
            if (!groupedData[timestamp]) {
                groupedData[timestamp] = { mt5: {} };
            }

            if (row.account_type === 'MT5') {
                groupedData[timestamp].mt5 = row;
            }
        });

        // Convert grouped data to chart format
        Object.keys(groupedData).sort().forEach(timestamp => {
            const data = groupedData[timestamp];
            const date = new Date(timestamp);
            
            // Format time label based on period
            let timeLabel;
            if (this.currentPeriod === '24h') {
                timeLabel = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (this.currentPeriod === '7d') {
                timeLabel = date.toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit' });
            } else {
                timeLabel = date.toLocaleDateString([], { month: 'short', day: 'numeric' });
            }
            
            timeLabels.push(timeLabel);
            
            const totalProfit = data.mt5.profit || 0;
            profitData.push(totalProfit);

            // Individual account data
            mt5BalanceData.push(data.mt5.balance || null);
            mt5EquityData.push(data.mt5.equity || null);
        });

        // Update profit chart with enhanced visuals
        this.profitChart.data.labels = timeLabels;
        this.profitChart.data.datasets[0].data = profitData;
        
        // Dynamic color based on performance
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

        // Update balance chart
        this.balanceChart.data.labels = timeLabels;
        this.balanceChart.data.datasets[0].data = mt5BalanceData;
        this.balanceChart.data.datasets[1].data = mt5EquityData;
        this.balanceChart.update('none');
    }

    updateConnectionStatus(isOnline) {
        const mt5Status = document.getElementById('mt5-status');

        if (isOnline) {
            mt5Status.textContent = 'MT5: Online';
            mt5Status.className = 'badge status-online me-2';
        } else {
            mt5Status.textContent = 'MT5: Offline';
            mt5Status.className = 'badge status-offline me-2';
        }
    }

    startRealTimeUpdates() {
        // Update every 30 seconds
        this.updateInterval = setInterval(() => {
            this.fetchData();
        }, 30000);
    }

    stopRealTimeUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }

    // Enhanced number animation with easing
    animateNumber(elementId, targetValue, isCurrency = false) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const currentValue = parseFloat(element.textContent.replace(/[$,]/g, '')) || 0;
        const increment = (targetValue - currentValue) / 30; // 30 steps for smoother animation
        let currentStep = 0;
        
        const timer = setInterval(() => {
            currentStep++;
            // Easing function for smooth animation
            const progress = currentStep / 30;
            const easedProgress = 1 - Math.pow(1 - progress, 3); // Ease out cubic
            const newValue = currentValue + (increment * currentStep);
            
            if (isCurrency) {
                element.textContent = this.formatCurrency(newValue);
            } else {
                element.textContent = this.formatNumber(newValue);
            }
            
            if (currentStep >= 30) {
                clearInterval(timer);
                // Set final value to ensure accuracy
                if (isCurrency) {
                    element.textContent = this.formatCurrency(targetValue);
                } else {
                    element.textContent = this.formatNumber(targetValue);
                }
            }
        }, 33); // ~30fps animation
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

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Wait for AOS to initialize
    setTimeout(() => {
        window.tradingDashboard = new TradingDashboard();
    }, 100);
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.tradingDashboard) {
        window.tradingDashboard.stopRealTimeUpdates();
    }
});
