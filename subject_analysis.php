<?php
include 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Print POST data
echo "<!-- POST data: " . print_r($_POST, true) . " -->";

// Check if form was submitted
if (!isset($_POST['subject_id']) || !isset($_POST['generate'])) {
    echo "<!-- Form not submitted correctly -->";
    header('Location: subject_selection.php');
    exit;
}

try {
    $subject_id = (int)$_POST['subject_id'];
    
    // Debug: Print subject ID
    echo "<!-- Subject ID: " . $subject_id . " -->";
    
    // Get subject details
    $subjectQuery = "SELECT CONCAT(subject, ' (', subject_code, ')') as subject_name FROM subjects WHERE id = ?";
    $stmt = $conn->prepare($subjectQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare subject query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $subject_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute subject query: " . $stmt->error);
    }
    
    $subjectResult = $stmt->get_result();
    if (!$subjectResult) {
        throw new Exception("Failed to get subject result: " . $stmt->error);
    }
    
    $subjectRow = $subjectResult->fetch_assoc();
    if (!$subjectRow) {
        throw new Exception("No subject found with ID: " . $subject_id);
    }
    
    $subjectName = $subjectRow['subject_name'];
    
    // Debug: Print subject name
    echo "<!-- Subject Name: " . $subjectName . " -->";
    
    // Get performance data
    $query = "SELECT 
        c.level,
        c.section,
        COUNT(DISTINCT s.id) as total_students,
        ROUND(AVG(ri.mark), 2) as average_mark,
        MAX(ri.mark) as highest_mark,
        MIN(ri.mark) as lowest_mark,
        COUNT(CASE WHEN ri.mark >= 90 THEN 1 END) as excellent,
        COUNT(CASE WHEN ri.mark >= 75 AND ri.mark < 90 THEN 1 END) as good,
        COUNT(CASE WHEN ri.mark >= 60 AND ri.mark < 75 THEN 1 END) as average,
        COUNT(CASE WHEN ri.mark < 60 THEN 1 END) as below_average
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id
    LEFT JOIN results r ON s.id = r.student_id
    LEFT JOIN result_items ri ON r.id = ri.result_id
    WHERE ri.subject_id = ?
    GROUP BY c.id, c.level, c.section
    ORDER BY c.level, c.section";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare performance query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $subject_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute performance query: " . $stmt->error);
    }
    
    $results = $stmt->get_result();
    if (!$results) {
        throw new Exception("Failed to get performance results: " . $stmt->error);
    }
    
    // Debug: Print number of results
    echo "<!-- Number of results: " . $results->num_rows . " -->";
    
    // Get top students
    $topQuery = "SELECT 
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
    
    $stmt = $conn->prepare($topQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare top students query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $subject_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute top students query: " . $stmt->error);
    }
    
    $topStudents = $stmt->get_result();
    if (!$topStudents) {
        throw new Exception("Failed to get top students results: " . $stmt->error);
    }
    
    // Debug: Print number of top students
    echo "<!-- Number of top students: " . $topStudents->num_rows . " -->";
    
    // Calculate overall statistics
    $totalStudents = 0;
    $totalMarks = 0;
    $highestMark = 0;
    $lowestMark = 100;
    $excellent = 0;
    $good = 0;
    $average = 0;
    $belowAverage = 0;
    
    while ($row = $results->fetch_assoc()) {
        $totalStudents += $row['total_students'];
        $totalMarks += $row['average_mark'] * $row['total_students'];
        $highestMark = max($highestMark, $row['highest_mark']);
        $lowestMark = min($lowestMark, $row['lowest_mark']);
        $excellent += $row['excellent'];
        $good += $row['good'];
        $average += $row['average'];
        $belowAverage += $row['below_average'];
    }
    
    $overallAverage = $totalStudents > 0 ? round($totalMarks / $totalStudents, 2) : 0;
    
    // Debug: Print statistics
    echo "<!-- Statistics: Total Students: $totalStudents, Average: $overallAverage, Highest: $highestMark, Lowest: $lowestMark -->";
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in subject_analysis.php: " . $e->getMessage());
    
    // Display error message
    echo '<div style="background: #fee; color: #c00; padding: 20px; margin: 20px; border-radius: 5px;">';
    echo '<h2>Error Occurred</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Please try again or contact support if the problem persists.</p>';
    echo '</div>';
    
    // Add a back button
    echo '<div style="text-align: center; margin: 20px;">';
    echo '<a href="subject_selection.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Back to Subject Selection</a>';
    echo '</div>';
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Analysis - <?php echo htmlspecialchars($subjectName); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background: #f4f6f8;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }
        
        .top-students {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .student-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .student-card:last-child {
            border-bottom: none;
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-name {
            font-weight: bold;
        }
        
        .student-class {
            color: #666;
            font-size: 0.9em;
        }
        
        .student-score {
            font-size: 1.2em;
            font-weight: bold;
            color: #007bff;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="subject_selection.php" class="back-link">← Back to Subject Selection</a>
        
        <div class="header">
            <h1><?php echo htmlspecialchars($subjectName); ?> - Performance Analysis</h1>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div>Total Students</div>
                <div class="stat-value"><?php echo $totalStudents; ?></div>
            </div>
            <div class="stat-card">
                <div>Overall Average</div>
                <div class="stat-value"><?php echo $overallAverage; ?>%</div>
            </div>
            <div class="stat-card">
                <div>Highest Mark</div>
                <div class="stat-value"><?php echo $highestMark; ?>%</div>
            </div>
            <div class="stat-card">
                <div>Lowest Mark</div>
                <div class="stat-value"><?php echo $lowestMark; ?>%</div>
            </div>
        </div>
        
        <div class="top-students">
            <h3>Top Performing Students</h3>
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
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Students</th>
                    <th>Average</th>
                    <th>Highest</th>
                    <th>Lowest</th>
                    <th>Excellent</th>
                    <th>Good</th>
                    <th>Average</th>
                    <th>Below Avg</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $results->data_seek(0);
                while ($row = $results->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['level'] . ' - ' . $row['section']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_students']); ?></td>
                        <td><?php echo htmlspecialchars($row['average_mark']); ?>%</td>
                        <td><?php echo htmlspecialchars($row['highest_mark']); ?>%</td>
                        <td><?php echo htmlspecialchars($row['lowest_mark']); ?>%</td>
                        <td><?php echo htmlspecialchars($row['excellent']); ?></td>
                        <td><?php echo htmlspecialchars($row['good']); ?></td>
                        <td><?php echo htmlspecialchars($row['average']); ?></td>
                        <td><?php echo htmlspecialchars($row['below_average']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 