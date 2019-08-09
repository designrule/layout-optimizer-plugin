<?php
/*
	Plugin Name: LayoutOptimizer
	Plugin URI:
	Description: レイアウトを最適化するプラグイン
	Version: 0.0.1
	Author: designrule
	Author URI: https://github.com/designrule/layout-optimizer-plugin
	License: UNLICENSED
*/
add_action( 'init', 'LayoutOptimizer::init' );
class LayoutOptimizer {
	const PLUGIN_ID              = 'layout-optimizer';
	const CREDENTIAL_ACTION      = self::PLUGIN_ID . '-nonce-action';
	const CREDENTIAL_NAME        = self::PLUGIN_ID . '-nonce-key';
	const CREDENTIAL_VIEW_ACTION = self::PLUGIN_ID . '-view-nonce-action';
	const CREDENTIAL_VIEW_NAME   = self::PLUGIN_ID . '-view-nonce-key';
	const PLUGIN_DB_KEY          = self::PLUGIN_ID . '-data';
	// config画面のslug
	const CONFIG_MENU_SLUG = self::PLUGIN_ID . '-config';
	const COMPLETE_CONFIG  = self::PLUGIN_ID . '-complete';
	const ERROR_MESSAGE  = self::PLUGIN_ID . '-alert';
	const CONTENTS_GROUP_COUNT = 10;

	static function init() {
		return new self();
	}

	function __construct() {
		if ( is_admin() && is_user_logged_in() ) {
			// メニュー追加
			add_action( 'admin_menu', [ $this, 'set_plugin_menu' ] );
			add_action( 'admin_notices', [ $this, 'flash_messages' ] );
			add_action( 'admin_notices', [ $this, 'flash_alert' ] );
			add_action( 'admin_init', [ $this, 'save_config' ] );
			add_action( 'admin_init', [ $this, 'save_view_id' ] );
		}
		add_action( 'my_hourly_event', [ $this, 'my_hourly_action' ] );
		$this->my_activation();
		// register_activation_hook( __FILE__, 'LayoutOptimizer::my_activation');
		register_deactivation_hook( __FILE__, 'LayoutOptimizer::my_deactivation' );
	}
	function my_activation() {
		// イベントが未登録なら登録する
		// wp_clear_scheduled_hook('my_hourly_event');
		if ( ! wp_next_scheduled( 'my_hourly_event' ) ) {
			wp_schedule_single_event( time() + ( 60 * 60 ), 'my_hourly_event' );
		}
	}

	static function my_deactivation() {
		delete_option( self::PLUGIN_DB_KEY );
		wp_clear_scheduled_hook( 'my_hourly_event' );
	}
	function my_hourly_action() {
		$this->fetch_theme();
		$this->change_theme();
	}
	function change_theme() {
		$data = get_option( self::PLUGIN_DB_KEY );
		foreach ( $data["contents_group"] as $contents_group ) {
			if ( ! empty( $contents_group['theme'] ) || empty( $contents_group['optimize_page'] ) ) {
				$post_id = url_to_postid($contents_group['optimize_page']);
				if ( $post_id == 0 ) {
					continue;
				}
				if ( 'A' === $contents_group['theme'] ) {
					switch_theme( 'twentysixteen' );
					update_post_meta($post_id, "_wp_page_template", "page-a.php");
				} elseif ( 'B' === $contents_group['theme'] ) {
					update_post_meta($post_id, "_wp_page_template", "page-b.php");
				} else {
					update_post_meta($post_id, "_wp_page_template", "page-c.php");
				}
			}
		}
	}
	function is_api_login( $data ) {
		return ! ( empty( $data['uid'] ) || empty( $data['client'] ) || empty( $data['expiry'] ) || empty( $data['access_token'] ) );
	}
	function build_query( $query ) {
		$qstring = http_build_query($query);
		if ( !empty($qstring) ) {
			return "?". $qstring;
		}
		return "";
	}
	function fetch_theme() {
		$data = get_option( self::PLUGIN_DB_KEY );
		if ( ! $this->is_api_login( $data ) && ! empty( $data['view_id'] ) ) {
			return false;
		}
		for ( $i = 0; $i < count($data["contents_group"]); $i++ ) {
			$http     = new WP_Http();
			$url      = getenv( 'LAYOUT_OPTIMIZER_API_URL' ) ? getenv( 'LAYOUT_OPTIMIZER_API_URL' ) : 'https://layout-optimizer.herokuapp.com/api/v1/themes/';
			$response = $http->get(
				$url . $data['view_id'] . $this->build_query($data["contents_group"][$i]["query"]),
				[
					'headers' => [
						'uid'          => $data['uid'],
						'client'       => $data['client'],
						'expiry'       => $data['expiry'],
						'access-token' => $data['access_token'],
						'Content-Type' => 'application/json',
					],
					'timeout' => 10,
				]
			);
			if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
				return $response;
			}
			$res                      = json_decode( $response['body'], true );
			$data["contents_group"][$i]["theme"] = $res['theme'];
			$data["contents_group"][$i]['gini_coefficient'] = $res['gini_coefficient'];
			$data["contents_group"][$i]['json']             = $res;
			$data["contents_group"][$i]['last']             = time();
		}
		update_option( self::PLUGIN_DB_KEY, $data );
	}

	function set_plugin_menu() {
		add_menu_page(
			'レイアウト最適化',          /* ページタイトル*/
			'レイアウト最適化',          /* メニュータイトル */
			'manage_options',            /* 権限 */
			self::CONFIG_MENU_SLUG,      /* ページを開いたときのURL */
			[ $this, 'show_config_form' ], /* メニューに紐づく画面を描画するcallback関数 */
			'dashicons-format-gallery',  /* アイコン see: https://developer.wordpress.org/resource/dashicons/#awards */
			99                           /* 表示位置のオフセット */
		);
	}
	/** 設定画面の項目データベースに保存する */
	function save_config() {
		// nonceで設定したcredentialのチェック
		if ( isset( $_POST[ self::CREDENTIAL_NAME ] ) && $_POST[ self::CREDENTIAL_NAME ] ) {
			if ( check_admin_referer( self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME ) ) {
				// 保存処理
				$data = [
					'email'        => ! empty( $_POST['email'] ) ? $_POST['email'] : '',
					'uid'          => ! empty( $_POST['uid'] ) ? $_POST['uid'] : '',
					'client'       => ! empty( $_POST['client'] ) ? $_POST['client'] : '',
					'expiry'       => ! empty( $_POST['expiry'] ) ? $_POST['expiry'] : '',
					'access_token' => ! empty( $_POST['access_token'] ) ? $_POST['access_token'] : '',
				];
				update_option( self::PLUGIN_DB_KEY, $data );
				$completed_text = '連携が完了しました。';

				// 保存が完了したら、WordPressの機構を使って、一度だけメッセージを表示する
				set_transient( self::COMPLETE_CONFIG, [ $completed_text ], 5 );

				// 設定画面にリダイレクト
				wp_safe_redirect( menu_page_url( self::CONFIG_MENU_SLUG, false ) );
			}
		}
	}
	function save_view_id() {
		// nonceで設定したcredentialのチェック
		if ( isset( $_POST[ self::CREDENTIAL_VIEW_NAME ] ) && $_POST[ self::CREDENTIAL_VIEW_NAME ] ) {
			if ( check_admin_referer( self::CREDENTIAL_VIEW_ACTION, self::CREDENTIAL_VIEW_NAME ) ) {
				// 保存処理
				$data            = get_option( self::PLUGIN_DB_KEY );
				$data['view_id'] = ! empty( $_POST['view_id'] ) ? filter_input( INPUT_POST, "view_id", FILTER_VALIDATE_INT) : '';
				$optimize_page = filter_input( INPUT_POST, "optimize_page", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
				$dir = filter_input( INPUT_POST, "dir", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
				$data["contents_group"] = [];
				for ( $i = 0 ; $i<self::CONTENTS_GROUP_COUNT; $i++ ) {
					$contents_group = ["optimize_page" => $optimize_page[$i],
									   "query" => [ "dir" => $dir[$i] ] ];
					$data["contents_group"][] = $contents_group;
				}
				if ( $data['view_id'] === false ) {
					$e = new WP_Error();
					$e->add('error', "view_idは数値で入力してください");
					set_transient( self::ERROR_MESSAGE, $e->get_error_messages(), 5 );
				}else {
					update_option( self::PLUGIN_DB_KEY, $data );
					$this->fetch_theme();
					$this->change_theme();
					$completed_text = 'view_idと集計対象ディレクトリの保存が完了しました。';
					// 保存が完了したら、WordPressの機構を使って、一度だけメッセージを表示する
					set_transient( self::COMPLETE_CONFIG, [ $completed_text ], 5 );
				}
				// 設定画面にリダイレクト
				wp_safe_redirect( menu_page_url( self::CONFIG_MENU_SLUG, false ) );
			}
		}
	}

	function show_config_form() {
		wp_enqueue_script( 'layout-optimizer', plugins_url( '/dist/main.js', __FILE__ ), array(), date( 'U' ) );
		$data = get_option( self::PLUGIN_DB_KEY );
		include __DIR__ . '/templates/config_form.php';
	}
	function flash_alert() {
		$messages = get_transient( self::ERROR_MESSAGE );
		include __DIR__ . '/templates/flash_alert.php';
	}
	function flash_messages() {
		$messages = get_transient( self::COMPLETE_CONFIG );
		include __DIR__ . '/templates/flash_messages.php';
	}
}

