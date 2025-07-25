<?php
include 'db_connect.php';

// Read the SQL file
$sql = file_get_contents('create_student_scores_table.sql');

// Execute the SQL
if($conn->multi_query($sql)) {
    echo "Student scores table created successfully!\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
    exit;
}

// Wait for previous query to complete
while($conn->more_results()) {
    $conn->next_result();
}

// Get existing student IDs
$student_query = "SELECT id FROM students LIMIT 8";
$result = $conn->query($student_query);

if($result->num_rows == 0) {
    echo "No students found in the database. Please add students first.\n";
    exit;
}

$students = array();
while($row = $result->fetch_assoc()) {
    $students[] = $row['id'];
}

// Get first class ID
$class_query = "SELECT id FROM classes LIMIT 1";
$result = $conn->query($class_query);
if($result->num_rows == 0) {
    echo "No classes found in the database. Please add classes first.\n";
    exit;
}
$class_id = $result->fetch_assoc()['id'];

// Get first subject ID
$subject_query = "SELECT id FROM subjects LIMIT 1";
$result = $conn->query($subject_query);
if($result->num_rows == 0) {
    echo "No subjects found in the database. Please add subjects first.\n";
    exit;
}
$subject_id = $result->fetch_assoc()['id'];

// Prepare sample data using existing IDs
$grades = array('A+', 'A', 'B+', 'B', 'C+', 'C', 'D', 'F');
$marks = array(95.5, 87.0, 78.5, 72.0, 68.5, 62.0, 55.0, 45.0);

$values = array();
for($i = 0; $i < min(count($students), count($grades)); $i++) {
    $values[] = "({$students[$i]}, {$class_id}, {$subject_id}, {$marks[$i]}, '{$grades[$i]}')";
}

if(empty($values)) {
    echo "No data to insert.\n";
    exit;
}

$sample_data = "INSERT INTO student_scores (student_id, class_id, subject_id, mark, grade) VALUES " . 
    implode(",\n", $values);

// Insert sample data
if($conn->query($sample_data)) {
    echo "Sample data inserted successfully!\n";
} else {
    echo "Error inserting sample data: " . $conn->error . "\n";
}

$conn->close();
?> 