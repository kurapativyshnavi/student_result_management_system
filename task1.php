<?php
include 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get all classes for the dropdown
    $classQuery = "SELECT id, CONCAT(level, ' - ', section) as class_name FROM classes ORDER BY level, section";
    $classes = $conn->query($classQuery);
    if (!$classes) {
        throw new Exception("Failed to fetch classes: " . $conn->error);
    }

    // Get all subjects for the dropdown
    $subjectQuery = "SELECT id, CONCAT(subject, ' (', subject_code, ')') as subject_name FROM subjects ORDER BY subject_code";
    $subjects = $conn->query($subjectQuery);
    if (!$subjects) {
        throw new Exception("Failed to fetch subjects: " . $conn->error);
    }

    // Process analytics if form is submitted
    if (isset($_POST['generate']) && isset($_POST['class_id']) && isset($_POST['subject_id'])) {
        $class_id = (int)$_POST['class_id'];
        $subject_id = (int)$_POST['subject_id'];

        // Get detailed performance data for selected class and subject
        $analysisQuery = "SELECT 
            s.firstname,
            s.lastname,
            ri.mark,
            r.marks_percentage as overall_percentage,
            CASE 
                WHEN ri.mark >= 90 THEN 'Excellent'
                WHEN ri.mark >= 75 THEN 'Good'
                WHEN ri.mark >= 60 THEN 'Average'
                ELSE 'Below Average'
            END as performance_category
        FROM students s
        JOIN results r ON s.id = r.student_id
        JOIN result_items ri ON r.id = ri.result_id
        WHERE s.class_id = ? AND ri.subject_id = ?
        ORDER BY ri.mark DESC";

        $stmt = $conn->prepare($analysisQuery);
        $stmt->bind_param("ii", $class_id, $subject_id);
        $stmt->execute();
        $analysis = $stmt->get_result();

        if (!$analysis) {
            throw new Exception("Failed to fetch analysis data: " . $conn->error);
        }

        // Get class and subject names for display
        $classNameQuery = "SELECT CONCAT(level, ' - ', section) as class_name FROM classes WHERE id = ?";
        $stmt = $conn->prepare($classNameQuery);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $classResult = $stmt->get_result();
        $className = $classResult->fetch_assoc()['class_name'];

        $subjectNameQuery = "SELECT CONCAT(subject, ' (', subject_code, ')') as subject_name FROM subjects WHERE id = ?";
        $stmt = $conn->prepare($subjectNameQuery);
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $subjectResult = $stmt->get_result();
        $subjectName = $subjectResult->fetch_assoc()['subject_name'];

        // Calculate statistics
        $marks = array();
        $performanceData = array(
            'Excellent' => 0,
            'Good' => 0,
            'Average' => 0,
            'Below Average' => 0
        );

        while ($row = $analysis->fetch_assoc()) {
            $marks[] = (float)$row['mark'];
            $performanceData[$row['performance_category']]++;
        }

        $totalStudents = count($marks);
        $averageMark = $totalStudents > 0 ? round(array_sum($marks) / $totalStudents, 2) : 0;
        $highestMark = $totalStudents > 0 ? max($marks) : 0;
        $lowestMark = $totalStudents > 0 ? min($marks) : 0;

        // Reset result pointer for student list
        $analysis->data_seek(0);
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Analytics</title>
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

        .selection-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group select {
            width: 100%;
            max-width: 300px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .submit-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #2980b9;
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

        .student-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .student-table th,
        .student-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .student-table th {
            background: #e9ecef;
            font-weight: bold;
            color: #2c3e50;
        }

        .student-table tr:hover {
            background: #f5f5f5;
        }

        .performance-excellent { color: #27ae60; }
        .performance-good { color: #2980b9; }
        .performance-average { color: #f39c12; }
        .performance-below { color: #c0392b; }

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

            .student-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Performance Analytics</h1>
            <p>Select a class and subject to generate detailed analytics</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="selection-form">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="class_id">Select Class:</label>
                    <select name="class_id" id="class_id" required>
                        <option value="">Choose a class...</option>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject_id">Select Subject:</label>
                    <select name="subject_id" id="subject_id" required>
                        <option value="">Choose a subject...</option>
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo (isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" name="generate" class="submit-btn">Generate Analytics</button>
            </form>
        </div>

        <?php if (isset($_POST['generate']) && isset($analysis) && $analysis->num_rows > 0): ?>
            <h2 style="text-align: center; margin-bottom: 20px;">
                Analytics for <?php echo htmlspecialchars($className); ?> - <?php echo htmlspecialchars($subjectName); ?>
            </h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="stat-value"><?php echo $totalStudents; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Average Mark</h3>
                    <div class="stat-value"><?php echo $averageMark; ?>%</div>
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
                    <h2>Performance Distribution</h2>
                    <canvas id="performanceChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2>Mark Distribution</h2>
                    <canvas id="marksChart"></canvas>
                </div>
            </div>

            <div class="student-list">
                <h2>Student Performance List</h2>
                <table class="student-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Mark</th>
                            <th>Performance Category</th>
                            <th>Overall Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $analysis->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($student['mark']); ?>%</td>
                                <td class="performance-<?php echo strtolower(str_replace(' ', '', $student['performance_category'])); ?>">
                                    <?php echo htmlspecialchars($student['performance_category']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['overall_percentage']); ?>%</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <script>
                // Performance Distribution Chart
                new Chart(document.getElementById('performanceChart'), {
                    type: 'pie',
                    data: {
                        labels: ['Excellent', 'Good', 'Average', 'Below Average'],
                        datasets: [{
                            data: [
                                <?php echo $performanceData['Excellent']; ?>,
                                <?php echo $performanceData['Good']; ?>,
                                <?php echo $performanceData['Average']; ?>,
                                <?php echo $performanceData['Below Average']; ?>
                            ],
                            backgroundColor: [
                                'rgba(46, 204, 113, 0.8)',
                                'rgba(52, 152, 219, 0.8)',
                                'rgba(241, 196, 15, 0.8)',
                                'rgba(231, 76, 60, 0.8)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        }
                    }
                });

                // Marks Distribution Chart
                new Chart(document.getElementById('marksChart'), {
                    type: 'bar',
                    data: {
                        labels: ['90-100', '75-89', '60-74', 'Below 60'],
                        datasets: [{
                            label: 'Number of Students',
                            data: [
                                <?php echo $performanceData['Excellent']; ?>,
                                <?php echo $performanceData['Good']; ?>,
                                <?php echo $performanceData['Average']; ?>,
                                <?php echo $performanceData['Below Average']; ?>
                            ],
                            backgroundColor: [
                                'rgba(46, 204, 113, 0.8)',
                                'rgba(52, 152, 219, 0.8)',
                                'rgba(241, 196, 15, 0.8)',
                                'rgba(231, 76, 60, 0.8)'
                            ]
                        }]
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
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html> 