(function($) {
    'use strict';

    const HTK_Testing = {
        init: function() {
            this.bindEvents();
            this.initializeTabs();
            this.initializeCharts();
            this.loadTestHistory();
        },

        bindEvents: function() {
            // Tab Navigation
            $('.htk-tab-button').on('click', this.handleTabClick.bind(this));
            
            // Functional Testing
            $('#htk-run-functional-tests').on('click', this.handleRunTests.bind(this));
            
            // Usability Testing
            $('#htk-start-usability-test').on('click', this.handleUsabilityTest.bind(this));
            
            // Compatibility Testing
            $('#htk-check-compatibility').on('click', this.handleCompatibilityCheck.bind(this));
            
            // Feedback Form
            $('#htk-feedback-form').on('submit', this.handleFeedbackSubmit.bind(this));
        },

        initializeTabs: function() {
            // Show active tab content
            const activeTab = $('.htk-tab-button.active').data('tab');
            $(`#${activeTab}`).addClass('active');
        },

        initializeCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }

            // Initialize feedback trends chart
            const ctx = document.getElementById('htk-feedback-trends').getContext('2d');
            this.feedbackChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Bug Reports',
                            data: [],
                            borderColor: '#dc3232',
                            tension: 0.4
                        },
                        {
                            label: 'Feature Requests',
                            data: [],
                            borderColor: '#2271b1',
                            tension: 0.4
                        },
                        {
                            label: 'Improvements',
                            data: [],
                            borderColor: '#46b450',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            this.loadFeedbackTrends();
        },

        handleTabClick: function(e) {
            const $button = $(e.currentTarget);
            const tabId = $button.data('tab');

            // Update active states
            $('.htk-tab-button').removeClass('active');
            $('.htk-tab-content').removeClass('active');
            
            $button.addClass('active');
            $(`#${tabId}`).addClass('active');
        },

        handleRunTests: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $testItems = $('.htk-test-item');

            // Get selected tests
            const selectedTests = [];
            $('input[name="tests[]"]:checked').each(function() {
                selectedTests.push($(this).val());
            });

            if (selectedTests.length === 0) {
                this.showNotice('error', 'Please select at least one test to run.');
                return;
            }

            $button.addClass('htk-loading');
            $testItems.find('.htk-test-status').addClass('running');

            $.ajax({
                url: htk_testing.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_run_tests',
                    nonce: htk_testing.nonce,
                    tests: selectedTests
                },
                success: (response) => {
                    $button.removeClass('htk-loading');
                    $testItems.find('.htk-test-status').removeClass('running');

                    if (response.success) {
                        this.updateTestResults(response.data);
                    } else {
                        this.showNotice('error', 'Failed to run tests.');
                    }
                },
                error: () => {
                    $button.removeClass('htk-loading');
                    $testItems.find('.htk-test-status').removeClass('running');
                    this.showNotice('error', 'An error occurred while running tests.');
                }
            });
        },

        updateTestResults: function(results) {
            const $resultsContainer = $('.htk-results-container');
            $resultsContainer.empty();

            Object.entries(results).forEach(([test, result]) => {
                // Update test status indicator
                const $testItem = $(`.htk-test-item input[value="${test}"]`).closest('.htk-test-item');
                const $status = $testItem.find('.htk-test-status');
                
                $status.removeClass('success error warning')
                       .addClass(result.status);

                // Add result to results container
                const $resultItem = $('<div>')
                    .addClass(`htk-result-item ${result.status}`)
                    .append(
                        $('<div>')
                            .addClass('htk-result-title')
                            .text(this.getTestLabel(test))
                    )
                    .append(
                        $('<div>')
                            .addClass('htk-result-message')
                            .text(result.message)
                    );

                $resultsContainer.append($resultItem);
            });
        },

        getTestLabel: function(test) {
            const $testInput = $(`.htk-test-item input[value="${test}"]`);
            return $testInput.siblings('.htk-checkbox-label').text();
        },

        handleUsabilityTest: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);

            $button.addClass('htk-loading');

            // Simulate starting a new usability test session
            const testConfig = {
                tasks: [
                    'Create a new hackathon',
                    'Add participants to the hackathon',
                    'Create and assign tasks',
                    'Submit project feedback'
                ],
                metrics: [
                    'time_on_task',
                    'error_rate',
                    'completion_rate',
                    'satisfaction_score'
                ]
            };

            // In a real implementation, this would start a test session
            setTimeout(() => {
                $button.removeClass('htk-loading');
                this.showNotice('success', 'Usability test session started.');
                this.initializeUsabilityTest(testConfig);
            }, 1000);
        },

        initializeUsabilityTest: function(config) {
            // Implementation would depend on specific usability testing requirements
            console.log('Usability test initialized with config:', config);
        },

        handleCompatibilityCheck: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $indicators = $('.htk-status-indicator');

            $button.addClass('htk-loading');
            $indicators.removeClass('compatible incompatible partial');

            $.ajax({
                url: htk_testing.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_check_compatibility',
                    nonce: htk_testing.nonce
                },
                success: (response) => {
                    $button.removeClass('htk-loading');
                    if (response.success) {
                        this.updateCompatibilityMatrix(response.data);
                    } else {
                        this.showNotice('error', 'Failed to check compatibility.');
                    }
                },
                error: () => {
                    $button.removeClass('htk-loading');
                    this.showNotice('error', 'An error occurred while checking compatibility.');
                }
            });
        },

        updateCompatibilityMatrix: function(results) {
            Object.entries(results).forEach(([key, status]) => {
                const [environment, version] = key.split('_');
                $(`.htk-compatibility-status[data-environment="${environment}"][data-version="${version}"] .htk-status-indicator`)
                    .addClass(status);
            });
        },

        handleFeedbackSubmit: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $submitButton = $form.find('button[type="submit"]');

            $submitButton.addClass('htk-loading');

            const formData = new FormData($form[0]);
            formData.append('action', 'htk_save_feedback');
            formData.append('nonce', htk_testing.nonce);

            $.ajax({
                url: htk_testing.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    $submitButton.removeClass('htk-loading');
                    if (response.success) {
                        this.showNotice('success', 'Feedback submitted successfully.');
                        $form[0].reset();
                        this.loadFeedbackTrends();
                    } else {
                        this.showNotice('error', 'Failed to submit feedback.');
                    }
                },
                error: () => {
                    $submitButton.removeClass('htk-loading');
                    this.showNotice('error', 'An error occurred while submitting feedback.');
                }
            });
        },

        loadTestHistory: function() {
            $.ajax({
                url: htk_testing.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_get_test_history',
                    nonce: htk_testing.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayTestHistory(response.data);
                    }
                }
            });
        },

        displayTestHistory: function(history) {
            const $container = $('.htk-test-history');
            if (!$container.length || !history.length) {
                return;
            }

            const $table = $('<table>').addClass('htk-history-table');
            const $thead = $('<thead>').append(
                $('<tr>').append(
                    $('<th>').text('Date'),
                    $('<th>').text('Tests Run'),
                    $('<th>').text('Success Rate'),
                    $('<th>').text('Details')
                )
            );

            const $tbody = $('<tbody>');
            history.forEach(entry => {
                const successRate = this.calculateSuccessRate(entry.results);
                const $row = $('<tr>').append(
                    $('<td>').text(this.formatDate(entry.timestamp)),
                    $('<td>').text(Object.keys(entry.results).length),
                    $('<td>').text(`${successRate}%`),
                    $('<td>').append(
                        $('<button>')
                            .addClass('htk-button htk-button-secondary')
                            .text('View Details')
                            .on('click', () => this.showTestDetails(entry))
                    )
                );
                $tbody.append($row);
            });

            $table.append($thead, $tbody);
            $container.html($table);
        },

        calculateSuccessRate: function(results) {
            const total = Object.keys(results).length;
            if (total === 0) return 0;

            const successful = Object.values(results).filter(r => r.status === 'success').length;
            return Math.round((successful / total) * 100);
        },

        formatDate: function(timestamp) {
            return new Date(timestamp).toLocaleString();
        },

        showTestDetails: function(entry) {
            // Implementation would show a modal with detailed test results
            console.log('Test details:', entry);
        },

        loadFeedbackTrends: function() {
            $.ajax({
                url: htk_testing.ajax_url,
                type: 'POST',
                data: {
                    action: 'htk_get_feedback_trends',
                    nonce: htk_testing.nonce
                },
                success: (response) => {
                    if (response.success && this.feedbackChart) {
                        this.updateFeedbackChart(response.data);
                    }
                }
            });
        },

        updateFeedbackChart: function(data) {
            this.feedbackChart.data.labels = data.labels;
            this.feedbackChart.data.datasets.forEach((dataset, index) => {
                dataset.data = data.datasets[index].data;
            });
            this.feedbackChart.update();
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
        }
    };

    $(document).ready(() => {
        HTK_Testing.init();
    });
})(jQuery); 