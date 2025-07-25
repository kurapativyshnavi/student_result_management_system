<?php
if(!isset($conn)){ include 'db_connect.php'; }
?>

<style>
.analytics-container {
    min-height: 85vh;
    padding: 20px;
    background: #fff;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    border-radius: 8px;
    margin: 20px;
}

.card {
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
    border: none;
    margin-bottom: 20px;
}

.card-body {
    padding: 25px;
}

.form-control {
    height: 45px;
}

.btn-primary {
    height: 45px;
    font-size: 16px;
}

.table th {
    background-color: #f8f9fa;
}
</style>

<div class="analytics-container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4" style="color:#3ac4be ;">Performance Analytics</h2>
            <p class="text-center text-muted mb-4">Select a class and subject to generate detailed analytics</p>
            
            <form id="analytics-form" class="form-horizontal">
                <div class="form-group row justify-content-center">
                    <div class="col-md-5">
                        <label for="class_id" class="font-weight-bold">SELECT CLASS:</label>
                        <select class="form-control form-control-lg" name="class_id" id="class_id" required>
                            <option value="">Select Class</option>
                            <?php 
                            $classes = $conn->query("SELECT id,concat(level,' - ',section) as class FROM classes");
                            while($row = $classes->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id'] ?>"><?php echo $row['class'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="subject_id" class="font-weight-bold">SELECT SUBJECT:</label>
                        <select class="form-control form-control-lg" name="subject_id" id="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php 
                            $subjects = $conn->query("SELECT * FROM subjects");
                            while($row = $subjects->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id'] ?>"><?php echo $row['subject'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group row justify-content-center mt-4">
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-block">Generate Analytics</button>
                    </div>
                </div>
            </form>

            <div id="error-message" class="alert alert-danger mt-4" style="display: none;"></div>
            <div id="analytics-content" class="mt-4"></div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#analytics-form').submit(function(e){
        e.preventDefault();
        
        var formData = $(this).serialize();
        $('#error-message').hide();
        $('#analytics-content').html('<div class="text-center mt-5"><i class="fas fa-spinner fa-spin fa-3x"></i><br><p class="mt-3">Generating analytics...</p></div>');
        
        $.ajax({
            url: 'generate_analytics.php',
            method: 'POST',
            data: formData,
            success: function(response){
                try {
                    $('#analytics-content').html(response);
                } catch(e) {
                    $('#error-message').html('Error processing the analytics data.').show();
                }
            },
            error: function(){
                $('#error-message').html('Failed to generate analytics. Please try again.').show();
                $('#analytics-content').html('');
            }
        });
    });
});
</script> 