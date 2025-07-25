<?php include'db_connect.php' ?>
<div class="col-lg-12">
	<div class="card card-outline card-primary" style="margin: 32px auto; max-width: 1200px; border-radius: 18px; box-shadow: 0 4px 24px rgba(44,62,80,0.08);">
		<div class="card-header">
			<div class="card-tools">
				<a class="btn btn-block btn-sm btn-default btn-flat border-primary new_class" href="javascript:void(0)"><i class="fa fa-plus"></i> Add New</a>
			</div>
		</div>
		<div class="card-body" style="background: #fff; border-radius: 12px; width: 100%; overflow-x: auto;">
			<div class="table-responsive" style="margin: 0; width: 100%;">
				<table class="table table-hover table-bordered" id="class-list">
					<thead>
						<tr>
							<th class="text-center" width="5%">#</th>
							<th width="40%">Level</th>
							<th width="40%">Section</th>
							<th width="15%" class="text-center">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 1;
						$qry = $conn->query("SELECT * FROM classes order by level asc, section asc");
						while($row= $qry->fetch_assoc()):
						?>
						<tr>
							<td class="text-center"><?php echo $i++ ?></td>
							<td><b><?php echo $row['level'] ?></b></td>
							<td><b><?php echo $row['section'] ?></b></td>
							<td class="text-center">
								<div class="btn-group">
									<a href="javascript:void(0)" data-id='<?php echo $row['id'] ?>' class="btn btn-primary btn-flat manage_class">
										<i class="fas fa-edit"></i>
									</a>
									<button type="button" class="btn btn-danger btn-flat delete_class" data-id="<?php echo $row['id'] ?>">
										<i class="fas fa-trash"></i>
									</button>
								</div>
							</td>
						</tr>	
						<?php endwhile; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		var table = $('#class-list').DataTable({
			"responsive": true,
			"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
			"pageLength": 25,
			"searching": true,
			"ordering": true,
			"info": true,
			"autoWidth": false,
			"language": {
				"lengthMenu": "Display _MENU_ classes per page",
				"zeroRecords": "No classes found",
				"info": "Showing page _PAGE_ of _PAGES_",
				"infoEmpty": "No classes available",
				"infoFiltered": "(filtered from _MAX_ total classes)",
				"search": "Search classes:",
				"paginate": {
					"first": "First",
					"last": "Last",
					"next": "<i class='fas fa-chevron-right'></i>",
					"previous": "<i class='fas fa-chevron-left'></i>"
				}
			},
			"dom": '<"top"lf>rt<"bottom"ip><"clear">',
			"drawCallback": function(settings) {
				$('.dataTables_paginate > .pagination').addClass('pagination-sm');
			}
		});

		// Add New Class
		$('.new_class').click(function(){
			uni_modal("New Class","manage_class.php", "medium");
		});

		// Edit Class
		$('.manage_class').click(function(){
			uni_modal("Manage Class","manage_class.php?id="+$(this).attr('data-id'), "medium");
		});

		// Delete Class
		$('.delete_class').click(function(){
			_conf("Are you sure to delete this class?","delete_class",[$(this).attr('data-id')]);
		});
	});

	function delete_class($id){
		start_load();
		$.ajax({
			url:'ajax.php?action=delete_class',
			method:'POST',
			data:{id:$id},
			success:function(resp){
				if(resp==1){
					alert_toast("Data successfully deleted",'success');
					setTimeout(function(){
						location.reload();
					},1500);
				}
			}
		});
	}
</script>