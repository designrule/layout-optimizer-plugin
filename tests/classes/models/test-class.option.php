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

	/**
	 * @test
	 */
	public function build_queryはquerystringを組み立てる() {
		$data = new LayoutOptimizerOption([]);
		// Replace this with some actual testing code.
		$this->assertEquals("?hoge=hoge&fuga=%2Ffuga", $data->build_query(["hoge" => "hoge", "fuga" => "/fuga"]));
	}

	/**
	 * @test
	 */
	public function build_queryは引数がから配列の場合空文字を返す() {
		$data = new LayoutOptimizerOption([]);
		// Replace this with some actual testing code.
		$this->assertEquals("", $data->build_query([]));
	}

	/**
	 * @test
	 */
	public function APIの結果をもとに固定ページのテンプレートを切り替える() {
		$post_ids = [];
		$post_ids [] = $this->factory()->post->create(["post_type"=>"page"]);
		$post_ids [] = $this->factory()->post->create(["post_type"=>"page"]);
		$post_ids [] = $this->factory()->post->create(["post_type"=>"page"]);

		$option = [
			"contents_group" => [
				[
					"theme" => "A",
					"optimize_page" => get_permalink($post_ids[0]),
				],
				[
					"theme" => "B",
					"optimize_page" => get_permalink($post_ids[1]),
				],
				[
					"theme" => "C",
					"optimize_page" => get_permalink($post_ids[2]),
				]
			]
		];
		$data = new LayoutOptimizerOption($option);
		$data->change_theme();
		$this->assertEquals("page-a.php", get_post( $post_ids[0] )->page_template);
		$this->assertEquals("page-b.php", get_post( $post_ids[1] )->page_template);
		$this->assertEquals("page-c.php", get_post( $post_ids[2] )->page_template);
	}

	/**
	 * @test
	 */
	public function fetch_themeはコンテンツグループごとにAPIにアクセスしてoptionに保存する() {
		$stub = $this->createMock(WP_Http::class);
		$stub->method("get")->willReturn([
			"response" =>[
				"code" => 200
			],
			"body" => json_encode([
				"theme" => "A",
				"gini_coefficient" => "0.77777",
				"pages" => [
					["path" => "/hoge", "pv" => 100],
					["path" => "/fuga", "pv" => 1],
				]
			])
		]);

		$data = new LayoutOptimizerOption(["view_id" => 1234,
										   "uid" => "1234",
										   "client" => "test",
										   "expiry" => "12345678",
										   "access_token" => "token",
										   "contents_group" => [
											   [
												   "query" =>  ["dir" => "/"],
												   "optimize_page" => "/sample-page"
											   ],
											   [
												   "query" =>  ["dir" => "/"],
												   "optimize_page" => "/sample-page"
											   ]
										   ]
		], $stub);
		$data->fetch_theme();
		$this->assertEquals("A", $data->options["contents_group"][0]["theme"]);
		$this->assertEquals("A", $data->options["contents_group"][1]["theme"]);
	}

	/**
	 * @test
	 */
	public function fetch_themeはAPIサーバーと連携していない場合何もしない() {
		$stub = $this->createMock(WP_Http::class);
		$data = new LayoutOptimizerOption(["view_id" => 1234,
										   "contents_group" => [
											   [
												   "query" =>  ["dir" => "/"],
												   "optimize_page" => "/sample-page"
											   ],
											   [
												   "query" =>  ["dir" => "/"],
												   "optimize_page" => "/sample-page"
											   ]
										   ]
		], $stub);
		$this->assertFalse($data->fetch_theme());
	}

	/**
	 * @test
	 */
	public function fetch_themeはview_idがない場合は何もしない() {
		$stub = $this->createMock(WP_Http::class);
		$data = new LayoutOptimizerOption(["view_id" => 1234,
										   "contents_group" => [
											   [
												   "query" =>  ["dir" => "/"],
												   "optimize_page" => "/sample-page"
											   ],
											   [
												   "query" =>  ["dir" => "/"],
												   "optimize_page" => "/sample-page"
											   ]
										   ]
		], $stub);
		$this->assertFalse($data->fetch_theme());
	}

	/**
	 * @test
	 */
	public function fetch_themeはAPIがエラーになった場合それ以降を取得しない() {
		$stub = $this->createMock(WP_Http::class);
		$stub->method("get")->willReturn([
			"response" =>[
				"code" => 500
			],
			"body" => json_encode([
				"theme" => "A",
				"gini_coefficient" => "0.77777",
				"pages" => [
					["page" => "/hoge", "pv" => 100],
					["page" => "/fuga", "pv" => 1],
				]
			])
		]);

		$data = new LayoutOptimizerOption(["view_id" => 1234,
										   "uid" => "1234",
										   "client" => "test",
										   "expiry" => "12345678",
										   "access_token" => "token",
										   "contents_group" => [
											   [
												   "query" =>  ["dir" => "/"],
												   "optimize_page" => "/sample-page"
											   ],
											   [
												   "query" =>  ["dir" => "/"],
												   "optimize_page" => "/sample-page"
											   ]
										   ]
		], $stub);
		$response = $data->fetch_theme();
		$this->assertEquals(500, $response["response"]["code"]);
	}
}
