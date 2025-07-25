<!DOCTYPE html>
<html lang="en">
<?php 
session_start();
include('./db_connect.php');
  ob_start();
  // if(!isset($_SESSION['system'])){

    $system = $conn->query("SELECT * FROM system_settings")->fetch_array();
    foreach($system as $k => $v){
      $_SESSION['system'][$k] = $v;
    }
  // }
  ob_end_flush();
?>
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Login | <?php echo $_SESSION['system']['name'] ?></title>
  <?php include('./header.php'); ?>
  <link rel="stylesheet" href="assets/dist/css/custom.css">
<?php 
if(isset($_SESSION['login_id']))
header("location:index.php?page=home");

?>

</head>

<body class="login-page">
  <div class="login-card fade-in">
    <div class="login-header">
      <div class="icon-wrapper">
        <i class="fas fa-graduation-cap fa-3x"></i>
      </div>
      <h1 class="system-name">Student Result Management System</h1>
      <p class="subtitle" style="color:rgb(25, 152, 146) ; font-weight:bold; font-size:20px;">🎯 Access your grades and stay on track for success.
      🚀</p>
    </div>

    <div class="login-body">
      <div class="login-form-wrapper">
        <h3 style="color:rgb(105, 17, 84); font-weight:bold;">Admin Login</h3>
        <form id="login-form" class="login-form">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username">
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password">
          </div>
          <button type="submit" class="btn bg-primary text-white">Next</button>
        </form>

        <div class="student-section">
          <p>Student Section</p>
          <button class="btn bg-success text-white" type="button" id="view_result">
            Check Results
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Result Modal -->
  <div class="modal fade" id="view_student_results" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Student Result Lookup</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="vsr-frm">
            <div class="form-group">
              <label for="student_code">Student ID #</label>
              <input type="text" id="student_code" name="student_code" class="form-control" placeholder="Enter your Student ID">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id='submit' onclick="$('#view_student_results form').submit()">View Results</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</body>
<?php include 'footer.php' ?>
<script>
  $('#view_result').click(function(){
    $('#view_student_results').modal('show')
  })
	$('#login-form').submit(function(e){
		e.preventDefault()
		$('#login-form button[type="submit"]').attr('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Logging in...');
		if($(this).find('.alert-danger').length > 0 )
			$(this).find('.alert-danger').remove();
		$.ajax({
			url:'ajax.php?action=login',
			method:'POST',
			data:$(this).serialize(),
			error:err=>{
				console.log(err)
		$('#login-form button[type="submit"]').removeAttr('disabled').html('Next');

			},
			success:function(resp){
				if(resp == 1){
					location.href ='index.php?page=home';
				}else{
					$('#login-form').prepend('<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Username or password is incorrect.</div>')
					$('#login-form button[type="submit"]').removeAttr('disabled').html('Next');
				}
			}
		})
	})

  // $('#login-form-student').submit(function(e){
	// 	e.preventDefault()
	// 	$('#login-form-student button[type="button"]').attr('disabled',true).html('Logging in...');
	// 	if($(this).find('.alert-danger').length > 0 )
	// 		$(this).find('.alert-danger').remove();
	// 	$.ajax({
	// 		url:'ajax.php?action=stu_login',
	// 		method:'POST',
	// 		data:$(this).serialize(),
	// 		error:err=>{
	// 			console.log(err)
	// 	$('#login-form-student button[type="button"]').removeAttr('disabled').html('Login');

	// 		},
	// 		success:function(resp){
	// 			if(resp == 1){
	// 				location.href ='index.php?page=student';
	// 			}else{
	// 				$('#login-form-student').prepend('<div class="alert alert-danger">Username or password is incorrect.</div>')
	// 				$('#login-form-student button[type="button"]').removeAttr('disabled').html('Login');
	// 			}
	// 		}
	// 	})
	// })

  $('#vsr-frm').submit(function(e){
    e.preventDefault()
    if($(this).find('.alert-danger').length > 0 )
      $(this).find('.alert-danger').remove();
    $.ajax({
      url:'ajax.php?action=login2',
      method:'POST',
      data:$(this).serialize(),
      error:err=>{
        console.log(err)
      },
      success:function(resp){
        if(resp == 1){
          start_load();
          location.href ='student_results.php';
        }else{
          $('#vsr-frm').prepend('<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Student ID # is incorrect.</div>')
        }
      }
    })
  })
	$('.number').on('input keyup keypress',function(){
        var val = $(this).val()
        val = val.replace(/[^0-9 \,]/, '');
        val = val.toLocaleString('en-US')
        $(this).val(val)
    })
</script>	
</html>