<?php
include 'db_connect.php';
$qry = $conn->query("SELECT r.*,concat(s.firstname,' ',s.middlename,' ',s.lastname) as name,s.student_code,concat(c.level,'-',c.section) as class,s.gender FROM results r inner join classes c on c.id = r.class_id inner join students s on s.id = r.student_id where r.id = ".$_GET['id'])->fetch_array();
foreach($qry as $k => $v){
	$$k = $v;
}

// Calculate total marks and max marks
$total_marks = 0;
$max_marks = 0;
$subject_count = 0;
$items=$conn->query("SELECT r.*,s.subject_code,s.subject, s.max_mark FROM result_items r inner join subjects s on s.id = r.subject_id where result_id = $id order by s.subject_code asc");
$subject_data = [];
while($row = $items->fetch_assoc()){
    $total_marks += $row['mark'];
    $max_marks += isset($row['max_mark']) ? $row['max_mark'] : 100; // fallback to 100 if not set
    $subject_count++;
    // Calculate percentage and grade
    $percentage = isset($row['max_mark']) && $row['max_mark'] > 0 ? ($row['mark'] / $row['max_mark']) * 100 : $row['mark'];
    $grade = ($percentage >= 90) ? 'A+' : (($percentage >= 80) ? 'A' : (($percentage >= 70) ? 'B' : (($percentage >= 60) ? 'C' : (($percentage >= 50) ? 'D' : (($percentage >= 40) ? 'E' : 'F')))));
    $row['percentage'] = round($percentage, 2);
    $row['grade'] = $grade;
    $subject_data[] = $row;
}
// Calculate overall percentage and grade
$overall_percentage = $max_marks > 0 ? ($total_marks / $max_marks) * 100 : 0;
$overall_grade = ($overall_percentage >= 90) ? 'A+' : (($overall_percentage >= 80) ? 'A' : (($overall_percentage >= 70) ? 'B' : (($overall_percentage >= 60) ? 'C' : (($overall_percentage >= 50) ? 'D' : (($overall_percentage >= 40) ? 'E' : 'F')))));
?>
<style>
    body {
        background: url('assets/dist/img/report-bg.jpg') no-repeat center center fixed;
        background-size: cover;
    }
    #printable {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    .table-bordered {
        border: 1px solid #dee2e6;
    }
    
    .table thead th {
        background: #3b43ed ;
        color: white;
        border-color: #454d55;
        vertical-align: middle;
    }
    
    .table tbody td {
        vertical-align: middle;
    }
    
    .table tfoot th {
        background: #3b43ed ;
        color: white;
        border-color: #454d55;
    }
    
    hr {
        border-top: 2px solid #dee2e6;
        margin: 1.5rem 0;
    }
    
    .btn-success {
        background: linear-gradient(to right, #28a745, #218838);
        border: none;
    }
    
    .btn-secondary {
        background: linear-gradient(to right, #6c757d, #5a6268);
        border: none;
    }

    .text-center {
        text-align: center;
    }

    /* Print styles */
    @media print {
        .table thead th {
            background-color: #3b43ed  !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .table tfoot th {
            background-color: #3b43ed  !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    .report-card {
        max-width: 700px;
        margin: 30px auto;
        padding: 32px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        font-family: 'Arial', sans-serif;
    }
    .rc-header {
        text-align: center;
        border-bottom: 2px solid #1a237e;
        margin-bottom: 24px;
        padding-bottom: 12px;
    }
    .rc-header h1 {
        font-size: 2.2rem;
        color: #1a237e;
        margin: 0;
        letter-spacing: 2px;
    }
    .rc-class {
        font-size: 1.1rem;
        color: #1a237e;
        margin-top: 8px;
    }
    .rc-student-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 18px;
        font-size: 1rem;
    }
    .rc-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18px;
    }
    .rc-table th, .rc-table td {
        border: 1.5px solid #1a237e;
        padding: 10px;
        text-align: center;
    }
    .rc-table th, .rc-table tfoot th {
        background: #28a745 !important;
        color: #fff !important;
        font-weight: bold;
    }
    .rc-footer {
        margin-top: 24px;
    }
    .rc-grade-level {
        margin-bottom: 12px;
    }
    .rc-grade {
        display: inline-block;
        width: 32px;
        height: 32px;
        line-height: 32px;
        border-radius: 50%;
        background: #e3e7f1;
        color: #1a237e;
        font-weight: bold;
        margin: 0 4px;
        text-align: center;
        font-size: 1.2rem;
        letter-spacing: 1px;
    }
    .rc-grade.active {
        background:rgb(230, 231, 245);
        color: #17b43d;
    }
    .rc-notes {
        margin-top: 10px;
        font-size: 0.95rem;
    }
    @media print {
        body {
            background: #fff !important;
        }
        .report-card {
            box-shadow: none !important;
            border: none !important;
        }
        .rc-table th, .rc-table tfoot th {
            background: #28a745 !important;
            color: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
<div class="report-card" id="printable">
    <div class="rc-header">
        <h1>REPORT CARD</h1>
        <div class="rc-class">Class: <b><?php echo $class ?></b></div>
    </div>
    <div class="rc-student-info">
        <div>Student ID: <b><?php echo $student_code ?></b></div>
        <div>Student Name: <b><?php echo ucwords($name) ?></b></div>
        <div>Gender: <b><?php echo ucwords($gender) ?></b></div>
    </div>
    <table class="rc-table" >
        <thead>
            <tr>
                <th>Subject Code</th>
                <th>Subject</th>
                <th>Mark</th>
                <th>Percentage</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($subject_data as $row): ?>
            <tr>
                <td><?php echo $row['subject_code'] ?></td>
                <td><?php echo ucwords($row['subject']) ?></td>
                <td><?php echo number_format($row['mark']) ?></td>
                <td><?php echo $row['percentage'] ?>%</td>
                <td><?php echo $row['grade'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total Marks</th>
                <th><?php echo number_format($total_marks,2) ?></th>
                <th><?php echo round($overall_percentage,2) ?>%</th>
                <th><?php echo $overall_grade ?></th>
            </tr>
        </tfoot>
    </table>
    <div class="rc-footer">
        <div class="rc-grade-level">
            Grade Level:
            <span class="rc-grade<?php if($overall_grade=='A+') echo ' active'; ?>">A+</span>
            <span class="rc-grade<?php if($overall_grade=='A') echo ' active'; ?>">A</span>
            <span class="rc-grade<?php if($overall_grade=='B') echo ' active'; ?>">B</span>
            <span class="rc-grade<?php if($overall_grade=='C') echo ' active'; ?>">C</span>
            <span class="rc-grade<?php if($overall_grade=='D') echo ' active'; ?>">D</span>
            <span class="rc-grade<?php if($overall_grade=='E') echo ' active'; ?>">E</span>
            <span class="rc-grade<?php if($overall_grade=='F') echo ' active'; ?>">F</span>
        </div>
        
    </div>
</div>
<div class="modal-footer display p-0 m-0">
    <button type="button" class="btn btn-success" id="print"><i class="fa fa-print"></i> Print</button>
    <button type="button" class="btn btn-secondary" id="closeBtn">Close</button>
</div>
<style>
	#uni_modal .modal-footer{
		display: none
	}
	#uni_modal .modal-footer.display{
		display: flex
	}
</style>
<noscript>
	<style>
		table.table{
			width:100%;
			border-collapse: collapse;
		}
		table.table tr,table.table th, table.table td{
			border:1px solid;
		}
		.text-cnter{
			text-align: center;
		}
	</style>
	<h3 class="text-center"><b>Student Result</b></h3>
</noscript>
<script>
	$('#print').click(function(){
		start_load();
		var content = $('.report-card').prop('outerHTML');
		var style = `
			<style>
			body { background: #fff !important; font-family: 'Arial', sans-serif; }
			.report-card { max-width: 700px; margin: 30px auto; padding: 32px; background: #fff; border-radius: 16px; box-shadow: none; font-family: 'Arial', sans-serif; }
			.rc-header { text-align: center; border-bottom: 2px solid #1a237e; margin-bottom: 24px; padding-bottom: 12px; }
			.rc-header h1 { font-size: 2.2rem; color: #1a237e; margin: 0; letter-spacing: 2px; }
			.rc-class { font-size: 1.1rem; color: #1a237e; margin-top: 8px; }
			.rc-student-info { display: flex; justify-content: space-between; margin-bottom: 18px; font-size: 1rem; }
			.rc-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
			.rc-table th, .rc-table tfoot th { background: #28a745 !important; color: #fff !important; font-weight: bold; }
			.rc-table th, .rc-table td { border: 1.5px solid #1a237e; padding: 10px; text-align: center; }
			.rc-footer { margin-top: 24px; }
			.rc-grade-level { margin-bottom: 12px; }
			.rc-grade { display: inline-block; width: 32px; height: 32px; line-height: 32px; border-radius: 50%; background: #e3e7f1; color: #1a237e; font-weight: bold; margin: 0 4px; text-align: center; font-size: 1.2rem; letter-spacing: 1px; }
			.rc-grade.active { background: #1a237e; color: #fff; }
			.rc-notes { margin-top: 10px; font-size: 0.95rem; }
			@media print { 
				body { background: #fff !important; }
				.report-card { box-shadow: none !important; border: none !important; }
				.rc-table th, .rc-table tfoot th { 
					background: #28a745 !important; 
					color: #fff !important; 
					-webkit-print-color-adjust: exact; 
					print-color-adjust: exact; 
				}
				@page {
					size: A4;
					margin: 0;
				}
			}
			</style>
		`;
		var nw = window.open('', '_blank', 'height=700,width=900');
		nw.document.write('<html><head><title>Report Card</title>' + style + '</head><body>' + content + '</body></html>');
		nw.document.close();
		
		// Wait for the content to load before printing
		nw.onload = function() {
			nw.focus();
			nw.print();
			setTimeout(function(){
				nw.close();
				end_load();
			}, 1000);
		};
	});

	$('#closeBtn').click(function(){
		$('#uni_modal').modal('hide');
	});
</script>