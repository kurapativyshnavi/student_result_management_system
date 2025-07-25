<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <div class="dropdown">
    <a href="javascript:void(0)" class="brand-link dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
      <?php if(empty($_SESSION['login_avatar'])): ?>
        <span class="brand-image img-circle elevation-3 d-flex justify-content-center align-items-center bg-gradient-primary text-white">
          <i class="fas fa-user-shield"></i>
        </span>
      <?php else: ?>
        <span class="image">
          <img src="../assets/uploads/<?php echo $_SESSION['login_avatar'] ?>" style="width: 38px;height:38px" class="img-circle elevation-2" alt="User Image">
        </span>
      <?php endif; ?>
      <span class="brand-text font-weight-light">Administrator</span>
    </a>
    <div class="dropdown-menu">
      <a class="dropdown-item manage_account" href="javascript:void(0)" data-id="<?php echo $_SESSION['login_id'] ?>">
        <i class="fas fa-user-cog mr-2"></i> Manage Account
      </a>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="ajax.php?action=logout">
        <i class="fas fa-sign-out-alt mr-2"></i> Logout
      </a>
    </div>
  </div>
  <div class="sidebar">
    <nav class="mt-0">
      <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
          <a href="./" class="nav-link nav-home">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="./index.php?page=classes" class="nav-link nav-classes">
            <i class="nav-icon fas fa-th-list"></i>
            <p>Classes</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="./index.php?page=subjects" class="nav-link nav-subjects">
            <i class="nav-icon fas fa-book"></i>
            <p>Subjects</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="./index.php?page=student_list" class="nav-link nav-student_list">
            <i class="nav-icon fas fa-users"></i>
            <p>Students</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="./index.php?page=results" class="nav-link nav-results">
            <i class="nav-icon fas fa-file-alt"></i>
            <p>Results</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="./index.php?page=analytics" class="nav-link nav-analytics">
            <i class="nav-icon fas fa-chart-bar"></i>
            <p>Visuals</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>

<script>
  $(document).ready(function(){
    var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
    if($('.nav-link.nav-'+page).length > 0){
      $('.nav-link.nav-'+page).addClass('active');
    }
    
    // Prevent menu expansion
    $('.nav-link').click(function(e){
      if($(this).attr('href') === '#') {
        e.preventDefault();
      }
    });
    
    $('.manage_account').click(function(){
      uni_modal('Manage Account','manage_user.php?id='+$(this).attr('data-id'))
    });
  });
</script>
