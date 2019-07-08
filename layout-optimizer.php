<?php
/*
  Plugin Name: LayoutOptimizer
  Plugin URI:
  Description: レイアウトを最適化するプラグイン
  Version: 1.0.0
  Author: designrule
  Author URI: https://github.com/designrule/layout-optimizer-plugin
  License: UNLICENSED
 */
//define("FS_METHOD","direct");
add_action('init', 'LayoutOptimizer::init');
class LayoutOptimizer {
    const VERSION           = '1.0.0';
    const PLUGIN_ID         = 'layout-optimizer';
    const CREDENTIAL_ACTION = self::PLUGIN_ID . '-nonce-action';
    const CREDENTIAL_NAME   = self::PLUGIN_ID . '-nonce-key';
    const CREDENTIAL_VIEW_ACTION = self::PLUGIN_ID . '-view-nonce-action';
    const CREDENTIAL_VIEW_NAME   = self::PLUGIN_ID . '-view-nonce-key';
    const PLUGIN_DB_PREFIX  = self::PLUGIN_ID . '_';
    // config画面のslug
    const CONFIG_MENU_SLUG  = self::PLUGIN_ID . '-config';
    const COMPLETE_CONFIG    = self::PLUGIN_ID . '_complete';


    static function init()
    {
        return new self();
    }

    function __construct()
    {
        if (is_admin() && is_user_logged_in()) {
            // メニュー追加
            add_action('admin_menu', [$this, 'set_plugin_menu']);
            add_action('admin_menu', [$this, 'set_plugin_sub_menu']);
            add_action('admin_init', [$this, 'save_config']);
        }
		add_action('my_hourly_event', [$this, 'my_hourly_action']);
		$this->my_activation();
		//register_activation_hook( __FILE__, 'LayoutOptimizer::my_activation');
		register_deactivation_hook( __FILE__, 'LayoutOptimizer::my_deactivation');
    }
	function my_activation() {
		//イベントが未登録なら登録する
		//wp_clear_scheduled_hook('my_hourly_event');
		if(!wp_next_scheduled('my_hourly_event')) {
			wp_schedule_single_event(time()+(60 * 60), 'my_hourly_event');
		}
	}

	static function my_deactivation() {
		delete_option(self::PLUGIN_DB_PREFIX . "_data");
		wp_clear_scheduled_hook('my_hourly_event');
	}

    function set_plugin_menu()
    {
        add_menu_page(
            'レイアウト最適化',           /* ページタイトル*/
            'レイアウト最適化',           /* メニュータイトル */
            'manage_options',         /* 権限 */
            'layout-optimizer',    /* ページを開いたときのURL */
            [$this, 'show_about_plugin'],       /* メニューに紐づく画面を描画するcallback関数 */
            'dashicons-format-gallery', /* アイコン see: https://developer.wordpress.org/resource/dashicons/#awards */
            99                          /* 表示位置のオフセット */
        );
    }
    function set_plugin_sub_menu() {

        add_submenu_page(
            'layout-optimizer',  /* 親メニューのslug */
            '設定',
            '設定',
            'manage_options',
            'layout-optimizer-config',
            [$this, 'show_config_form']);
    }

    function show_about_plugin() {
        $html = "<h1>レイアウト最適化</h1>";
        $html .= "<p>Google Analyticsと連携しレイアウトを最適化します</p>";

        echo $html;
    }

    /** 設定画面の項目データベースに保存する */
    function save_config(){
        // nonceで設定したcredentialのチェック
        if (isset($_POST[self::CREDENTIAL_NAME]) && $_POST[self::CREDENTIAL_NAME]) {
            if (check_admin_referer(self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME)) {
                // 保存処理
                $key = "_data";
                $data = [
                    "email" => $_POST['email'] ? $_POST['email'] : "",
                    "uid" => $_POST['uid'] ? $_POST['uid'] : "",
                    "client" => $_POST['client'] ? $_POST['client'] : "",
                    "expiry" => $_POST['expiry'] ? $_POST['expiry'] : "",
                    "access_token" => $_POST['access_token'] ? $_POST['access_token'] : ""
                ];
                update_option(self::PLUGIN_DB_PREFIX . $key, $data);
                $completed_text = "設定の保存が完了しました。管理画面にログインした状態で、トップページにアクセスし変更が正しく反映されたか確認してください。";

                // 保存が完了したら、wordpressの機構を使って、一度だけメッセージを表示する
                //set_transient(self::COMPLETE_CONFIG, $completed_text, 5);

                // 設定画面にリダイレクト
                wp_safe_redirect(menu_page_url(self::CONFIG_MENU_SLUG), false);
            }
        }
        // nonceで設定したcredentialのチェック
        if (isset($_POST[self::CREDENTIAL_VIEW_NAME]) && $_POST[self::CREDENTIAL_VIEW_NAME]) {
            if (check_admin_referer(self::CREDENTIAL_VIEW_ACTION, self::CREDENTIAL_VIEW_NAME)) {
                // 保存処理
                $key = "_data";
                $data = get_option(self::PLUGIN_DB_PREFIX . "_data");
                $data['view_id'] = $_POST['view_id'] ? $_POST['view_id'] : "";
                update_option(self::PLUGIN_DB_PREFIX . $key, $data);
                $completed_text = "設定の保存が完了しました。管理画面にログインした状態で、トップページにアクセスし変更が正しく反映されたか確認してください。";

                // 保存が完了したら、wordpressの機構を使って、一度だけメッセージを表示する
                set_transient(self::COMPLETE_CONFIG, $completed_text, 5);

                // 設定画面にリダイレクト
                wp_safe_redirect(menu_page_url(self::CONFIG_MENU_SLUG), false);
            }
        }
    }
	function my_hourly_action() {
		$this->fetch_theme();
		$this->change_theme();
	}
	function change_theme() {
		$data = get_option(self::PLUGIN_DB_PREFIX . "_data");
		if(isset($data["theme"])){
			if($data["theme"] == "A"){
				switch_theme("twentyseventeen");
			}else if($data["theme"] == "B"){
				switch_theme("twentysixteen");
			}else{
				switch_theme("twentynineteen");
			}
		}
	}
    function fetch_theme() {
        $data = get_option(self::PLUGIN_DB_PREFIX . "_data");
        $http = new WP_Http();
        $response = $http->get("http://docker.for.mac.host.internal:3000/api/v1/themes/{$data['view_id']}", [ "headers" => ["uid" => $data["uid"], "client" => $data["client"],"expiry" => $data["expiry"],"access-token" => $data["access_token"],"Content-Type" => "application/json"], "timeout" => 10]);
        if ( is_wp_error($response) || $response['response']['code'] != 200 ) {
			new Exception($response);
        }
		$res = json_decode($response['body'], true);
		$data["theme"] = $res["theme"];
		$data["gini_coefficient"] = $res["gini_coefficient"];
		$data["last"] = time();
		update_option(self::PLUGIN_DB_PREFIX . "_data", $data);
    }

    function show_config_form() {
        wp_enqueue_script('layout-optimizer', plugins_url( '/dist/main.js', __FILE__ ),array(),date('U'));
        $data = get_option(self::PLUGIN_DB_PREFIX . "_data");
?>
        <div class="wrap">
        <h1>Google Analyticsの設定</h1>
        <?php if($data['email']) { ?>
        <p><?php echo htmlspecialchars($data['email']); ?>でログイン中 <input type='button' value='連携解除' class='signout button button-primary button-large'></p>
        <?php }else{ ?>
        <p><input type='button' value='ログイン' class='signin button button-primary button-large'></p>
        <?php } ?>
        <form action="" method='post' id="my-submenu-form">
            <?php wp_nonce_field(self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME) ?>
            <p>
              <input type="hidden" name="email" value=""/>
              <input type="hidden" name="uid" value=""/>
              <input type="hidden" name="client" value=""/>
              <input type="hidden" name="expiry" value=""/>
              <input type="hidden" name="access_token" value=""/>
            </p>
        </form>
        <?php if($data['email']) { ?>
        <form action="" method='post' id="view-id-form">
            <?php wp_nonce_field(self::CREDENTIAL_VIEW_ACTION, self::CREDENTIAL_VIEW_NAME) ?>
            <p>
              <label for="view_id">GoogleAnalyticsのview_id:</label>
              <input type="text" name="view_id" value="<?= isset($data["view_id"]) ? $data["view_id"]: "" ?>"/>
            </p>
            <p><input type='submit' value='登録' class='view_id button button-primary button-large' /></p>
        </form>
            <?php if(isset($data["theme"])) { ?>
            <h2>APIの取得結果</h2>
            <p>theme: <?= $data["theme"]; ?></p>
            <p>gini: <?= $data["gini_coefficient"]; ?></p>
            <p>updated at: <?= $data["last"]; ?></p>
		    <p><?= wp_next_scheduled('my_hourly_event'); ?></p>
		    <p><?= wp_get_theme(); ?></p>
    		<?php } ?>
        <?php } ?>
      </div>
<?php
    }

}

