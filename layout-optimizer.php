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
		if ( ! empty( $data['theme'] ) ) {
			if ( 'A' === $data['theme'] ) {
				//update_post_meta(2, "_wp_page_template", "");
				switch_theme( 'twentyseventeen' );
			} elseif ( 'B' === $data['theme'] ) {
				//update_post_meta(2, "_wp_page_template", "page-1.php");
				//update_post_meta(2, "_wp_page_template", "");
				switch_theme( 'twentysixteen' );
			} else {
				switch_theme( 'twentynineteen' );
			}
		}
	}
	function is_api_login( $data ) {
		return ! ( empty( $data['uid'] ) || empty( $data['client'] ) || empty( $data['expiry'] ) || empty( $data['access_token'] ) );
	}
	function fetch_theme() {
		$data = get_option( self::PLUGIN_DB_KEY );
		if ( ! $this->is_api_login( $data ) && ! empty( $data['view_id'] ) ) {
			return false;
		}
		$http     = new WP_Http();
		$url      = getenv( 'LAYOUT_OPTIMIZER_API_URL' ) ? getenv( 'LAYOUT_OPTIMIZER_API_URL' ) : 'https://layout-optimizer.herokuapp.com/api/v1/themes/';
		$query    = !empty($data['dir']) ? '?dir='.urlencode($data['dir']):'';
		$response = $http->get(
			$url . $data['view_id'],
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
			return new $response;
		}
		$res                      = json_decode( $response['body'], true );
		$data['theme']            = $res['ja']['theme'];
		$data['gini_coefficient'] = $res['ja']['gini_coefficient'];
		$data['json']             = $res;
		$data['last']             = time();
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
				$data['dir'] = filter_input( INPUT_POST, "dir", FILTER_SANITIZE_STRING) ? filter_input( INPUT_POST, "dir", FILTER_SANITIZE_STRING): '/';
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

