<?php
session_start();
ini_set('display_errors', 1);
Class Action {
	private $db;

	public function __construct() {
		ob_start();
   	include 'db_connect.php';
    
    $this->db = $conn;
	}
	function __destruct() {
	    $this->db->close();
	    ob_end_flush();
	}

	function login(){
		extract($_POST);
			$qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM users where username = '".$username."' and password = '".md5($password)."' and type= 1 ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
				return 1;
		}else{
			return 2;
		}
	}
	function stu_login(){
		extract($_POST);
			$qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM students where student_code = '".$stu_code."' and password = '".md5($stu_password)."' ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['stu_login_'.$key] = $value;
			}
				return 1;
		}else{
			return 2;
		}
	}
	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}
	function login2(){
		extract($_POST);
			$qry = $this->db->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM students where student_code = '".$student_code."' ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['rs_'.$key] = $value;
			}
				return 1;
		}else{
			return 3;
		}
	}
	function save_user(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','password')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(!empty($cpass) && !empty($password)){
					$data .= ", password=md5('$password') ";

		}
		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'../assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");
		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			return 1;
		}
	}
	function signup(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass')) && !is_numeric($k)){
				if($k =='password'){
					if(empty($v))
						continue;
					$v = md5($v);

				}
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}

		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'../assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set $data");

		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			if(empty($id))
				$id = $this->db->insert_id;
			foreach ($_POST as $key => $value) {
				if(!in_array($key, array('id','cpass','password')) && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
					$_SESSION['login_id'] = $id;
			return 1;
		}
	}

	function update_user(){
		extract($_POST);
		$data = "";
		
		// Debug log
		error_log("Update User - POST Data: " . print_r($_POST, true));
		
		// Handle password update
		if(!empty($password) && !empty($cpass)){
			if($password == $cpass){
				$data .= " password = '" . md5($password) . "'";
			} else {
				return 3; // Passwords don't match
			}
		}
		
		// Handle other fields
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','password','table')) && !is_numeric($k)){
				if(!empty($data)){
					$data .= ", ";
				}
				$data .= " $k = '$v'";
			}
		}
		
		// Debug log
		error_log("Update User - SQL Data: " . $data);
		
		// Handle image upload if exists
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			if(!empty($data)){
				$data .= ", ";
			}
			$data .= " avatar = '$fname'";
		}
		
		// Check for duplicate email
		if(!empty($email)){
			$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
			if($check > 0){
				return 2;
			}
		}
		
		// Execute update
		if(empty($id)){
			$sql = "INSERT INTO users set $data";
		} else {
			$sql = "UPDATE users set $data where id = $id";
		}
		
		error_log("Update User - Final SQL: " . $sql);
		
		$save = $this->db->query($sql);
		
		if($save){
			// Update session if needed
			if(!empty($id) && $id == $_SESSION['login_id']){
				foreach ($_POST as $key => $value) {
					if($key != 'password' && !is_numeric($key)){
						$_SESSION['login_'.$key] = $value;
					}
				}
				if(isset($fname)){
					$_SESSION['login_avatar'] = $fname;
				}
			}
			return 1;
		} else {
			error_log("Update User - SQL Error: " . $this->db->error);
			return 0;
		}
	}
	function delete_user(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM users where id = ".$id);
		if($delete)
			return 1;
	}
	function save_system_settings(){
		extract($_POST);
		$data = '';
		foreach($_POST as $k => $v){
			if(!is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if($_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
			$data .= ", cover_img = '$fname' ";

		}
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set $data where id =".$chk->fetch_array()['id']);
		}else{
			$save = $this->db->query("INSERT INTO system_settings set $data");
		}
		if($save){
			foreach($_POST as $k => $v){
				if(!is_numeric($k)){
					$_SESSION['system'][$k] = $v;
				}
			}
			if($_FILES['cover']['tmp_name'] != ''){
				$_SESSION['system']['cover_img'] = $fname;
			}
			return 1;
		}
	}
	function save_image(){
		extract($_FILES['file']);
		if(!empty($tmp_name)){
			$fname = strtotime(date("Y-m-d H:i"))."_".(str_replace(" ","-",$name));
			$move = move_uploaded_file($tmp_name,'../assets/uploads/'. $fname);
			$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
			$hostName = $_SERVER['HTTP_HOST'];
			$path =explode('/',$_SERVER['PHP_SELF']);
			$currentPath = '/'.$path[1]; 
			if($move){
				return $protocol.'://'.$hostName.$currentPath.'/assets/uploads/'.$fname;
			}
		}
	}
	function save_class(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$chk = $this->db->query("SELECT * FROM classes where level ='$level' and section = '$section' and id != '$id' ");
		if($chk->num_rows > 0){
			return 2;
			exit;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO classes set $data");
		}else{
			$save = $this->db->query("UPDATE classes set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_class(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM classes where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_subject(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$chk = $this->db->query("SELECT * FROM subjects where subject_code ='$subject_code' and id != '$id' ");
		if($chk->num_rows > 0){
			return 2;
			exit;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO subjects set $data");
		}else{
			$save = $this->db->query("UPDATE subjects set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_subject(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM subjects where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_student(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','areas_id')) && !is_numeric($k)){
				if($k == 'description')
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$chk = $this->db->query("SELECT * FROM students where student_code ='$student_code' and id != '$id' ")->num_rows;
		if($chk > 0){
			return 2;
			exit;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO students set $data");
		}else{
			$save = $this->db->query("UPDATE students set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_student(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM students where id = $id");
		if($delete){
			return 1;
		}
	}

	function save_result(){
		extract($_POST);
		
		// Basic validation
		if(empty($student_id) || empty($class_id)) {
			return 2;
		}
		
		// Prepare data for results table
		$data = " student_id = '$student_id' ";
		$data .= ", class_id = '$class_id' ";
		$data .= ", date_created = NOW() ";
		
		// Check for existing result
		$chk = $this->db->query("SELECT id FROM results WHERE student_id = '$student_id' AND class_id = '$class_id' AND id != '$id'");
		if($chk->num_rows > 0) {
			return 2;
		}
		
		// Begin transaction
		$this->db->query("START TRANSACTION");
		
		try {
			// Insert or update result
			if(empty($id)) {
				$save = $this->db->query("INSERT INTO results SET $data");
				$id = $this->db->insert_id;
			} else {
				$save = $this->db->query("UPDATE results SET $data WHERE id = $id");
			}
			
			if($save) {
				// Delete existing result items
				$this->db->query("DELETE FROM result_items WHERE result_id = $id");
				
				// Insert new result items
				if(isset($subject_id) && is_array($subject_id)) {
					$total_marks = 0;
					$max_marks = 0;
					$subject_count = 0;
					// Get max marks for each subject
					$subject_max_marks = array();
					$subject_query = $this->db->query("SELECT id, max_mark FROM subjects");
					while($row = $subject_query->fetch_assoc()) {
						$subject_max_marks[$row['id']] = $row['max_mark'] > 0 ? $row['max_mark'] : 100;
					}
					// Delete old student_scores for this student/class
					$this->db->query("DELETE FROM student_scores WHERE student_id = '$student_id' AND class_id = '$class_id'");
					foreach($subject_id as $k => $v) {
						if(!empty($v) && isset($mark[$k])) {
							$item_data = " result_id = $id ";
							$item_data .= ", subject_id = '$v' ";
							$item_data .= ", mark = '{$mark[$k]}' ";
							$this->db->query("INSERT INTO result_items SET $item_data");
							// Calculate for results table
							$m = floatval($mark[$k]);
							$max = isset($subject_max_marks[$v]) ? floatval($subject_max_marks[$v]) : 100;
							$total_marks += $m;
							$max_marks += $max;
							$subject_count++;
							// Calculate grade
							$percentage = $max > 0 ? ($m / $max) * 100 : $m;
							if($percentage >= 90) $grade = 'A+';
							elseif($percentage >= 80) $grade = 'A';
							elseif($percentage >= 70) $grade = 'B';
							elseif($percentage >= 60) $grade = 'C';
							elseif($percentage >= 50) $grade = 'D';
							elseif($percentage >= 40) $grade = 'E';
							else $grade = 'F';
							// Insert into student_scores
							$this->db->query("INSERT INTO student_scores (student_id, class_id, subject_id, mark, grade, date_created) VALUES ('$student_id', '$class_id', '$v', '$m', '$grade', NOW())");
						}
					}
					// Update results.marks_percentage
					$marks_percentage = ($max_marks > 0 && $subject_count > 0) ? round(($total_marks / $max_marks) * 100, 2) : 0;
					$this->db->query("UPDATE results SET marks_percentage = '$marks_percentage' WHERE id = $id");
				}
				
				$this->db->query("COMMIT");
				return 1;
			}
		} catch (Exception $e) {
			$this->db->query("ROLLBACK");
			return 0;
		}
		
		return 0;
	}
	function delete_result(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM results where id = $id");
		if($delete){
			return 1;
		}
	}
	
}