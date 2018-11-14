<?php

class Services_JSON {
	public function encode($obj) {
		return json_encode($obj);
	}
	public function decode($str) {
		return json_decode($str, false);
	}
	public function print_json($obj) {
		header("Content-Type: application/json");
		echo $this->encode($obj);
	}
	public function exception($code, $ex) {
		header("Content-Type: application/json");
		http_response_code($code);
		$return = array("status" => "failed", "text" => $ex->getMessage());
		echo $this->encode($return);
		exit;
	}
	public function failure($code, $message) {
		header("Content-Type: application/json");
		http_response_code($code);
		$return = array("status" => "failed", "text" => $message);
		echo $this->encode($return);
		exit;
	}
}