<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ChartService
{
    /**
     * Generate line chart data
     */
    public function generateLineChart(array $data, array $options = []): array
    {
        $defaults = [
            'label' => 'Data Series',
            'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
            'borderColor' => 'rgba(54, 162, 235, 1)',
            'borderWidth' => 2,
            'fill' => false,
            'tension' => 0.1,
        ];

        $chartOptions = array_merge($defaults, $options);

        return [
            'type' => 'line',
            'data' => [
                'labels' => array_keys($data),
                'datasets' => [[
                    'label' => $chartOptions['label'],
                    'data' => array_values($data),
                    'backgroundColor' => $chartOptions['backgroundColor'],
                    'borderColor' => $chartOptions['borderColor'],
                    'borderWidth' => $chartOptions['borderWidth'],
                    'fill' => $chartOptions['fill'],
                    'tension' => $chartOptions['tension'],
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => !empty($chartOptions['title']),
                        'text' => $chartOptions['title'] ?? '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate bar chart data
     */
    public function generateBarChart(array $data, array $options = []): array
    {
        $defaults = [
            'label' => 'Data Series',
            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
            'borderColor' => 'rgba(75, 192, 192, 1)',
            'borderWidth' => 1,
        ];

        $chartOptions = array_merge($defaults, $options);

        return [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($data),
                'datasets' => [[
                    'label' => $chartOptions['label'],
                    'data' => array_values($data),
                    'backgroundColor' => $chartOptions['backgroundColor'],
                    'borderColor' => $chartOptions['borderColor'],
                    'borderWidth' => $chartOptions['borderWidth'],
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => !empty($chartOptions['title']),
                        'text' => $chartOptions['title'] ?? '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate pie chart data
     */
    public function generatePieChart(array $data, array $options = []): array
    {
        $colors = [
            'rgba(255, 99, 132, 0.2)',
            'rgba(54, 162, 235, 0.2)',
            'rgba(255, 205, 86, 0.2)',
            'rgba(75, 192, 192, 0.2)',
            'rgba(153, 102, 255, 0.2)',
            'rgba(255, 159, 64, 0.2)',
            'rgba(199, 199, 199, 0.2)',
            'rgba(83, 102, 255, 0.2)',
        ];

        $borderColors = [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 205, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)',
            'rgba(199, 199, 199, 1)',
            'rgba(83, 102, 255, 1)',
        ];

        $defaults = [
            'label' => 'Data Distribution',
            'backgroundColor' => array_slice($colors, 0, count($data)),
            'borderColor' => array_slice($borderColors, 0, count($data)),
            'borderWidth' => 1,
        ];

        $chartOptions = array_merge($defaults, $options);

        return [
            'type' => 'pie',
            'data' => [
                'labels' => array_keys($data),
                'datasets' => [[
                    'label' => $chartOptions['label'],
                    'data' => array_values($data),
                    'backgroundColor' => $chartOptions['backgroundColor'],
                    'borderColor' => $chartOptions['borderColor'],
                    'borderWidth' => $chartOptions['borderWidth'],
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => !empty($chartOptions['title']),
                        'text' => $chartOptions['title'] ?? '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate doughnut chart data
     */
    public function generateDoughnutChart(array $data, array $options = []): array
    {
        $pieChart = $this->generatePieChart($data, $options);
        $pieChart['type'] = 'doughnut';
        
        return $pieChart;
    }

    /**
     * Generate multi-series line chart
     */
    public function generateMultiLineChart(array $series, array $labels, array $options = []): array
    {
        $colors = [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 205, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)',
        ];

        $datasets = [];
        $colorIndex = 0;

        foreach ($series as $seriesName => $seriesData) {
            $color = $colors[$colorIndex % count($colors)];
            $datasets[] = [
                'label' => $seriesName,
                'data' => $seriesData,
                'backgroundColor' => str_replace('1)', '0.2)', $color),
                'borderColor' => $color,
                'borderWidth' => 2,
                'fill' => false,
                'tension' => 0.1,
            ];
            $colorIndex++;
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => !empty($options['title']),
                        'text' => $options['title'] ?? '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate multi-series bar chart
     */
    public function generateMultiBarChart(array $series, array $labels, array $options = []): array
    {
        $colors = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 205, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
        ];

        $datasets = [];
        $colorIndex = 0;

        foreach ($series as $seriesName => $seriesData) {
            $color = $colors[$colorIndex % count($colors)];
            $datasets[] = [
                'label' => $seriesName,
                'data' => $seriesData,
                'backgroundColor' => $color,
                'borderColor' => str_replace('0.8)', '1)', $color),
                'borderWidth' => 1,
            ];
            $colorIndex++;
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => !empty($options['title']),
                        'text' => $options['title'] ?? '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate stacked bar chart
     */
    public function generateStackedBarChart(array $series, array $labels, array $options = []): array
    {
        $chart = $this->generateMultiBarChart($series, $labels, $options);
        
        $chart['options']['scales']['x'] = [
            'stacked' => true,
        ];
        $chart['options']['scales']['y'] = [
            'stacked' => true,
            'beginAtZero' => true,
        ];

        return $chart;
    }

    /**
     * Generate area chart (filled line chart)
     */
    public function generateAreaChart(array $data, array $options = []): array
    {
        $lineChart = $this->generateLineChart($data, $options);
        $lineChart['data']['datasets'][0]['fill'] = true;
        $lineChart['data']['datasets'][0]['backgroundColor'] = $options['backgroundColor'] ?? 'rgba(54, 162, 235, 0.3)';
        
        return $lineChart;
    }

    /**
     * Format data for time-based charts
     */
    public function formatTimeSeriesData(Collection $data, string $dateField, string $valueField, string $format = 'Y-m-d'): array
    {
        $formatted = [];
        
        foreach ($data as $item) {
            $date = date($format, strtotime($item[$dateField]));
            if (!isset($formatted[$date])) {
                $formatted[$date] = 0;
            }
            $formatted[$date] += $item[$valueField];
        }
        
        ksort($formatted);
        return $formatted;
    }

    /**
     * Generate attendance trend chart
     */
    public function generateAttendanceTrendChart(array $attendanceData, array $options = []): array
    {
        $defaultOptions = [
            'title' => 'Attendance Trends',
            'label' => 'Attendance Rate (%)',
            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
            'borderColor' => 'rgba(75, 192, 192, 1)',
        ];

        return $this->generateLineChart($attendanceData, array_merge($defaultOptions, $options));
    }

    /**
     * Generate grade distribution chart
     */
    public function generateGradeDistributionChart(array $gradeData, array $options = []): array
    {
        $defaultOptions = [
            'title' => 'Grade Distribution',
            'label' => 'Number of Students',
        ];

        return $this->generateBarChart($gradeData, array_merge($defaultOptions, $options));
    }

    /**
     * Generate performance comparison chart
     */
    public function generatePerformanceComparisonChart(array $subjects, array $classes, array $scores, array $options = []): array
    {
        $series = [];
        
        foreach ($classes as $class) {
            $series[$class] = $scores[$class] ?? array_fill(0, count($subjects), 0);
        }

        $defaultOptions = [
            'title' => 'Performance Comparison Across Classes',
        ];

        return $this->generateMultiBarChart($series, $subjects, array_merge($defaultOptions, $options));
    }

    /**
     * Generate calendar heat map data (for attendance patterns)
     */
    public function generateCalendarHeatMap(array $dailyData, array $options = []): array
    {
        // This would generate data suitable for a calendar heatmap
        // Format: array of objects with date and value properties
        $heatMapData = [];
        
        foreach ($dailyData as $date => $value) {
            $heatMapData[] = [
                'date' => $date,
                'value' => $value,
                'intensity' => $this->calculateIntensity($value, $options['max'] ?? 100),
            ];
        }

        return [
            'type' => 'calendar-heatmap',
            'data' => $heatMapData,
            'options' => array_merge([
                'colorScale' => ['#ebedf0', '#c6e48b', '#7bc96f', '#239a3b', '#196127'],
                'tooltip' => true,
                'responsive' => true,
            ], $options),
        ];
    }

    /**
     * Calculate intensity for heatmap
     */
    protected function calculateIntensity(float $value, float $max): int
    {
        if ($max == 0) {
            return 0;
        }
        
        $percentage = ($value / $max) * 100;
        
        if ($percentage >= 80) return 4;
        if ($percentage >= 60) return 3;
        if ($percentage >= 40) return 2;
        if ($percentage >= 20) return 1;
        
        return 0;
    }

    /**
     * Validate chart data
     */
    public function validateChartData(array $data, string $type = 'line'): bool
    {
        if (empty($data)) {
            return false;
        }

        switch ($type) {
            case 'line':
            case 'bar':
            case 'area':
                return is_array($data) && count($data) > 0;
            
            case 'pie':
            case 'doughnut':
                return is_array($data) && count($data) > 0 && count($data) <= 8; // Limit pie slices
            
            case 'multi-line':
            case 'multi-bar':
                return is_array($data) && count($data) > 0 && is_array(reset($data));
            
            default:
                return false;
        }
    }

    /**
     * Get chart configuration for specific report type
     */
    public function getReportChartConfig(string $reportType, array $data, array $options = []): array
    {
        return match ($reportType) {
            'attendance_daily' => $this->generateLineChart($data, [
                'title' => 'Daily Attendance',
                'label' => 'Attendance Count',
                'borderColor' => 'rgba(75, 192, 192, 1)',
            ]),
            
            'attendance_monthly' => $this->generateBarChart($data, [
                'title' => 'Monthly Attendance Summary',
                'label' => 'Average Attendance (%)',
                'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
            ]),
            
            'grade_distribution' => $this->generatePieChart($data, [
                'title' => 'Grade Distribution',
                'label' => 'Students by Grade',
            ]),
            
            'performance_trends' => $this->generateLineChart($data, [
                'title' => 'Academic Performance Trends',
                'label' => 'Average Score',
                'borderColor' => 'rgba(255, 99, 132, 1)',
            ]),
            
            'class_comparison' => $this->generateMultiBarChart($data['series'], $data['labels'], [
                'title' => 'Class Performance Comparison',
            ]),
            
            default => $this->generateLineChart($data, $options),
        };
    }
}