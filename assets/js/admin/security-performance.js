(function($) {
    'use strict';

    const HTK_Security_Performance = {
        init: function() {
            this.bindEvents();
            this.initializeCharts();
            this.loadPerformanceMetrics();
            this.startAutoRefresh();
        },

        bindEvents: function() {
            // Security Options Form
            $('#htk-security-options-form').on('submit', this.handleSecurityOptionsSubmit.bind(this));
            
            // Cache Management
            $('#htk-clear-cache').on('click', this.handleClearCache.bind(this));
            
            // Security Scan
            $('#htk-run-security-scan').on('click', this.handleSecurityScan.bind(this));
            
            // Performance Actions
            $('#htk-optimize-database').on('click', this.handleDatabaseOptimization.bind(this));
            $('#htk-optimize-assets').on('click', this.handleAssetOptimization.bind(this));
            
            // Real-time Monitoring Toggle
            $('#htk-toggle-monitoring').on('change', this.handleMonitoringToggle.bind(this));
        },

        initializeCharts: function() {
            // Initialize performance metrics charts if Chart.js is available
            if (typeof Chart !== 'undefined') {
                this.initializeMemoryChart();
                this.initializeQueryChart();
                this.initializeLoadTimeChart();
            }
        },

        initializeMemoryChart: function() {
            const ctx = document.getElementById('htk-memory-usage-chart').getContext('2d');
            this.memoryChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Memory Usage (MB)',
                        data: [],
                        borderColor: '#2271b1',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        initializeQueryChart: function() {
            const ctx = document.getElementById('htk-query-performance-chart').getContext('2d');
            this.queryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Query Time (ms)',
                        data: [],
                        backgroundColor: '#2271b1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        },

        initializeLoadTimeChart: function() {
            const ctx = document.getElementById('htk-load-time-chart').getContext('2d');
            this.loadTimeChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Page Load Time (s)',
                        data: [],
                        borderColor: '#2271b1',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        },

        loadPerformanceMetrics: function() {
            $.ajax({
                url: htk_security.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_get_performance_metrics',
                    nonce: htk_security.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateMetrics(response.data);
                    }
                }
            });
        },

        updateMetrics: function(data) {
            // Update memory usage chart
            if (this.memoryChart) {
                this.memoryChart.data.labels = data.memory.labels;
                this.memoryChart.data.datasets[0].data = data.memory.values;
                this.memoryChart.update();
            }

            // Update query performance chart
            if (this.queryChart) {
                this.queryChart.data.labels = data.queries.labels;
                this.queryChart.data.datasets[0].data = data.queries.values;
                this.queryChart.update();
            }

            // Update load time chart
            if (this.loadTimeChart) {
                this.loadTimeChart.data.labels = data.loadTime.labels;
                this.loadTimeChart.data.datasets[0].data = data.loadTime.values;
                this.loadTimeChart.update();
            }

            // Update metric cards
            $('.htk-metric-value[data-metric="memory"]').text(data.currentMemory + ' MB');
            $('.htk-metric-value[data-metric="queries"]').text(data.currentQueries);
            $('.htk-metric-value[data-metric="load-time"]').text(data.currentLoadTime + ' s');
        },

        handleSecurityOptionsSubmit: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $submitButton = $form.find('button[type="submit"]');

            $submitButton.addClass('htk-loading');

            $.ajax({
                url: htk_security.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_save_security_options',
                    nonce: htk_security.nonce,
                    options: $form.serialize()
                },
                success: (response) => {
                    $submitButton.removeClass('htk-loading');
                    if (response.success) {
                        this.showNotice('success', 'Security options saved successfully.');
                    } else {
                        this.showNotice('error', 'Failed to save security options.');
                    }
                },
                error: () => {
                    $submitButton.removeClass('htk-loading');
                    this.showNotice('error', 'An error occurred while saving security options.');
                }
            });
        },

        handleClearCache: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);

            $button.addClass('htk-loading');

            $.ajax({
                url: htk_security.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_clear_cache',
                    nonce: htk_security.nonce
                },
                success: (response) => {
                    $button.removeClass('htk-loading');
                    if (response.success) {
                        this.showNotice('success', 'Cache cleared successfully.');
                    } else {
                        this.showNotice('error', 'Failed to clear cache.');
                    }
                },
                error: () => {
                    $button.removeClass('htk-loading');
                    this.showNotice('error', 'An error occurred while clearing cache.');
                }
            });
        },

        handleSecurityScan: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $results = $('.htk-scan-results');

            $button.addClass('htk-loading');
            $results.html('<div class="htk-loading">Running security scan...</div>');

            $.ajax({
                url: htk_security.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_run_security_scan',
                    nonce: htk_security.nonce
                },
                success: (response) => {
                    $button.removeClass('htk-loading');
                    if (response.success) {
                        this.updateScanResults(response.data);
                    } else {
                        $results.html('<div class="htk-issue error">Failed to complete security scan.</div>');
                    }
                },
                error: () => {
                    $button.removeClass('htk-loading');
                    $results.html('<div class="htk-issue error">An error occurred during the security scan.</div>');
                }
            });
        },

        updateScanResults: function(results) {
            const $results = $('.htk-scan-results');
            $results.empty();

            if (results.length === 0) {
                $results.html('<div class="htk-issue success">No security issues found.</div>');
                return;
            }

            results.forEach(issue => {
                const $issue = $('<div>')
                    .addClass(`htk-issue ${issue.severity}`)
                    .append(
                        $('<div>')
                            .addClass('htk-issue-title')
                            .text(issue.title)
                    )
                    .append(
                        $('<div>')
                            .addClass('htk-issue-description')
                            .text(issue.description)
                    );

                if (issue.action) {
                    $issue.append(
                        $('<button>')
                            .addClass('htk-button htk-button-secondary')
                            .text(issue.action.label)
                            .on('click', () => this.handleIssueAction(issue.action))
                    );
                }

                $results.append($issue);
            });
        },

        handleDatabaseOptimization: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);

            $button.addClass('htk-loading');

            $.ajax({
                url: htk_security.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_optimize_database',
                    nonce: htk_security.nonce
                },
                success: (response) => {
                    $button.removeClass('htk-loading');
                    if (response.success) {
                        this.showNotice('success', 'Database optimized successfully.');
                        this.loadPerformanceMetrics();
                    } else {
                        this.showNotice('error', 'Failed to optimize database.');
                    }
                },
                error: () => {
                    $button.removeClass('htk-loading');
                    this.showNotice('error', 'An error occurred while optimizing database.');
                }
            });
        },

        handleAssetOptimization: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);

            $button.addClass('htk-loading');

            $.ajax({
                url: htk_security.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_optimize_assets',
                    nonce: htk_security.nonce
                },
                success: (response) => {
                    $button.removeClass('htk-loading');
                    if (response.success) {
                        this.showNotice('success', 'Assets optimized successfully.');
                        this.loadPerformanceMetrics();
                    } else {
                        this.showNotice('error', 'Failed to optimize assets.');
                    }
                },
                error: () => {
                    $button.removeClass('htk-loading');
                    this.showNotice('error', 'An error occurred while optimizing assets.');
                }
            });
        },

        handleMonitoringToggle: function(e) {
            const isEnabled = $(e.currentTarget).is(':checked');
            
            if (isEnabled) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }

            $.ajax({
                url: htk_security.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_toggle_monitoring',
                    nonce: htk_security.nonce,
                    enabled: isEnabled
                }
            });
        },

        startAutoRefresh: function() {
            this.refreshInterval = setInterval(() => {
                this.loadPerformanceMetrics();
            }, 30000); // Refresh every 30 seconds
        },

        stopAutoRefresh: function() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        },

        showNotice: function(type, message) {
            const $notice = $('<div>')
                .addClass(`notice notice-${type} is-dismissible`)
                .append($('<p>').text(message));

            $('.wrap > h1').after($notice);

            // Initialize WordPress dismissible notices
            if (window.wp && window.wp.notices) {
                window.wp.notices.initialize();
            }
        },

        handleIssueAction: function(action) {
            $.ajax({
                url: htk_security.ajax_url,
                type: 'POST',
                data: {
                    action: action.handler,
                    nonce: htk_security.nonce,
                    issue_data: action.data
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', 'Action completed successfully.');
                        this.handleSecurityScan({ preventDefault: () => {} });
                    } else {
                        this.showNotice('error', 'Failed to complete action.');
                    }
                },
                error: () => {
                    this.showNotice('error', 'An error occurred while performing the action.');
                }
            });
        }
    };

    $(document).ready(() => {
        HTK_Security_Performance.init();
    });
})(jQuery); 