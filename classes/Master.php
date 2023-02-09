<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function delete_img(){
		extract($_POST);
		if(is_file($path)){
			if(unlink($path)){
				$resp['status'] = 'success';
			}else{
				$resp['status'] = 'failed';
				$resp['error'] = 'failed to delete '.$path;
			}
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = 'Unkown '.$path.' path';
		}
		return json_encode($resp);
	}
	function save_category(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$v = $this->conn->real_escape_string($v);
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `category_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Category Name already exists.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `category_list` set {$data} ";
		}else{
			$sql = "UPDATE `category_list` set {$data} where id = '{$id}' ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$bid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = "New Category successfully saved.";
			else
				$resp['msg'] = " Category successfully updated.";
			
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
			return json_encode($resp);
	}
	function delete_category(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `category_list` set `delete_flag` = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Category successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_product(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$v = $this->conn->real_escape_string($v);
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `product_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Product Name already exists.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `product_list` set {$data} ";
		}else{
			$sql = "UPDATE `product_list` set {$data} where id = '{$id}' ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$pid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = "New Product successfully saved.";
			else
				$resp['msg'] = " Product successfully updated.";
			if(!empty($_FILES['img']['tmp_name'])){
				$dir = 'uploads/products/';
				if(!is_dir(base_app.$dir))
				mkdir(base_app.$dir);
				$ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
				$fname = $dir.$pid.".png";
				$accept = array('image/jpeg','image/png');
				if(!in_array($_FILES['img']['type'],$accept)){
					$resp['msg'] .= "Image file type is invalid";
				}
				if($_FILES['img']['type'] == 'image/jpeg')
					$uploadfile = imagecreatefromjpeg($_FILES['img']['tmp_name']);
				elseif($_FILES['img']['type'] == 'image/png')
					$uploadfile = imagecreatefrompng($_FILES['img']['tmp_name']);
				if(!$uploadfile){
					$resp['msg'] .= "Image is invalid";
				}
				list($width, $height) = getimagesize($_FILES['img']['tmp_name']);
				if($width > 640 || $height > 480){
					if($width > $height){
						$perc = ($width - 640) / $width;
						$width = 640;
						$height = $height - ($height * $perc);
					}else{
						$perc = ($height - 480) / $height;
						$height = 480;
						$width = $width - ($width * $perc);
					}
				}
				$temp = imagescale($uploadfile,$width,$height);
				if(is_file(base_app.$fname))
				unlink(base_app.$fname);
				$upload =imagepng($temp,base_app.$fname,6);
				if($upload){
					$this->conn->query("UPDATE `product_list` set image_path = CONCAT('{$fname}', '?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$pid}' ");
				}
				imagedestroy($temp);
			}
			
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
			return json_encode($resp);
	}
	function delete_product(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `product_list` set `delete_flag` = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Product successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_sale(){
		if(empty($_POST['id'])){
			$_POST['user_id'] = $this->settings->userdata('id');
			$prefix = date("Ymd");
			$code = sprintf("%'.04d", 1);
			while(true){
				$check = $this->conn->query("SELECT * FROM `sale_list` where code = '{$prefix}{$code}' ")->num_rows;
				if($check > 0){
					$code = sprintf("%'.04d", abs($code) + 1);
				}else{
					$_POST['code'] = $prefix.$code;
					break;
				}
			}
		}
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id')) && !is_array($_POST[$k])){
				if(!empty($data)) $data .=",";
				$v = $this->conn->real_escape_string($v);
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `sale_list` set {$data} ";
		}else{
			$sql = "UPDATE `sale_list` set {$data} where id = '{$id}' ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$sid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['sid'] = $sid;
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = "New Sale successfully saved.";
			else
				$resp['msg'] = " Sale successfully updated.";
			if(isset($product_id)){
				$data = "";
				foreach($product_id as $k =>$v){
					$pid = $v;
					$price = $this->conn->real_escape_string($product_price[$k]);
					$qty = $this->conn->real_escape_string($product_qty[$k]);
					if(!empty($data)) $data .= ", ";
					$data .= "('{$sid}', '{$pid}', '{$qty}', '{$price}')";
				}
				if(!empty($data)){
					$this->conn->query("DELETE FROM `sale_products` where sale_id = '{$sid}'");
					$sql_product = "INSERT INTO `sale_products` (`sale_id`, `product_id`,`qty`, `price`) VALUES {$data}";
					$save_products = $this->conn->query($sql_product);
					if(!$save_products){
						$resp['status'] = 'failed';
						$resp['sql'] = $sql_product;
						$resp['error'] = $this->conn->error;
						if(empty($id)){
							$resp['msg'] = "Sale Transaction has failed save.";
							$this->conn->query("DELETE FROM `sale_products` where sale_id = '{$sid}'");
						}else{
							$resp['msg'] = "Sale Transaction has failed update.";
						}
						return json_encode($resp);
					}
				}
			}
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
			return json_encode($resp);
	}
	function delete_sale(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `sale_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Sale successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function update_status(){
		extract($_POST);
		$update = $this->conn->query("UPDATE `sale_list` set `status` = '{$status}' where id = '{$id}'");
		if($update){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "sale's status has failed to update.";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success', 'sale\'s Status has been updated successfully.');
		return json_encode($resp);
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'delete_img':
		echo $Master->delete_img();
	break;
	case 'save_category':
		echo $Master->save_category();
	break;
	case 'delete_category':
		echo $Master->delete_category();
	break;
	case 'save_product':
		echo $Master->save_product();
	break;
	case 'delete_product':
		echo $Master->delete_product();
	break;
	case 'save_sale':
		echo $Master->save_sale();
	break;
	case 'delete_sale':
		echo $Master->delete_sale();
	break;
	case 'update_status':
		echo $Master->update_status();
	break;
	default:
		// echo $sysset->index();
		break;
}