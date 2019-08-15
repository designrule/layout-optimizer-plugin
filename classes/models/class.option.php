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

	public function delete() {
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
			$this->options["contents_group"][$i]["theme"] = $res['theme'];
			$this->options["contents_group"][$i]['gini_coefficient'] = $res['gini_coefficient'];
			$this->options["contents_group"][$i]['json']             = $res;
			$this->options["contents_group"][$i]['last']             = time();
		}
		$this->save();
	}
	function change_theme() {
		foreach ( $this->options["contents_group"] as $contents_group ) {
			if ( ! empty( $contents_group['theme'] ) || empty( $contents_group['optimize_page'] ) ) {
				$post_id = url_to_postid($contents_group['optimize_page']);
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
}