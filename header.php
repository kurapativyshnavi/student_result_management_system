<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php 
  date_default_timezone_set("Asia/Manila");
  
  ob_start();
  $title = isset($_GET['page']) ? ucwords(str_replace("_", ' ', $_GET['page'])) : "Home";
  $title = str_replace("Persons Companies","Persons/Companies",$title);
  $hide_title = (isset($_GET['page']) && ($_GET['page'] == 'task1' || $_GET['page'] == 'task11')) ? true : false;
  ?>
  <title><?php echo $title ?> | <?php echo $_SESSION['system']['name'] ?></title>
  <style>
    <?php if($hide_title): ?>
    .content-header { display: none !important; }
    <?php endif; ?>
  </style>
  <?php ob_end_flush() ?>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="assets/plugins/jquery/jquery.min.js"></script>
  
  <!-- Bootstrap 4 CSS and JS -->
  <link rel="stylesheet" href="assets/plugins/bootstrap/css/bootstrap.min.css">
  <script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  
  <!-- Theme style -->
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  
  <!-- Custom styles -->
  <link rel="stylesheet" href="assets/dist/css/styles.css">
  <link rel="stylesheet" href="assets/dist/css/custom.css">
  
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <script src="assets/plugins/sweetalert2/sweetalert2.min.js"></script>
  
  <!-- Toastr -->
  <link rel="stylesheet" href="assets/plugins/toastr/toastr.min.css">
  <script src="assets/plugins/toastr/toastr.min.js"></script>
</head>

<script>
    // Modal handling functions
    function uni_modal(title = '', url = '', size = "") {
        start_load();
        $.ajax({
            url: url,
            error: err => {
                console.log(err);
                end_load();
            },
            success: function(resp) {
                if (resp) {
                    $('#uni_modal .modal-title').html(title);
                    $('#uni_modal .modal-body').html(resp);
                    if (size != '') {
                        $('#uni_modal .modal-dialog').addClass(size);
                    } else {
                        $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md");
                    }
                    $('#uni_modal').modal('show');
                    end_load();
                }
            }
        });
    }

    // Loading indicator functions
    window.start_load = function() {
        $('body').prepend('<div id="preloader2"></div>');
    }
    window.end_load = function() {
        $('#preloader2').fadeOut('fast', function() {
            $(this).remove();
        });
    }

    // Alert toast function
    window.alert_toast = function($msg = 'TEST', $bg = 'success', $pos = 'top-right') {
        $('#alert_toast').removeClass('bg-success');
        $('#alert_toast').removeClass('bg-danger');
        $('#alert_toast').removeClass('bg-info');
        $('#alert_toast').removeClass('bg-warning');

        if ($bg == 'success')
            $('#alert_toast').addClass('bg-success');
        if ($bg == 'danger')
            $('#alert_toast').addClass('bg-danger');
        if ($bg == 'info')
            $('#alert_toast').addClass('bg-info');
        if ($bg == 'warning')
            $('#alert_toast').addClass('bg-warning');

        $('#alert_toast .toast-body').html($msg);
        $('#alert_toast').toast({delay:3000}).toast('show');
    }

    // Confirmation dialog function
    window._conf = function($msg = '', $func = '', $params = []) {
        $('#confirm_modal #confirm').attr('onclick', $func + "(" + $params.join(',') + ")");
        $('#confirm_modal .modal-body').html($msg);
        $('#confirm_modal').modal('show');
    }

    // Form submission handling
    $(document).ready(function() {
        // Handle form submissions
        $('form').on('submit', function(e) {
            e.preventDefault();
            start_load();
            $.ajax({
                url: 'ajax.php?action=' + $(this).attr('action'),
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                success: function(resp) {
                    if (resp == 1) {
                        alert_toast("Data successfully saved", 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else if (resp == 2) {
                        alert_toast("Data already exists", 'danger');
                        end_load();
                    } else {
                        end_load();
                    }
                }
            });
        });
    });
</script>