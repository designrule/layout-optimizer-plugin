<?php
class LayoutOptimizerAdminController {

	public function __construct() {
		if ( is_admin() && is_user_logged_in() ) {
			// メニュー追加
			add_action( 'admin_menu', [ $this, 'set_plugin_menu' ] );
			add_action( 'admin_notices', [ $this, 'flash_messages' ] );
			add_action( 'admin_notices', [ $this, 'flash_alert' ] );
			add_action( 'admin_init', [ $this, 'save_config' ] );
			add_action( 'admin_init', [ $this, 'save_view_id' ] );
		}
	}

	function set_plugin_menu() {
		add_menu_page(
			'レイアウト最適化',          /* ページタイトル*/
			'レイアウト最適化',          /* メニュータイトル */
			'manage_options',            /* 権限 */
			LayoutOptimizerConfig::CONFIG_MENU_SLUG,      /* ページを開いたときのURL */
			[ $this, 'show_config_form' ], /* メニューに紐づく画面を描画するcallback関数 */
			'dashicons-format-gallery',  /* アイコン see: https://developer.wordpress.org/resource/dashicons/#awards */
			99                           /* 表示位置のオフセット */
		);
	}

	/** 設定画面の項目データベースに保存する */
	function save_config() {
		// nonceで設定したcredentialのチェック
		if ( isset( $_POST[ LayoutOptimizerConfig::CREDENTIAL_NAME ] ) && $_POST[ LayoutOptimizerConfig::CREDENTIAL_NAME ] ) {
			if ( check_admin_referer( LayoutOptimizerConfig::CREDENTIAL_ACTION, LayoutOptimizerConfig::CREDENTIAL_NAME ) ) {
				$data = LayoutOptimizerOption::find();
				// 保存処理
				$data->options['email'] = ! empty( $_POST['email'] ) ? $_POST['email'] : '';
				$data->options['uid']          = ! empty( $_POST['uid'] ) ? $_POST['uid'] : '';
				$data->options['client']       = ! empty( $_POST['client'] ) ? $_POST['client'] : '';
				$data->options['expiry']       = ! empty( $_POST['expiry'] ) ? $_POST['expiry'] : '';
				$data->options['access_token'] = ! empty( $_POST['access_token'] ) ? $_POST['access_token'] : '';
				$data->save();
				$completed_text = '連携が完了しました。';

				// 保存が完了したら、WordPressの機構を使って、一度だけメッセージを表示する
				set_transient( LayoutOptimizerConfig::COMPLETE_CONFIG, [ $completed_text ], 5 );

				// 設定画面にリダイレクト
				wp_safe_redirect( menu_page_url( LayoutOptimizerConfig::CONFIG_MENU_SLUG, false ) );
			}
		}
	}

	function save_view_id() {
		// nonceで設定したcredentialのチェック
		if ( isset( $_POST[ LayoutOptimizerConfig::CREDENTIAL_VIEW_NAME ] ) && $_POST[ LayoutOptimizerConfig::CREDENTIAL_VIEW_NAME ] ) {
			if ( check_admin_referer( LayoutOptimizerConfig::CREDENTIAL_VIEW_ACTION, LayoutOptimizerConfig::CREDENTIAL_VIEW_NAME ) ) {
				// 保存処理
				$data = LayoutOptimizerOption::find();
				$data->options['view_id'] = ! empty( $_POST['view_id'] ) ? filter_input( INPUT_POST, "view_id", FILTER_VALIDATE_INT) : '';
				$optimize_page_id = filter_input( INPUT_POST, "optimize_page_id", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
				$optimize_page = filter_input( INPUT_POST, "optimize_page", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
				$dir = filter_input( INPUT_POST, "dir", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
				$data->options["contents_group"] = [];
				for ( $i = 0 ; $i<LayoutOptimizerConfig::CONTENTS_GROUP_COUNT; $i++ ) {
					$contents_group = [
						"optimize_page_id" => $optimize_page_id[$i],
						"optimize_page" => $optimize_page[$i],
						"query" => [ "dir" => $dir[$i] ] ];
					$data->options["contents_group"][] = $contents_group;
				}
				if ( $data->options['view_id'] === false ) {
					$e = new WP_Error();
					$e->add('error', "view_idは数値で入力してください");
					set_transient( LayoutOptimizerConfig::ERROR_MESSAGE, $e->get_error_messages(), 5 );
				}else {
					$data->save();
					$data->fetch_theme();
					$data->change_theme();
					$completed_text = 'view_idと集計対象ディレクトリの保存が完了しました。';
					// 保存が完了したら、WordPressの機構を使って、一度だけメッセージを表示する
					set_transient( LayoutOptimizerConfig::COMPLETE_CONFIG, [ $completed_text ], 5 );
				}
				// 設定画面にリダイレクト
				wp_safe_redirect( menu_page_url( LayoutOptimizerConfig::CONFIG_MENU_SLUG, false ) );
			}
		}
	}

	function show_config_form() {
		wp_enqueue_script( 'layout-optimizer', plugins_url( 'layout-optimizer-plugin/dist/main.js'), [], date( 'U' ) );
		$data = LayoutOptimizerOption::find();
		include __DIR__ . '/../../templates/config_form.php';
	}
	function flash_alert() {
		$messages = get_transient( LayoutOptimizerConfig::ERROR_MESSAGE );
		include __DIR__ . '/../../templates/flash_alert.php';
	}
	function flash_messages() {
		$messages = get_transient( LayoutOptimizerConfig::COMPLETE_CONFIG );
		include __DIR__ . '/../../templates/flash_messages.php';
	}
}
