<?php
include 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Get all subjects for the dropdown
    $subjectQuery = "SELECT id, CONCAT(subject, ' (', subject_code, ')') as subject_name FROM subjects ORDER BY subject_code";
    $subjects = $conn->query($subjectQuery);
    if (!$subjects) {
        throw new Exception("Failed to fetch subjects: " . $conn->error);
    }

    // Process analytics if form is submitted
    if (isset($_POST['generate']) && isset($_POST['subject_id'])) {
        $subject_id = (int)$_POST['subject_id'];

        // Get subject name for display
        $subjectNameQuery = "SELECT CONCAT(subject, ' (', subject_code, ')') as subject_name FROM subjects WHERE id = ?";
        $stmt = $conn->prepare($subjectNameQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare subject name query: " . $conn->error);
        }
        
        $stmt->bind_param("i", $subject_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute subject name query: " . $stmt->error);
        }
        
        $subjectResult = $stmt->get_result();
        if (!$subjectResult) {
            throw new Exception("Failed to get subject name result: " . $stmt->error);
        }
        
        $subjectRow = $subjectResult->fetch_assoc();
        if (!$subjectRow) {
            throw new Exception("No subject found with ID: " . $subject_id);
        }
        
        $subjectName = $subjectRow['subject_name'];

        // Get detailed performance data for selected subject across all classes
        $analysisQuery = "SELECT 
            c.level,
            c.section,
            COUNT(DISTINCT s.id) as total_students,
            ROUND(AVG(ri.mark), 2) as average_mark,
            MAX(ri.mark) as highest_mark,
            MIN(ri.mark) as lowest_mark,
            COUNT(CASE WHEN ri.mark >= 90 THEN 1 END) as excellent_count,
            COUNT(CASE WHEN ri.mark >= 75 AND ri.mark < 90 THEN 1 END) as good_count,
            COUNT(CASE WHEN ri.mark >= 60 AND ri.mark < 75 THEN 1 END) as average_count,
            COUNT(CASE WHEN ri.mark < 60 THEN 1 END) as below_average_count
        FROM classes c
        LEFT JOIN students s ON c.id = s.class_id
        LEFT JOIN results r ON s.id = r.student_id
        LEFT JOIN result_items ri ON r.id = ri.result_id
        WHERE ri.subject_id = ?
        GROUP BY c.id, c.level, c.section
        ORDER BY c.level, c.section";

        $stmt = $conn->prepare($analysisQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare analysis query: " . $conn->error);
        }

        $stmt->bind_param("i", $subject_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute analysis query: " . $stmt->error);
        }

        $analysis = $stmt->get_result();
        if (!$analysis) {
            throw new Exception("Failed to get analysis result: " . $stmt->error);
        }

        if ($analysis->num_rows === 0) {
            throw new Exception("No data found for the selected subject. Please check if there are any results recorded for this subject.");
        }

        // Get top performing students in this subject
        $topStudentsQuery = "SELECT 
            CONCAT(s.firstname, ' ', s.lastname) as student_name,
            c.level,
            c.section,
            ri.mark
        FROM students s
        JOIN classes c ON s.class_id = c.id
        JOIN results r ON s.id = r.student_id
        JOIN result_items ri ON r.id = ri.result_id
        WHERE ri.subject_id = ?
        ORDER BY ri.mark DESC
        LIMIT 5";

        $stmt = $conn->prepare($topStudentsQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare top students query: " . $conn->error);
        }

        $stmt->bind_param("i", $subject_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute top students query: " . $stmt->error);
        }

        $topStudents = $stmt->get_result();
        if (!$topStudents) {
            throw new Exception("Failed to get top students result: " . $stmt->error);
        }

        // Calculate overall statistics
        $classData = array();
        $totalStudents = 0;
        $totalMarks = 0;
        $highestMark = 0;
        $lowestMark = 100;
        $performanceData = array(
            'Excellent' => 0,
            'Good' => 0,
            'Average' => 0,
            'Below Average' => 0
        );

        while ($row = $analysis->fetch_assoc()) {
            $classData[] = $row;
            $totalStudents += $row['total_students'];
            $totalMarks += $row['average_mark'] * $row['total_students'];
            $highestMark = max($highestMark, $row['highest_mark']);
            $lowestMark = min($lowestMark, $row['lowest_mark']);
            $performanceData['Excellent'] += $row['excellent_count'];
            $performanceData['Good'] += $row['good_count'];
            $performanceData['Average'] += $row['average_count'];
            $performanceData['Below Average'] += $row['below_average_count'];
        }

        $overallAverage = $totalStudents > 0 ? round($totalMarks / $totalStudents, 2) : 0;

        // Reset result pointer for displaying class data
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
    <title>Subject Analytics Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background: #f0f2f5;
            color: var(--dark-text);
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--light-bg);
            border-radius: 10px;
        }

        .header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .header p {
            color: var(--light-text);
        }

        .selection-form {
            background: var(--light-bg);
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
            color: var(--primary-color);
        }

        .form-group select {
            width: 100%;
            max-width: 300px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group select:focus {
            border-color: var(--secondary-color);
            outline: none;
        }

        .submit-btn {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #2980b9;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .analytics-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
        }

        .analytics-card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            color: var(--light-text);
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .card-value {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
        }

        .performance-section {
            margin: 30px 0;
        }

        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .performance-card {
            padding: 20px;
            border-radius: 10px;
            color: white;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .performance-card.excellent {
            background: linear-gradient(135deg, var(--success-color), #27ae60);
        }

        .performance-card.good {
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
        }

        .performance-card.average {
            background: linear-gradient(135deg, var(--warning-color), #f39c12);
        }

        .performance-card.below-average {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }

        .chart-wrapper {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .chart-title {
            text-align: center;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .top-students {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin: 30px 0;
        }

        .top-students h2 {
            text-align: center;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .student-list {
            display: grid;
            gap: 15px;
        }

        .student-card {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s;
        }

        .student-card:hover {
            transform: translateX(5px);
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-weight: bold;
            color: var(--primary-color);
        }

        .student-class {
            color: var(--light-text);
            font-size: 0.9em;
        }

        .student-score {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--secondary-color);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background: var(--light-bg);
            font-weight: bold;
            color: var(--primary-color);
        }

        .data-table tr:hover {
            background: var(--light-bg);
        }

        .error-message {
            background: #fee;
            color: var(--danger-color);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }

            .data-table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Add loading indicator styles */
        .loading-indicator {
            background: rgba(255, 255, 255, 0.9);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            font-size: 1.2em;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Subject Analytics Dashboard</h1>
            <p>Select a subject to view detailed performance analytics</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="selection-form">
            <form id="analytics-form" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
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

        <?php if (isset($_POST['generate']) && isset($_POST['subject_id'])): ?>
            <div id="analytics-content">
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="card-title">Total Students</div>
                        <div class="card-value"><?php echo $totalStudents; ?></div>
                    </div>
                    <div class="analytics-card">
                        <div class="card-title">Overall Average</div>
                        <div class="card-value"><?php echo $overallAverage; ?>%</div>
                    </div>
                    <div class="analytics-card">
                        <div class="card-title">Highest Mark</div>
                        <div class="card-value"><?php echo $highestMark; ?>%</div>
                    </div>
                    <div class="analytics-card">
                        <div class="card-title">Lowest Mark</div>
                        <div class="card-value"><?php echo $lowestMark; ?>%</div>
                    </div>
                </div>

                <div class="performance-section">
                    <h2>Performance Distribution</h2>
                    <div class="performance-grid">
                        <div class="performance-card excellent">
                            <h3>Excellent</h3>
                            <div class="stat-value"><?php echo $performanceData['Excellent']; ?></div>
                            <div>(90% and above)</div>
                        </div>
                        <div class="performance-card good">
                            <h3>Good</h3>
                            <div class="stat-value"><?php echo $performanceData['Good']; ?></div>
                            <div>(75% - 89%)</div>
                        </div>
                        <div class="performance-card average">
                            <h3>Average</h3>
                            <div class="stat-value"><?php echo $performanceData['Average']; ?></div>
                            <div>(60% - 74%)</div>
                        </div>
                        <div class="performance-card below-average">
                            <h3>Below Average</h3>
                            <div class="stat-value"><?php echo $performanceData['Below Average']; ?></div>
                            <div>(Below 60%)</div>
                        </div>
                    </div>
                </div>

                <div class="charts-container">
                    <div class="chart-wrapper">
                        <h3 class="chart-title">Performance Distribution</h3>
                        <canvas id="performanceChart"></canvas>
                    </div>
                    <div class="chart-wrapper">
                        <h3 class="chart-title">Class-wise Average Marks</h3>
                        <canvas id="classAverageChart"></canvas>
                    </div>
                </div>

                <div class="top-students">
                    <h2>Top Performing Students</h2>
                    <div class="student-list">
                        <?php while ($student = $topStudents->fetch_assoc()): ?>
                            <div class="student-card">
                                <div class="student-info">
                                    <div class="student-name"><?php echo htmlspecialchars($student['student_name']); ?></div>
                                    <div class="student-class"><?php echo htmlspecialchars($student['level'] . ' - ' . $student['section']); ?></div>
                                </div>
                                <div class="student-score"><?php echo htmlspecialchars($student['mark']); ?>%</div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Total Students</th>
                            <th>Average Mark</th>
                            <th>Highest Mark</th>
                            <th>Lowest Mark</th>
                            <th>Excellent</th>
                            <th>Good</th>
                            <th>Average</th>
                            <th>Below Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $analysis->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['level'] . ' - ' . $row['section']); ?></td>
                                <td><?php echo htmlspecialchars($row['total_students']); ?></td>
                                <td><?php echo htmlspecialchars($row['average_mark']); ?>%</td>
                                <td><?php echo htmlspecialchars($row['highest_mark']); ?>%</td>
                                <td><?php echo htmlspecialchars($row['lowest_mark']); ?>%</td>
                                <td><?php echo htmlspecialchars($row['excellent_count']); ?></td>
                                <td><?php echo htmlspecialchars($row['good_count']); ?></td>
                                <td><?php echo htmlspecialchars($row['average_count']); ?></td>
                                <td><?php echo htmlspecialchars($row['below_average_count']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <script>
                // Performance Distribution Chart
                new Chart(document.getElementById("performanceChart"), {
                    type: "doughnut",
                    data: {
                        labels: ["Excellent", "Good", "Average", "Below Average"],
                        datasets: [{
                            data: [
                                <?php echo $performanceData['Excellent']; ?>,
                                <?php echo $performanceData['Good']; ?>,
                                <?php echo $performanceData['Average']; ?>,
                                <?php echo $performanceData['Below Average']; ?>
                            ],
                            backgroundColor: [
                                "#2ecc71",
                                "#3498db",
                                "#f1c40f",
                                "#e74c3c"
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: "bottom",
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        },
                        cutout: "70%"
                    }
                });

                // Class Average Chart
                new Chart(document.getElementById("classAverageChart"), {
                    type: "bar",
                    data: {
                        labels: <?php echo json_encode(array_map(function($row) { return $row['level'] . ' - ' . $row['section']; }, $classData)); ?>,
                        datasets: [{
                            label: "Average Mark",
                            data: <?php echo json_encode(array_map(function($row) { return $row['average_mark']; }, $classData)); ?>,
                            backgroundColor: "rgba(52, 152, 219, 0.8)",
                            borderRadius: 5,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                grid: {
                                    display: true,
                                    color: "rgba(0, 0, 0, 0.1)"
                                }
                            },
                            x: {
                                grid: {
                                    display: false
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

    <script>
        $(document).ready(function() {
            // Add a loading indicator
            $('<div class="loading-indicator" style="display: none; text-align: center; padding: 20px;">Loading...</div>').insertAfter('#analytics-form');
            
            $('#analytics-form').on('submit', function(e) {
                // Show loading indicator
                $('.loading-indicator').show();
                $('.submit-btn').prop('disabled', true).text('Loading...');
                
                // Let the form submit normally
                return true;
            });
        });
    </script>
</body>
</html> 