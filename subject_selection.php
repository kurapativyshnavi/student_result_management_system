<?php
include 'db_connect.php';

// Get all subjects for the dropdown
$subjectQuery = "SELECT id, CONCAT(subject, ' (', subject_code, ')') as subject_name FROM subjects ORDER BY subject_code";
$subjects = $conn->query($subjectQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Subject for Analytics</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background: #f4f6f8;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
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
        }
        
        select, button {
            width: 100%;
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
        }
        
        button:hover {
            background: #0056b3;
        }
        
        .visuals-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .visuals-link:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Subject Analytics</h1>
            <p>Select a subject to view detailed performance analysis</p>
        </div>
        
        <div class="form-container">
            <form action="subject_analysis.php" method="POST" onsubmit="return validateForm()">
                <select name="subject_id" id="subject_id" required>
                    <option value="">Choose a subject...</option>
                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                        <option value="<?php echo $subject['id']; ?>">
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="generate">Generate Analytics</button>
            </form>
        </div>
        
        <a href="subject_selection.php" class="visuals-link">View Visuals</a>
    </div>

    <script>
        function validateForm() {
            var subjectId = document.getElementById('subject_id').value;
            if (subjectId === "") {
                alert("Please select a subject");
                return false;
            }
            return true;
        }
    </script>
</body>
</html> 