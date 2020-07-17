<?php
class LayoutOptimizerOption {

	public $options = [];
	protected $http;

	public function __construct( $options, $http = null ) {
		$this->options = $options;
		$this->http = $http ? $http : new WP_Http();
	}
	/**
	 * Update with retained data
	 * @return boolean
	 */
	public function save() {
		return update_option( LayoutOptimizerConfig::PLUGIN_DB_KEY, $this->options );
	}
	/**
	 * Return all forms
	 * @return array $comment
	 */
	public static function find() {
		$options = get_option( LayoutOptimizerConfig::PLUGIN_DB_KEY, [] );
		return new LayoutOptimizerOption( $options );
	}

	public static function delete() {
		return delete_option( LayoutOptimizerConfig::PLUGIN_DB_KEY );
	}

	function build_query( $query ) {
		$qstring = http_build_query($query);
		if ( !empty($qstring) ) {
			return "?". $qstring;
		}
		return "";
	}
	function fetch_theme() {
		if ( ! $this->is_api_login() && ! empty( $this->options['view_id'] ) ) {
			return false;
		}
		$pv_list = [];
		for ( $i = 0; $i < count($this->options["contents_group"]); $i++ ) {
			$url      = getenv( 'LAYOUT_OPTIMIZER_API_URL' ) ? getenv( 'LAYOUT_OPTIMIZER_API_URL' ) : 'https://layout-optimizer.herokuapp.com/api/v1/themes/';
			$response = $this->http->get(
				$url . $this->options['view_id'] . $this->build_query($this->options["contents_group"][$i]["query"]),
				[
					'headers' => [
						'uid'          => $this->options['uid'],
						'client'       => $this->options['client'],
						'expiry'       => $this->options['expiry'],
						'access-token' => $this->options['access_token'],
						'Content-Type' => 'application/json',
					],
					'timeout' => 10,
				]
			);
			if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
				return $response;
			}
			$res                      = json_decode( $response['body'], true );
			//開発用テストデータ
			if ( "development" == getenv( 'LAYOUT_OPTIMIZER_ENV' ) ) {
				$res["pages"] = [
					["path" => "/mt-yoshino/", "pv" => 15],
					["path" => "/kinpusen-ji/", "pv" => 14],
					["path" => "/tanzan-jinja/", "pv" => 13],
					["path" => "/2019/08/08/hello-world-2/", "pv" => 12],
					["path" => "/contents1/", "pv" => 11]
				];
			}
			for ( $j = 0; $j < count($res["pages"]); $j++ ) {
				$res["pages"][$j]["post_id"] = $this->url_to_postid($res["pages"][$j]["path"]);
				if ( isset($this->options["contents_group"][$i]["optimize_page_id"]) ) {
					$res["pages"][$j]["optimize_page_id"] = $this->options["contents_group"][$i]["optimize_page_id"];
				}else {
					$res["pages"][$j]["optimize_page_id"] = $this->url_to_postid($this->options["contents_group"][$i]["optimize_page"]);
				}
			}
			$pv_list = array_merge($pv_list, $res["pages"]);
			$this->options["contents_group"][$i]["theme"] = $res['theme'];
			$this->options["contents_group"][$i]['gini_coefficient'] = $res['gini_coefficient'];
			$this->options["contents_group"][$i]['json']             = $res;
			$this->options["contents_group"][$i]['last']             = time();
		}
		LayoutOptimizerGoogleAnalytics::import($pv_list);
		$this->save();
	}
	function change_theme() {
		foreach ( $this->options["contents_group"] as $contents_group ) {
			if ( ! empty( $contents_group['theme'] ) || empty( $contents_group['optimize_page'] ) ) {
				$post_id = 0;
				if ( isset($contents_group['optimize_page_id']) ) {
					$post_id = $contents_group['optimize_page_id'];
				}else {
					$post_id = $this->url_to_postid($contents_group['optimize_page']);
				}
				if ( $post_id == 0 ) {
					continue;
				}
				if ( 'A' === $contents_group['theme'] ) {
					update_post_meta($post_id, "_wp_page_template", "page-a.php");
				} elseif ( 'B' === $contents_group['theme'] ) {
					update_post_meta($post_id, "_wp_page_template", "page-b.php");
				} else {
					update_post_meta($post_id, "_wp_page_template", "page-c.php");
				}
			}
		}
	}
	function is_api_login() {
		return ! ( empty( $this->options['uid'] ) || empty( $this->options['client'] ) || empty( $this->options['expiry'] ) || empty( $this->options['access_token'] ) );
	}
	function url_to_postid( $url ) {
		// First, check to see if there is a 'p=N' or 'page_id=N' to match against.
		if ( preg_match( '#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values ) ) {
			$id = absint( $values[2] );
			if ( $id ) {
				return $id;
			}
		}
		$parsed = parse_url($url);
		if ( $parsed == false ) {
			return 0;
		}
		if ( preg_match( '#(\d+)$#', $parsed["path"], $values ) ) {
			$id = absint( $values[1] );
			if ( $id ) {
				return $id;
			}
		}
		return 0;
	}
}
