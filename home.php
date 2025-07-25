<?php include('db_connect.php') ?>
<!-- Info boxes -->
<?php if($_SESSION['login_type'] == 1): ?>
  <div class="row fade-in">
    <div class="col-12 mb-4">
      <div class="welcome-card">
        <h4>Welcome to <?php echo $_SESSION['system']['name'] ?></h4>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
      <div class="info-box bg-white">
        <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Total Students</span>
          <span class="info-box-number">
            <?php echo $conn->query("SELECT * FROM students")->num_rows; ?>
          </span>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
      <div class="info-box bg-white">
        <span class="info-box-icon bg-primary"><i class="fas fa-th-list"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Total Classes</span>
          <span class="info-box-number">
            <?php echo $conn->query("SELECT * FROM classes")->num_rows; ?>
          </span>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
      <div class="info-box bg-white">
        <span class="info-box-icon bg-success"><i class="fas fa-book"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Total Subjects</span>
          <span class="info-box-number">
            <?php echo $conn->query("SELECT * FROM subjects")->num_rows; ?>
          </span>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="col-12">
    <div class="card welcome-card fade-in">
      <div class="card-body">
        <h4>Welcome <?php echo $_SESSION['login_name'] ?>!</h4>
        <p class="mt-3 mb-0">Access your student information and results using the menu on the left.</p>
      </div>
    </div>
  </div>
<?php endif; ?>
