<?php

set_error_handler(function($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

try {
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$mysqli = new mysqli("127.0.0.1", "admin", "", "vad");
	$mysqli->query("SET names utf8");

	$mysqli->begin_transaction();

	$id = $_POST['id'];
	$dataRaw = $_POST['data'];
	$md5 = md5($_POST['data']);
	$data = json_decode($_POST['data'], true);
	$collection = $_POST['collection'];

	// check record already exists
	$stmt = $mysqli->prepare("SELECT id from declarations where md5=? and collection=?");
	$stmt->bind_param("ss", $md5, $collection);
	$result = $stmt->execute();
	$stmt->store_result();
	$count = $stmt->num_rows();
	if($count > 0) {
		echo "IN";
		exit;
	}

	// insert into declarations table
	$stmt = $mysqli->prepare("INSERT INTO declarations (data, md5, collection, name, year, type, workplace, workplace_role, ip, agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$stmt->bind_param("ssssssssss", $dataRaw, $md5, $collection, $data['name'], $data['year'], $data['type'], $data['workplace'], $data['workplace_role'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
	$stmt->execute();
	$id = $stmt->insert_id;

	// row data that will be used for actual analytics
	foreach($data['sectionData'] as $section => $rows) {
		foreach($rows as $row) {
			$stmt = $mysqli->prepare("INSERT INTO declarations_row_data (declaration_id, collection, name, year, type, workplace, workplace_role, section, row_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$rowEncoded = json_encode($row);
			$stmt->bind_param("issssssss", $id, $collection, $data['name'], $data['year'], $data['type'], $data['workplace'], $data['workplace_role'], $section, $rowEncoded);
			$stmt->execute();
		}
	}

	$mysqli->commit();
	echo "OK";
} catch(\Exception $e) {
	$mysqli->rollback();
	echo $e->getMessage();
}
