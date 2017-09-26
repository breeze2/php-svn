<?php
namespace BL\LibSvn\Exceptions;

class SvnException extends \Exception {
	public function getOutput() {
		$message = $this->getMessage();
		$a = json_decode($message, true);
		if(isset($a['output'])) {
			if(is_array($a['output'])) {
				return implode(' ', $a['output']);
			}
			if(is_string($a['output'])) {
				return $a['output'];
			}
		}
		return $message;
	}
}