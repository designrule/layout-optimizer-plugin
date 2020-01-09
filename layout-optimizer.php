<?php
/*
	Plugin Name: LayoutOptimizer
	Plugin URI:
	Description: レイアウトを最適化するプラグイン
	Version: 0.0.3
	Author: designrule
	Author URI: https://github.com/designrule/layout-optimizer-plugin
	License: UNLICENSED
*/
add_action( 'init', 'LayoutOptimizer::init' );
class LayoutOptimizer {

	static function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->_load_initialize_files();
		if ( is_admin() && is_user_logged_in() ) {
			new LayoutOptimizerAdminController();
		}
	 	add_action( 'my_hourly_event', [ $this, 'my_hourly_action' ] );
	 	$this->my_activation();
	 	register_deactivation_hook( __FILE__, 'LayoutOptimizer::my_deactivation' );
	}
	/**
	 * プラグインがロード時に実行する処理.
	 *
	 * @return void
	 */
	public function _load_initialize_files() {
		require_once plugin_dir_path( __FILE__ ) . 'classes/config.php';
		require_once plugin_dir_path( __FILE__ ) . 'classes/functions.php';
		$plugin_dir_path = plugin_dir_path( __FILE__ );
		$includes        = array(
			'/classes/abstract',
			'/classes/controllers',
			'/classes/models',
			'/classes/services',
		);
		foreach ( $includes as $include ) {
			foreach ( glob( $plugin_dir_path . $include . '/*.php' ) as $file ) {
				require_once $file;
			}
		}
	}
	function my_activation() {
		// イベントが未登録なら登録する
		// wp_clear_scheduled_hook('my_hourly_event');
		if ( ! wp_next_scheduled( 'my_hourly_event' ) ) {
			wp_schedule_single_event( time() + ( 60 * 60 ), 'my_hourly_event' );
		}
		LayoutOptimizerGoogleAnalytics::init();
	}

	static function my_deactivation() {
		LayoutOptimizerOption::delete();
		LayoutOptimizerGoogleAnalytics::delete();
		remove_filter('posts_join', 'layout_optimizer_join' );
		remove_filter('posts_where', 'layout_optimizer_where' );
		remove_filter( 'posts_orderby', 'layout_optimizer_orderby' );
		wp_clear_scheduled_hook( 'my_hourly_event' );
	}

	function my_hourly_action() {
		$data = LayoutOptimizerOption::find();
		$data->fetch_theme();
		$data->change_theme();
	}
}

