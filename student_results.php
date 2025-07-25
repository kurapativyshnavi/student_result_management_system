<!DOCTYPE html>
<html lang="en">
<?php session_start(); ?>
<?php 
 include 'db_connect.php';
 if(!isset($_SESSION['rs_id']))
      header('location:login.php');
 if(!isset($_SESSION['system'])){
    $system = $conn->query("SELECT * FROM system_settings")->fetch_array();
    foreach($system as $k => $v){
      $_SESSION['system'][$k] = $v;
    }
 }
 include 'header.php'; 
?>
<head>
    <!-- Bootstrap CSS CDN (if not already included) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: url('assets/dist/img/report-bg.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .content-wrapper {
            margin-left: 0 !important;
            padding-left: 0 !important;
            
            min-height: 100vh;
        }
        .results-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(60, 72, 100, 0.15);
            padding: 2rem 2rem 1rem 2rem;
            margin: 2rem auto;
            max-width: 1100px;
        }
        .table thead th {
            
            color: #fff;
            font-weight: 600;
            border-top: none;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .btn-view {
            background: #18a2b8;
            color: #fff;
            border-radius: 6px;
            font-weight: 500;
            padding: 6px 16px;
        }
        .btn-view:hover {
            background: #117a8b;
            color: #fff;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
            margin: 0 2px;
        }
        .search-bar {
            max-width: 250px;
        }
        @media (max-width: 768px) {
            .results-card {
                padding: 1rem 0.5rem;
                max-width: 100%;
            }
            .search-bar {
                max-width: 100%;
            }
        }
        .main-header {
            margin-left: 0 !important;
            padding-left: 0 !important;
        }
    </style>
</head>
<body class="hold-transition layout-fixed layout-navbar-fixed layout-footer-fixed" >
<div class="wrapper" >
  <?php include 'topbar.php'; ?>
  <div class="content-wrapper">
    <section class="content">
      <div class="container-fluid py-2">
        <div class="results-container">
            <!-- Results Table -->
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover mb-0">
                    <thead style="background-color:rgb(245, 46, 142); color: #fff;">
                        <tr>
                            <th>#</th>
                            <th>STUDENT CODE</th>
                            <th>STUDENT NAME</th>
                            <th>CLASS</th>
                            <th>SUBJECTS</th>
                            <th>TOTAL MARKS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $student_id = $_SESSION['rs_id'];
                        $qry = $conn->query("SELECT r.*, s.student_code, CONCAT(s.firstname, ' ', s.middlename, ' ', s.lastname) AS student_name, CONCAT(c.level, '-', c.section) AS class
                            FROM results r
                            INNER JOIN students s ON s.id = r.student_id
                            INNER JOIN classes c ON c.id = r.class_id
                            WHERE r.student_id = $student_id
                            ORDER BY r.date_created DESC");

                        $i = 1;
                        while($row = $qry->fetch_assoc()):
                            $subjects = $conn->query("SELECT * FROM result_items WHERE result_id = ".$row['id'])->num_rows;
                            $total_marks = 0;
                            $marks_query = $conn->query("SELECT SUM(mark) AS total FROM result_items WHERE result_id = ".$row['id']);
                            if($marks_query->num_rows > 0) {
                                $total_marks = $marks_query->fetch_assoc()['total'];
                            }
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><a href="view_result.php?id=<?php echo $row['id']; ?>"><?php echo $row['student_code']; ?></a></td>
                            <td><?php echo $row['student_name']; ?></td>
                            <td><?php echo $row['class']; ?></td>
                            <td><?php echo $subjects; ?></td>
                            <td><?php echo number_format($total_marks, 2); ?></td>
                            <td>
                                <button type="button" class="btn btn-info btn-flat view_result" data-id="<?php echo $row['id']; ?>" title="View Result">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <!-- <div class="d-flex justify-content-between align-items-center mt-3">
                <span>Showing page 1 of 1</span>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item disabled"><span class="page-link">&lt;</span></li>
                        <li class="page-item active"><span class="page-link">1</span></li>
                        <li class="page-item disabled"><span class="page-link">&gt;</span></li>
                    </ul>
                </nav>-->
            </div>
        </div>
      </div>
    </section>
  </div>
</div>
<!-- Bootstrap JS (if not already included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome for eye icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script>
// Modal function (if not already defined globally)
function uni_modal(title, url, size = '') {
    start_load && start_load();
    $.ajax({
        url: url,
        error: function(err) {
            alert("An error occurred");
            end_load && end_load();
        },
        success: function(resp) {
            if (typeof resp === 'object' && resp.status === 'failed') {
                alert(resp.msg);
                end_load && end_load();
                return;
            }
            $('#uni_modal .modal-title').html(title);
            $('#uni_modal .modal-body').html(resp);
            if (size != '') {
                $('#uni_modal .modal-dialog').attr('class', 'modal-dialog ' + size + ' modal-dialog-centered');
            } else {
                $('#uni_modal .modal-dialog').attr('class', 'modal-dialog modal-md modal-dialog-centered');
            }
            $('#uni_modal').modal('show');
            end_load && end_load();
        }
    });
}

$(document).ready(function() {
    $(document).on('click', '.view_result', function() {
        var id = $(this).data('id');
        uni_modal('Result Details', 'view_result.php?id=' + id, 'mid-large');
    });
});
</script>
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
</body>
</html>
