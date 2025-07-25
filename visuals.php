<?php
// Maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug function
function debug($message, $data = null) {
    echo "<div style='background: #f8f9fa; padding: 10px; margin: 5px; border: 1px solid #ddd;'>";
    echo "<strong>Debug:</strong> " . htmlspecialchars($message);
    if ($data !== null) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    echo "</div>";
}

try {
    // Check if db_connect.php exists
    if (!file_exists('db_connect.php')) {
        throw new Exception("Database connection file (db_connect.php) not found!");
    }

    // Include database connection
    include 'db_connect.php';

    // Verify database connection
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed: " . (isset($conn) ? mysqli_connect_error() : "Connection variable not set"));
    }

    debug("Database connection successful");

    // Check if required tables exist
    $tables_query = "SHOW TABLES LIKE 'subjects'";
    $subjects_exist = $conn->query($tables_query)->num_rows > 0;
    
    $tables_query = "SHOW TABLES LIKE 'result_items'";
    $results_exist = $conn->query($tables_query)->num_rows > 0;
    
    if (!$subjects_exist || !$results_exist) {
        throw new Exception("Required tables not found. Subjects table exists: " . ($subjects_exist ? 'Yes' : 'No') . 
                          ", Result_items table exists: " . ($results_exist ? 'Yes' : 'No'));
    }

    debug("Required tables exist");

    // Updated query to match the actual database schema
    $query = "SELECT 
        s.subject as subject_name,
        COUNT(DISTINCT r.student_id) as total_students,
        ROUND(AVG(ri.mark), 2) as average_mark,
        MAX(ri.mark) as highest_mark,
        MIN(ri.mark) as lowest_mark
    FROM subjects s
    LEFT JOIN result_items ri ON s.id = ri.subject_id
    LEFT JOIN results r ON ri.result_id = r.id
    GROUP BY s.id, s.subject";

    debug("Executing query", $query);

    $result = $conn->query($query);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error . "\nQuery: " . $query);
    }

    debug("Query executed successfully");

    $data = array(
        'labels' => array(),
        'students' => array(),
        'averages' => array(),
        'highest' => array(),
        'lowest' => array()
    );

    if ($result->num_rows == 0) {
        debug("No results found in the database");
        throw new Exception("No results found in the database. Please make sure you have data in the subjects and result_items tables.");
    }

    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['subject_name'];
        $data['students'][] = (int)$row['total_students'];
        $data['averages'][] = (float)$row['average_mark'];
        $data['highest'][] = (float)$row['highest_mark'];
        $data['lowest'][] = (float)$row['lowest_mark'];
    }

    debug("Data processed", $data);

    // Calculate overall statistics
    $totalStudents = array_sum($data['students']);
    $overallAverage = !empty($data['averages']) ? round(array_sum($data['averages']) / count($data['averages']), 2) : 0;
    $highestMark = !empty($data['highest']) ? max($data['highest']) : 0;
    $lowestMark = !empty($data['lowest']) ? min($data['lowest']) : 0;

    debug("Statistics calculated", array(
        'totalStudents' => $totalStudents,
        'overallAverage' => $overallAverage,
        'highestMark' => $highestMark,
        'lowestMark' => $lowestMark
    ));

} catch (Exception $e) {
    $error = $e->getMessage();
    debug("Error occurred", $error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Performance Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background: #f0f2f5;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            min-height: 400px;
        }

        .chart-title {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 15px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .error-message {
            background: #fee;
            color: #c0392b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
            
            .stat-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Academic Performance Analytics</h1>
            <p>Comprehensive analysis of student performance across subjects</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="stat-value"><?php echo $totalStudents; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Overall Average</h3>
                    <div class="stat-value"><?php echo $overallAverage; ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>Highest Mark</h3>
                    <div class="stat-value"><?php echo $highestMark; ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>Lowest Mark</h3>
                    <div class="stat-value"><?php echo $lowestMark; ?>%</div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-container">
                    <h2 class="chart-title">Average Marks by Subject</h2>
                    <canvas id="averageMarksChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2 class="chart-title">Student Distribution</h2>
                    <canvas id="studentDistributionChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2 class="chart-title">Performance Range by Subject</h2>
                    <canvas id="performanceRangeChart"></canvas>
                </div>
            </div>

            <script>
                // Data from PHP
                const chartData = {
                    labels: <?php echo json_encode($data['labels']); ?>,
                    students: <?php echo json_encode($data['students']); ?>,
                    averages: <?php echo json_encode($data['averages']); ?>,
                    highest: <?php echo json_encode($data['highest']); ?>,
                    lowest: <?php echo json_encode($data['lowest']); ?>
                };

                // Common chart options
                const commonOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                };

                // Average Marks Chart
                new Chart(document.getElementById('averageMarksChart'), {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Average Mark (%)',
                            data: chartData.averages,
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });

                // Student Distribution Chart
                new Chart(document.getElementById('studentDistributionChart'), {
                    type: 'pie',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            data: chartData.students,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.8)',
                                'rgba(54, 162, 235, 0.8)',
                                'rgba(255, 206, 86, 0.8)',
                                'rgba(75, 192, 192, 0.8)',
                                'rgba(153, 102, 255, 0.8)'
                            ]
                        }]
                    },
                    options: commonOptions
                });

                // Performance Range Chart
                new Chart(document.getElementById('performanceRangeChart'), {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [
                            {
                                label: 'Highest Mark',
                                data: chartData.highest,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                fill: false
                            },
                            {
                                label: 'Average Mark',
                                data: chartData.averages,
                                borderColor: 'rgba(54, 162, 235, 1)',
                                fill: false
                            },
                            {
                                label: 'Lowest Mark',
                                data: chartData.lowest,
                                borderColor: 'rgba(255, 99, 132, 1)',
                                fill: false
                            }
                        ]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>