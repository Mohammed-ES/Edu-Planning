function initProgressChart() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not available, retrying...');
                setTimeout(initProgressChart, 500);
                return;
            }

            const progressChartCanvas = document.getElementById('progressChart');
            if (progressChartCanvas) {
                const avgProgress       = typeof DASHBOARD_AVG_PROGRESS !== 'undefined' ? DASHBOARD_AVG_PROGRESS : 0;
                const remainingProgress = Math.max(0, 100 - avgProgress);

                new Chart(progressChartCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Progress %', 'Remaining %'],
                        datasets: [{
                            data: [avgProgress, remainingProgress],
                            backgroundColor: ['#D4AF37', '#6B4423'],
                            borderColor: '#FFFFFF',
                            borderWidth: 3,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: { family: "'Inter', sans-serif", size: 13, weight: '500' },
                                    color: '#333333',
                                    padding: 18,
                                    usePointStyle: true,
                                    pointStyleWidth: 10
                                }
                            }
                        },
                        animation: { animateRotate: true, duration: 1200 }
                    }
                });
            }
        }

        // Initialize chart when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initProgressChart);
        } else {
            initProgressChart();
        }