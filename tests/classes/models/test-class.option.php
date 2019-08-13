<?php
/**
 * Class LayoutOptimizerOptionsTest
 *
 * @package Layout_Optimizer
 */

class LayoutOptimizerOptionTest extends WP_UnitTestCase {
	/**
	 * @test
	 */
	public function is_api_loginはログインしていない場合falseを返す() {
		$data = new LayoutOptimizerOption([]);
		// Replace this with some actual testing code.
		$this->assertFalse($data->is_api_login());
	}

	/**
	 * @test
	 */
	public function is_api_loginはログインしている場合場合trueを返す() {
		$data = new LayoutOptimizerOption(["uid" => "1", "client"=>"hoge", "expiry" => 123, "access_token" =>"123abc"]);
		// Replace this with some actual testing code.
		$this->assertTrue($data->is_api_login());
	}
}
