<div class="wrap">
	 <h1>Google Analyticsの設定</h1>
        <?php if ( $data->is_api_login() && !empty($data->options['email']) ) { ?>
        <p><?php echo esc_html($data->options['email']); ?>でログイン中 <input type='button' value='連携解除' class='signout button button-primary button-large'></p>
        <?php }else { ?>
        <p><input type='button' value='ログイン' class='signin button button-primary button-large'></p>
        <?php } ?>
        <form action="" method='post' id="my-submenu-form">
            <?php wp_nonce_field(LayoutOptimizerConfig::CREDENTIAL_ACTION, LayoutOptimizerConfig::CREDENTIAL_NAME) ?>
            <p>
              <input type="hidden" name="email" value=""/>
              <input type="hidden" name="uid" value=""/>
              <input type="hidden" name="client" value=""/>
              <input type="hidden" name="expiry" value=""/>
              <input type="hidden" name="access_token" value=""/>
            </p>
        </form>
        <?php if ( $data->is_api_login() ) { ?>
        <form action="#" method='post' id="view-id-form">
            <?php wp_nonce_field(LayoutOptimizerConfig::CREDENTIAL_VIEW_ACTION, LayoutOptimizerConfig::CREDENTIAL_VIEW_NAME) ?>
			<ul>
            <li>
              <label for="view_id">GoogleAnalyticsのview_id:</label>
              <input type="text" name="view_id" value="<?= esc_attr(!empty($data->options["view_id"]) ? $data->options["view_id"]: ""); ?>"/>
            </li>
			<?php for ( $layout_optimizer_i = 0; $layout_optimizer_i < LayoutOptimizerConfig::CONTENTS_GROUP_COUNT; $layout_optimizer_i++ ) { ?>
            <li>
              <label for="optimize_page">最適化するページID:</label>
              <input type="text" name="optimize_page_id[]" value="<?= esc_attr(!empty($data->options["contents_group"][$layout_optimizer_i]["optimize_page_id"]) ? $data->options["contents_group"][$layout_optimizer_i]["optimize_page_id"]: ""); ?>"/>
              <label for="optimize_page">最適化するページ:</label>
              <input type="text" name="optimize_page[]" value="<?= esc_attr(!empty($data->options["contents_group"][$layout_optimizer_i]["optimize_page"]) ? $data->options["contents_group"][$layout_optimizer_i]["optimize_page"]: ""); ?>"/>
              <label for="dir">集計対象のディレクトリ:</label>
              <input type="text" name="dir[]" value="<?= esc_attr(!empty($data->options["contents_group"][$layout_optimizer_i]["query"]["dir"]) ? $data->options["contents_group"][$layout_optimizer_i]["query"]["dir"]: ""); ?>"/>
            </li>
			<?php } ?>
			</ul>
            <p><input type='submit' value='登録' class='view_id button button-primary button-large' /></p>
        </form>
            <?php if ( !empty($data->options["contents_group"]) ) { ?>
            <h2>APIの取得結果</h2>
            	<?php foreach ( $data->options["contents_group"] as $layout_optimizer_content_group ) { ?>
            		<p>theme: <?= $layout_optimizer_content_group["theme"]; ?></p>
            		<p>gini: <?= $layout_optimizer_content_group["gini_coefficient"]; ?></p>
            		<p>json: <?= print_r($layout_optimizer_content_group["json"]); ?></p>
            		<p>updated at: <?= $layout_optimizer_content_group["last"]; ?></p>
		    		<p><?= wp_next_scheduled('my_hourly_event'); ?></p>
		    		<p><?= wp_get_theme(); ?></p>
    			<?php } ?>
    		<?php } ?>
        <?php } ?>
</div>
