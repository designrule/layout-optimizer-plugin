<?php
class LayoutOptimizerGoogleAnalytics {
	protected $id;
	protected $post_id;
	protected $path;
	protected $pv;
	protected $optimize_page;

	public function __construct( $id, $post_id, $path, $pv, $optimize_page_id ) {
		$this->id = $id;
		$this->path = $path;
		$this->post_id = $post_id;
		$this->pv = $pv;
		$this->optimize_page_id = $optimize_page_id;
	}
	public static function init() {
		global $wpdb;
		$table_name = $wpdb->prefix . "googleanalytics";
		$charset_collate = $wpdb->get_charset_collate();
		$data = LayoutOptimizerOption::find();
		if ( !isset($data->options["db_version"]) || $data->options["db_version"] != LayoutOptimizerConfig::PLUGIN_DB_VERSION ) {
			$sql = "CREATE TABLE $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    path text NOT NULL,
    post_id mediumint(9) NOT NULL,
    pv text NOT NULL,
    optimize_page_id mediumint(9) NOT NULL,
    KEY post_id_index(post_id)
  ) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			$data = LayoutOptimizerOption::find();
			$data->options["db_version"] = LayoutOptimizerConfig::PLUGIN_DB_VERSION;
			$data->save();
		}
	}

	public static function delete() {
		global $wpdb;
		$table_name = $wpdb->prefix . "googleanalytics";
		$sql = "DROP TABLE $table_name";
		$wpdb->query($sql);
	}

	public static function import( $pages ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "googleanalytics";
		$wpdb->query("TRUNCATE TABLE $table_name");
		$arr = [];
		$place_holders = [];
		foreach ( $pages as $page ) {
			$arr []= $page["path"];
			$arr []= $page["post_id"];
			$arr []= $page["pv"];
			$arr []= $page["optimize_page_id"];
			$place_holders[] = '(%s, %d, %d, %d)';
		}
		$sql = "INSERT INTO $table_name (path, post_id, pv, optimize_page_id) VALUES ".join(',', $place_holders);
		$wpdb->query( $wpdb->prepare($sql, $arr) );
	}
}
