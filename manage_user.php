<?php 
include('db_connect.php');
session_start();
if(isset($_GET['id'])){
$user = $conn->query("SELECT * FROM users where id =".$_GET['id']);
foreach($user->fetch_array() as $k =>$v){
	$meta[$k] = $v;
}
}
?>
<div class="container-fluid">
	<div id="msg"></div>
	
	<form action="" id="manage-user">	
		<input type="hidden" name="id" value="<?php echo isset($meta['id']) ? $meta['id']: '' ?>">
		<div class="form-group">
			<label for="name">First Name</label>
			<input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo isset($meta['firstname']) ? $meta['firstname']: '' ?>" required>
		</div>
		<div class="form-group">
			<label for="name">Last Name</label>
			<input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo isset($meta['lastname']) ? $meta['lastname']: '' ?>" required>
		</div>
		<div class="form-group">
			<label for="username">Username</label>
			<input type="text" name="username" id="username" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" required  autocomplete="off">
		</div>
		<div class="form-group">
			<label for="password">Password</label>
			<input type="password" name="password" id="password" class="form-control" value="" autocomplete="off">
			<small><i>Enter new password to change it.</i></small>
		</div>
		<div class="form-group">
			<label for="cpass">Confirm Password</label>
			<input type="password" name="cpass" id="cpass" class="form-control" value="" autocomplete="off">
			<small id="pass_match" data-status=''></small>
		</div>
		

	</form>
</div>
<style>
	img#cimg{
		max-height: 15vh;
		/*max-width: 6vw;*/
	}
</style>
<script>
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	$('[name="password"],[name="cpass"]').keyup(function(){
		var pass = $('[name="password"]').val()
		var cpass = $('[name="cpass"]').val()
		if(cpass == '' || pass == ''){
			$('#pass_match').attr('data-status','')
		}else{
			if(cpass == pass){
				$('#pass_match').attr('data-status','1').html('<i class="text-success">Password Matched.</i>')
			}else{
				$('#pass_match').attr('data-status','2').html('<i class="text-danger">Password does not match.</i>')
			}
		}
	})
	$('#manage-user').submit(function(e){
		e.preventDefault();
		start_load()
		
		// Get form data
		var formData = new FormData(this);
		
		// Check password match if both fields are filled
		if($('[name="password"]').val() != '' && $('[name="cpass"]').val() != ''){
			if($('[name="password"]').val() != $('[name="cpass"]').val()){
				$('#msg').html('<div class="alert alert-danger">Passwords do not match</div>')
				$('[name="password"],[name="cpass"]').addClass("border-danger")
				end_load()
				return false;
			}
		}
		
		// Send AJAX request
		$.ajax({
			url:'ajax.php?action=update_user',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
			method: 'POST',
			success:function(resp){
				console.log('Response:', resp); // Debug log
				if(resp == 1){
					alert_toast("Data successfully saved",'success')
					setTimeout(function(){
						location.reload()
					},1500)
				}else if(resp == 2){
					$('#msg').html('<div class="alert alert-danger">Username already exist</div>')
					end_load()
				}else if(resp == 3){
					$('#msg').html('<div class="alert alert-danger">Passwords do not match</div>')
					$('[name="password"],[name="cpass"]').addClass("border-danger")
					end_load()
				}else{
					$('#msg').html('<div class="alert alert-danger">Error occurred while saving</div>')
					end_load()
				}
			},
			error: function(xhr, status, error) {
				console.log('Error:', error);
				console.log('Status:', status);
				console.log('Response:', xhr.responseText);
				$('#msg').html('<div class="alert alert-danger">An error occurred while saving. Please try again.</div>')
				end_load()
			}
		})
	})

</script>