<div class="wrap">
	 <h1>Google Analyticsの設定</h1>
        <?php if($this->is_api_login($data) && !empty($data['email'])) { ?>
        <p><?php echo esc_html($data['email']); ?>でログイン中 <input type='button' value='連携解除' class='signout button button-primary button-large'></p>
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
        <?php if($this->is_api_login($data)) { ?>
        <form action="" method='post' id="view-id-form">
            <?php wp_nonce_field(self::CREDENTIAL_VIEW_ACTION, self::CREDENTIAL_VIEW_NAME) ?>
            <p>
              <label for="view_id">GoogleAnalyticsのview_id:</label>
              <input type="text" name="view_id" value="<?= esc_attr(!empty($data["view_id"]) ? $data["view_id"]: ""); ?>"/>
            </p>
            <p><input type='submit' value='登録' class='view_id button button-primary button-large' /></p>
        </form>
            <?php if(!empty($data["theme"])) { ?>
            <h2>APIの取得結果</h2>
            <p>theme: <?= $data["theme"]; ?></p>
            <p>gini: <?= $data["gini_coefficient"]; ?></p>
            <p>updated at: <?= $data["last"]; ?></p>
		    <p><?= wp_next_scheduled('my_hourly_event'); ?></p>
		    <p><?= wp_get_theme(); ?></p>
    		<?php } ?>
        <?php } ?>
</div>
