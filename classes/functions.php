<?php
function layout_optimizer_is_optimize_page() {
	$template = get_post_meta(get_the_ID(), '_wp_page_template', true);
	return (boolean)(!empty($template) && preg_match("/^page-[abc]\\.php$/", $template));
}
function layout_optimizer_join($join) {
	global $wpdb;
	if(!layout_optimizer_is_optimize_page()) {
		return $join;
	}
	$join .= " INNER JOIN {$wpdb->prefix}googleanalytics ON $wpdb->posts.ID = wp_googleanalytics.post_id ";
	return $join;
}

function layout_optimizer_where($where) {
	global $wpdb;
	if(!layout_optimizer_is_optimize_page()){
		return $where;
	}
	$optimize_page_id = get_the_ID();
	//var_dump($optimize_page_id);
	//$where .= " AND wp_googleanalytics.contents_group = 0 ";
	$where .= $wpdb->prepare(" AND {$wpdb->prefix}googleanalytics.optimize_page_id = %d ", $optimize_page_id);
	return $where;
}

function layout_optimizer_orderby ( $where ) {
	global  $wpdb;
	if(!layout_optimizer_is_optimize_page()){
		return $where;
	}
	$where =  " {$wpdb->prefix}googleanalytics.pv DESC ";
	return $where;

}
add_filter('posts_join', 'layout_optimizer_join' );
add_filter('posts_where', 'layout_optimizer_where' );
add_filter( 'posts_orderby', 'layout_optimizer_orderby' );
