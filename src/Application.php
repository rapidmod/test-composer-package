<?php
namespace Rapidmod;

class Application {
	const VERSION = "0.0.4";
	/**
	 * @required classes
	 */
	protected static $_CONFIG;

	public static function config() {
		if ( is_null( \Rapidmod\Application::$_CONFIG ) ) {
			\Rapidmod\Application::$_CONFIG = new \Rapidmod\Data\Model();
		}

		return \Rapidmod\Application::$_CONFIG;


	}
}
?>




