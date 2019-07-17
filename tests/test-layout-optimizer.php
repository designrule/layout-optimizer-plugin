<?php
/**
 * Class SampleTest
 *
 * @package Layout_Optimizer
 */

/**
 * Sample test case.
 */
class LayoutOptimizerTest extends WP_UnitTestCase {
	/**
	 * @test
	 */
	public function is_api_loginはログインしていない場合falseを返す() {
		$data = [];
		$plugin = new LayoutOptimizer();
		// Replace this with some actual testing code.
		$this->assertFalse($plugin->is_api_login($data));
	}

	/**
	 * @test
	 */
	public function is_api_loginはログインしている場合場合trueを返す() {
		$data = ["uid" => "1", "client"=>"hoge", "expiry" => 123, "access_token" =>"123abc"];
		$plugin = new LayoutOptimizer();
		// Replace this with some actual testing code.
		$this->assertTrue($plugin->is_api_login($data));
	}
}
