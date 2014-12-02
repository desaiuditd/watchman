<?php

/**
 * Created by PhpStorm.
 * User: udit
 * Date: 18/02/14
 * Time: 4:30 PM
 */
class WM_TestCase extends WP_UnitTestCase
{
	/**
	 * Ensure that the plugin has been installed and activated.
	 */
	function test_plugin_activated()
	{
		$this->assertTrue( is_plugin_active( 'watchman/watchman.php' ) );
	}

}