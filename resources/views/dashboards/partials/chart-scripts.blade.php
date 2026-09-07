@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        /**
         * Read a CSS custom property from the document root.
         * Falls back to a default if the property is not defined.
         */
        function cssVar(name, fallback) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
        }

        /**
         * Parse a comma-separated CSS variable into an array of colour strings.
         */
        function cssPalette(name, fallback) {
            const raw = cssVar(name, fallback);
            return raw.split(',').map(function (s) { return s.trim(); });
        }

        /**
         * Rebuild all registered Chart.js instances with current CSS variable values.
         */
        function refreshChartColors() {
            const palette = cssPalette('--shell-chart-palette', '#1b5e20, #2e7d32, #b45309, #6d28d9, #b91c1c, #047857');
            const chartBg = cssVar('--shell-chart-bg', 'rgba(30, 58, 138, 0.08)');
            const borderColor = cssVar('--shell-primary', '#1b5e20');
            const mutedText = cssVar('--shell-muted', '#475569');
            const subtleText = cssVar('--shell-subtle', '#64748b');
            const tooltipBg = cssVar('--shell-text', '#0f172a');

            Object.values(Chart.instances).forEach(function (chart) {
                if (!chart || !chart.data || !chart.data.datasets) return;
                chart.data.datasets.forEach(function (ds, i) {
                    ds.borderColor = borderColor;
                    ds.backgroundColor = chart.config.type === 'line' ? chartBg : palette;
                    ds.pointBackgroundColor = borderColor;
                });
                if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                    chart.options.plugins.legend.labels.color = mutedText;
                }
                if (chart.options.plugins && chart.options.plugins.tooltip) {
                    chart.options.plugins.tooltip.backgroundColor = tooltipBg;
                }
                if (chart.options.scales) {
                    if (chart.options.scales.x && chart.options.scales.x.ticks) {
                        chart.options.scales.x.ticks.color = subtleText;
                    }
                    if (chart.options.scales.y && chart.options.scales.y.ticks) {
                        chart.options.scales.y.ticks.color = subtleText;
                    }
                    if (chart.options.scales.y && chart.options.scales.y.grid) {
                        chart.options.scales.y.grid.color = 'rgba(148, 163, 184, 0.22)';
                    }
                }
                chart.update('none');
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const charts = @json($charts ?? []);
            const palette = cssPalette('--shell-chart-palette', '#1b5e20, #2e7d32, #b45309, #6d28d9, #b91c1c, #047857');
            const chartBg = cssVar('--shell-chart-bg', 'rgba(30, 58, 138, 0.08)');
            const borderColor = cssVar('--shell-primary', '#1b5e20');
            const mutedText = cssVar('--shell-muted', '#475569');
            const subtleText = cssVar('--shell-subtle', '#64748b');
            const tooltipBg = cssVar('--shell-text', '#0f172a');

            charts.forEach(function (chart) {
                const canvas = document.getElementById(chart.id);
                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                new Chart(canvas, {
                    type: chart.type || 'bar',
                    data: {
                        labels: chart.labels || [],
                        datasets: [{
                            label: chart.title,
                            data: chart.data || [],
                            borderColor: borderColor,
                            backgroundColor: chart.type === 'line' ? chartBg : palette,
                            borderWidth: 2,
                            pointRadius: chart.type === 'line' ? 3 : 0,
                            pointBackgroundColor: borderColor,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            tension: 0.35,
                            fill: chart.type === 'line'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: chart.type === 'doughnut',
                                labels: {
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    color: mutedText,
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: tooltipBg,
                                padding: 12,
                                titleFont: {
                                    size: 13,
                                    weight: '700'
                                },
                                bodyFont: {
                                    size: 12
                                }
                            }
                        },
                        scales: chart.type === 'doughnut' ? {} : {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: subtleText,
                                    font: {
                                        size: 12,
                                        weight: '400'
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.22)'
                                },
                                ticks: {
                                    precision: 0,
                                    color: subtleText,
                                    font: {
                                        size: 12,
                                        weight: '400'
                                    }
                                }
                            }
                        }
                    }
                });
            });

            // Recolour charts when theme preview changes
            document.addEventListener('theme-preview', function () {
                refreshChartColors();
            });
        });
    </script>
@endpush
