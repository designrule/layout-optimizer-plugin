<div class="wrap">
	 <h1>Google Analyticsの設定</h1>
        <?php if ( $this->is_api_login($data) && !empty($data['email']) ) { ?>
        <p><?php echo esc_html($data['email']); ?>でログイン中 <input type='button' value='連携解除' class='signout button button-primary button-large'></p>
        <?php }else { ?>
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
        <?php if ( $this->is_api_login($data) ) { ?>
        <form action="" method='post' id="view-id-form">
            <?php wp_nonce_field(self::CREDENTIAL_VIEW_ACTION, self::CREDENTIAL_VIEW_NAME) ?>
			<ul>
            <li>
              <label for="view_id">GoogleAnalyticsのview_id:</label>
              <input type="text" name="view_id" value="<?= esc_attr(!empty($data["view_id"]) ? $data["view_id"]: ""); ?>"/>
            </li>
			<?php for ( $layout_optimizer_i = 0; $layout_optimizer_i < 10; $layout_optimizer_i++ ) { ?>
            <li>
              <label for="optimize_page">最適化するページ:</label>
              <input type="text" name="optimize_page[]" value="<?= esc_attr(!empty($data["contents_group"][$layout_optimizer_i]["optimize_page"]) ? $data["contents_group"][$layout_optimizer_i]["optimize_page"]: ""); ?>"/>
              <label for="dir">集計対象のディレクトリ:</label>
              <input type="text" name="dir[]" value="<?= esc_attr(!empty($data["contents_group"][$layout_optimizer_i]["query"]["dir"]) ? $data["contents_group"][$layout_optimizer_i]["query"]["dir"]: ""); ?>"/>
              <label for="lang">集計対象の言語:</label>
			  <select name="lang[]">
				<option value="ja" <?= esc_attr(!empty($data["contents_group"][$layout_optimizer_i]["query"]["lang"]) && $data["contents_group"][$layout_optimizer_i]["query"]["lang"] == "ja" ? "selected": ""); ?>>日本語</option>
				<option value="en" <?= esc_attr(!empty($data["contents_group"][$layout_optimizer_i]["query"]["lang"]) && $data["contents_group"][$layout_optimizer_i]["query"]["lang"] == "en" ? "selected": ""); ?>>英語</option>
				<option value="ko" <?= esc_attr(!empty($data["contents_group"][$layout_optimizer_i]["query"]["lang"]) && $data["contents_group"][$layout_optimizer_i]["query"]["lang"] == "ko" ? "selected": ""); ?>>韓国語</option>
				<option value="zh-tw" <?= esc_attr(!empty($data["contents_group"][$layout_optimizer_i]["query"]["lang"]) && $data["contents_group"][$layout_optimizer_i]["query"]["lang"] == "zh-tw" ? "selected": ""); ?>>中国語(繁体字)</option>
				<option value="zh-cn" <?= esc_attr(!empty($data["contents_group"][$layout_optimizer_i]["query"]["lang"]) && $data["contents_group"][$layout_optimizer_i]["query"]["lang"] == "zh-cn" ? "selected": ""); ?>>中国語(簡体字)</option>
				<option value="fr" <?= esc_attr(!empty($data["contents_group"][$layout_optimizer_i]["query"]["lang"]) && $data["contents_group"][$layout_optimizer_i]["query"]["lang"] == "fr" ? "selected": ""); ?>>フランス語</option>
			  </select>
            </li>
			<?php } ?>
			</ul>
            <p><input type='submit' value='登録' class='view_id button button-primary button-large' /></p>
        </form>
            <?php if ( !empty($data["contents_group"]) ) { ?>
            <h2>APIの取得結果</h2>
            	<?php foreach ( $data["contents_group"] as $layout_optimizer_content_group ) { ?>
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
