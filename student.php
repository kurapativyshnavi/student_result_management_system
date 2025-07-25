<?php include('db_connect.php') ?>
<?php if($_SESSION['login_type'] == 1): ?>

    <div>harsha</div>

<?php else: ?>
	 <div class="col-12">
          <div class="card">
          	<div class="card-body">
          		Welcome <?php echo $_SESSION['stu_login_id'] ?>!
          	</div>
          </div>
      </div>
          
<?php endif; ?>
