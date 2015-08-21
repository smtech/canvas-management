<?php

class CanvasManagement {
	
	public $api = null;
	
	private static $singleton = null;
	
	public static function getInstance() {
		if (self::$singleton === null) {
			self::$singleton = new self();
		}
		return self::$singleton;
	}
	
	/** singleton */
	private function __construct() {
		$this->api = new CanvasPest($_SESSION['apiUrl'], $_SESSION['apiToken']);
		
		if (!$this->validateUser()) {
			throw new CanvasManagement_Exception(
				'Invalid user',
				CanvasManagement_Exception::INVALID_USER
			);
		}
	}
	
	/** singleton */
	private function __wakeup() {}
	
	/** singleton */
	private function __clone() {}
	
	/** Check if the current user is a root-level AccountAdmin */
	private function validateUser() {
		global $toolProvider; // FIXME grown-ups don't program like this
		
		$admins = $this->api->get(
			'accounts/1/admins',
			array(
				'user_id' => $toolProvider->user->getResourceLink()->settings['custom_canvas_user_id']
			)
		);

		if ($admins->count() === 1) {
			return true;
		} else {
			return false;
		}
	}
}	

class CanvasManagement_Exception extends Exception {
	const INVALID_USER = 1;
}

?>