<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-primary navbar-dark">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <?php if (isset($_SESSION['login_id'])): ?>
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    <?php endif; ?>
    <li>
      <a class="nav-link text-white" href="#" id="home-link" role="button">
        <large><b><?php echo $_SESSION['system']['name'] ?></b></large>
      </a>
    </li>
  </ul>

  <!-- Right navbar links -->
  <ul class="navbar-nav ml-auto">
    <?php if (isset($_SESSION['rs_id'])): ?>
      <li class="nav-item">
        <a class="nav-link admin-link" href="ajax.php?action=logout">
          <i class="fas fa-user"></i>
          <span class="admin-text"><?php echo ucwords($_SESSION['rs_name']) ?></span>
          <i class="fa fa-sign-out-alt"></i>
        </a>
      </li>
    <?php endif; ?>
  </ul>
</nav>

<style>
/* Enhanced Navbar Styles */
.main-header {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.main-header:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Admin Link Styles */
.admin-link {
    position: relative;
    padding: 8px 15px;
    border-radius: 5px;
    transition: all 0.3s ease;
    overflow: hidden;
}

.admin-link:hover {
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.admin-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,0.2),
        transparent
    );
    transition: 0.5s;
}

.admin-link:hover::before {
    left: 100%;
}

.admin-text {
    position: relative;
    display: inline-block;
    transition: all 0.3s ease;
}

.admin-link:hover .admin-text {
    color: #ffc107 !important;
    transform: scale(1.05);
}

/* Icon Animations */
.admin-link i {
    transition: all 0.3s ease;
}

.admin-link:hover i:first-child {
    transform: rotate(15deg);
}

.admin-link:hover i:last-child {
    transform: translateX(3px);
}

/* Menu Button Animation */
.nav-link[data-widget="pushmenu"] {
    transition: all 0.3s ease;
}

.nav-link[data-widget="pushmenu"]:hover {
    transform: scale(1.1);
    color: #ffc107 !important;
}

/* Home Link Animation */
#home-link {
    transition: all 0.3s ease;
}

#home-link:hover {
    transform: translateY(-2px);
    text-shadow: 0 0 10px rgba(255,255,255,0.5);
}
</style>
