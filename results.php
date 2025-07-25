<?php include 'db_connect.php' ?>
<div class="col-lg-12">
    <div class="card card-outline card-primary" style="margin: 32px auto; max-width: 1200px; border-radius: 18px; box-shadow: 0 4px 24px rgba(44,62,80,0.08);">
        <?php if(!isset($_SESSION['rs_id'])): ?>
        <div class="card-header">
            <div class="card-tools">
                <a class="btn btn-block btn-sm btn-default btn-flat border-primary new_result" href="javascript:void(0)"><i class="fa fa-plus"></i> Add New</a>
            </div>
        </div>
        <?php endif; ?>
        <div class="card-body" style="background: #fff; border-radius: 12px; width: 100%; overflow-x: auto;">
            <table class="table table-hover table-bordered" id="list">
                <colgroup>
                    <col width="5%">
                    <col width="15%">
                    <col width="25%">
                    <col width="20%">
                    <col width="10%">
                    <col width="15%">
                    <col width="10%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Student Code</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Subjects</th>
                        <th>Total Marks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $where = "";
                    if(isset($_SESSION['rs_id'])){
                        $where = " WHERE r.student_id = {$_SESSION['rs_id']} ";
                    }
                    $qry = $conn->query("
                        SELECT r.*, 
                        CONCAT(s.firstname,' ',s.middlename,' ',s.lastname) AS name,
                        s.student_code,
                        CONCAT(c.level,'-',c.section) AS class 
                        FROM results r 
                        INNER JOIN classes c ON c.id = r.class_id 
                        INNER JOIN students s ON s.id = r.student_id 
                        $where 
                        ORDER BY UNIX_TIMESTAMP(r.date_created) DESC
                    ");

                    while($row = $qry->fetch_assoc()):
                        // Get number of subjects
                        $subjects = $conn->query("SELECT * FROM result_items WHERE result_id = ".$row['id'])->num_rows;
                        
                        // Calculate total marks
                        $total_marks = 0;
                        $marks_query = $conn->query("SELECT SUM(mark) AS total FROM result_items WHERE result_id = ".$row['id']);
                        if($marks_query->num_rows > 0) {
                            $total_marks = $marks_query->fetch_assoc()['total'];
                        }
                    ?>
                    <tr>
                        <th class="text-center"><?php echo $i++ ?></th>
                        <td><b><?php echo $row['student_code'] ?></b></td>
                        <td><b><?php echo ucwords($row['name']) ?></b></td>
                        <td><b><?php echo ucwords($row['class']) ?></b></td>
                        <td class="text-center"><b><?php echo $subjects ?></b></td>
                        <td class="text-center"><b><?php echo number_format($total_marks, 2) ?></b></td>
                        <td class="text-center">
                            <?php if(isset($_SESSION['login_id'])): ?>
                            <div class="btn-group">
                                <a href="./index.php?page=edit_result&id=<?php echo $row['id'] ?>" class="btn btn-primary btn-flat">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button data-id="<?php echo $row['id'] ?>" type="button" class="btn btn-info btn-flat view_result">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-flat delete_result" data-id="<?php echo $row['id'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <?php elseif(isset($_SESSION['rs_id'])): ?>
                            <button data-id="<?php echo $row['id'] ?>" type="button" class="btn btn-info btn-flat view_result">
                                <i class="fas fa-eye"></i> View Result
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Initialize DataTable with responsive settings
    $('#list').DataTable({
        "responsive": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "pageLength": 25,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "language": {
            "lengthMenu": "Display _MENU_ results per page",
            "zeroRecords": "No results found",
            "info": "Showing page _PAGE_ of _PAGES_",
            "infoEmpty": "No results available",
            "infoFiltered": "(filtered from _MAX_ total results)",
            "search": "Search results:",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "<i class='fas fa-chevron-right'></i>",
                "previous": "<i class='fas fa-chevron-left'></i>"
            }
        },
        "drawCallback": function(settings) {
            $('.dataTables_paginate > .pagination').addClass('pagination-sm');
        }
    });
    
    // Delete result
    $('.delete_result').click(function(){
        _conf("Are you sure to delete this result?", "delete_result", [$(this).attr('data-id')]);
    });

    // View result
    $('.view_result').click(function(){
        uni_modal("Result Details", "view_result.php?id=" + $(this).attr('data-id'), "mid-large");
    });

    // Add this handler for the Add New button
    $('.new_result').click(function(){
        uni_modal("New Result", "new_result.php", "large");
    });
});

function delete_result(id) {
    start_load();
    $.ajax({
        url: 'ajax.php?action=delete_result',
        method: 'POST',
        data: {id: id},
        success: function(resp) {
            if(resp == 1) {
                alert_toast("Data deleted successfully", 'success');
                setTimeout(function(){
                    location.reload();
                }, 1500);
            }
        }
    });
}
</script>

<style>
.modal-dialog {
    max-width: 900px;
}
.modal-content {
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.modal-body {
    overflow-y: auto;
    flex: 1 1 auto;
    max-height: 60vh;
}
</style>
