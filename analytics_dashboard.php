<?php
include 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get all subjects for the dropdown
$subjectQuery = "SELECT id, CONCAT(subject, ' (', subject_code, ')') as subject_name FROM subjects ORDER BY subject_code";
$subjects = $conn->query($subjectQuery);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_id'])) {
    $subject_id = (int)$_POST['subject_id'];
    
    // Get subject details
    $subjectQuery = "SELECT CONCAT(subject, ' (', subject_code, ')') as subject_name FROM subjects WHERE id = ?";
    $stmt = $conn->prepare($subjectQuery);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $subjectResult = $stmt->get_result();
    $subjectName = $subjectResult->fetch_assoc()['subject_name'];
    
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
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $results = $stmt->get_result();
    
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
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $topStudents = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Analytics Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        select, button {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            padding: 10px 20px;
        }
        
        button:hover {
            background: #0056b3;
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
        
        .error-message {
            background: #fee;
            color: #c00;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .navigation {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .navigation ul {
            list-style: none;
        }
        
        .navigation li {
            margin-bottom: 10px;
        }
        
        .navigation a {
            color: #007bff;
            text-decoration: none;
            font-size: 16px;
        }
        
        .navigation a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Subject Analytics Dashboard</h1>
            <p>Select a subject to view performance analytics</p>
        </div>
        
        <div class="navigation">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="students.php">Students</a></li>
                <li><a href="classes.php">Classes</a></li>
                <li><a href="subjects.php">Subjects</a></li>
                <li><a href="results.php">Results</a></li>
            </ul>
        </div>
        
        <div class="form-container">
            <form method="POST">
                <select name="subject_id" required>
                    <option value="">Select a subject...</option>
                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                        <option value="<?php echo $subject['id']; ?>" <?php echo (isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Generate Analytics</button>
            </form>
        </div>
        
        <?php if (isset($subjectName)): ?>
            <h2 style="text-align: center; margin-bottom: 20px;"><?php echo htmlspecialchars($subjectName); ?></h2>
            
            <div class="stats-grid">
                <?php
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
                ?>
                
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
        <?php endif; ?>
    </div>
</body>
</html> 