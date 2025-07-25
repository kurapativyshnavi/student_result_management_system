<?php
include 'db_connect.php';

if (!isset($_POST['class_id']) || !isset($_POST['subject_id'])) {
    echo "Please select both class and subject.";
    exit;
}

$class_id = $_POST['class_id'];
$subject_id = $_POST['subject_id'];

// Get class and subject details
$class_query = $conn->query("SELECT concat(level,' - ',section) as class FROM classes WHERE id = $class_id");
$class_name = $class_query->fetch_assoc()['class'];

$subject_query = $conn->query("SELECT subject FROM subjects WHERE id = $subject_id");
$subject_name = $subject_query->fetch_assoc()['subject'];

// Get student performance data with proper class filtering
$query = "SELECT 
    s.student_id,
    CONCAT(st.firstname, ' ', st.middlename) as student_name,
    s.mark,
    s.grade
FROM student_scores s 
JOIN students st ON s.student_id = st.id
JOIN results r ON r.student_id = st.id
WHERE r.class_id = $class_id 
AND s.subject_id = $subject_id
ORDER BY s.mark DESC";

$result = $conn->query($query);
if (!$result) {
    echo "Error in query: " . $conn->error;
    exit;
}

$data = array();
$grades = array('A+' => 0, 'A' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 'D' => 0, 'F' => 0);
$total_students = 0;
$total_marks = 0;

while($row = $result->fetch_assoc()) {
    $data[] = $row;
    $grades[$row['grade']]++;
    $total_students++;
    $total_marks += $row['mark'];
}

$average_mark = $total_students > 0 ? round($total_marks / $total_students, 2) : 0;
$pass_rate = $total_students > 0 ? round((array_sum(array_slice($grades, 0, -1)) / $total_students) * 100, 1) : 0;

// Prepare data for charts
$grade_labels = json_encode(array_keys($grades));
$grade_values = array_values($grades);
// Ensure all values are integers
$grade_values = array_map('intval', $grade_values);
$grade_data = json_encode($grade_values);

if($total_students == 0) {
    echo '
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="text-center">Analytics for '.$class_name.' - '.$subject_name.'</h3>
            <div class="alert alert-info mt-4 text-center">
                <i class="fas fa-info-circle mr-2"></i>
                No performance data available for this class and subject combination.
                <br><br>
                <small>Please ensure that:</small>
                <ul class="list-unstyled mb-0">
                    <li>• Students are enrolled in this class</li>
                    <li>• Scores have been entered for this subject</li>
                </ul>
            </div>
        </div>
    </div>';
    exit;
}
?>

<style>
body {
    background: url('assets/dist/img/report-bg.jpg') no-repeat center center fixed;
    background-size: cover;
}
</style>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="text-center" style="color:#3840de  ;font-weight: bold;">Analytics for <?php echo $class_name ?> - <?php echo $subject_name ?></h3>
        <p class="text-center text-muted">Showing performance data for <?php echo $total_students ?> students</p>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title m-4" style="color:#c8673a ;font-weight: bold;">Grade Distribution</h5>
                <canvas id="gradeChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title m-4" style="color:#c8673a ;font-weight: bold;">Performance Summary</h5>
                <table class="table table-hover">
                    <tr>
                        <th style="width: 50%">Total Students</th>
                        <td class="font-weight-bold"><?php echo $total_students ?></td>
                    </tr>
                    <tr>
                        <th>Average Mark</th>
                        <td class="font-weight-bold"><?php echo $average_mark ?>%</td>
                    </tr>
                    <tr>
                        <th>Highest Grade</th>
                        <td class="font-weight-bold"><?php 
                            $highest_grade = '';
                            foreach($grades as $grade => $count) {
                                if($count > 0) {
                                    $highest_grade = $grade;
                                    break;
                                }
                            }
                            echo $highest_grade ? $highest_grade : 'N/A';
                        ?></td>
                    </tr>
                    <tr>
                        <th>Pass Rate</th>
                        <td class="font-weight-bold"><?php echo $pass_rate ?>%</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title mb-4 pt-4" style="color:#b0ba31; font-weight: bold;">Student Performance Details</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Mark (%)</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data as $row): ?>
                            <tr>
                                <td><?php echo $row['student_name'] ?></td>
                                <td><?php echo $row['mark'] ?></td>
                                <td><span class="badge badge-<?php 
                                    echo $row['grade'] == 'F' ? 'danger' : 
                                        ($row['grade'] == 'D' ? 'warning' : 
                                        ($row['grade'] == 'A+' ? 'success' : 'primary')); 
                                ?>"><?php echo $row['grade'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uni_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body"></div>
    </div>
  </div>
</div>

<script>
// Create grade distribution chart (x-axis: grade, y-axis: number of students)
var ctx = document.getElementById('gradeChart').getContext('2d');
var gradeLabels = <?php echo $grade_labels; ?>;
var gradeData = <?php echo $grade_data; ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: gradeLabels,
        datasets: [{
            label: 'Number of Students',
            data: gradeData,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'Grade Distribution (X: Grade, Y: Number of Students)'
            }
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Grade'
                }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Students'
                },
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script> 