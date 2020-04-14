<?php
class LayoutOptimizerConfig {
	const PLUGIN_ID              = 'layout-optimizer';
	const CREDENTIAL_ACTION      = self::PLUGIN_ID . '-nonce-action';
	const CREDENTIAL_NAME        = self::PLUGIN_ID . '-nonce-key';
	const CREDENTIAL_VIEW_ACTION = self::PLUGIN_ID . '-view-nonce-action';
	const CREDENTIAL_VIEW_NAME   = self::PLUGIN_ID . '-view-nonce-key';
	const PLUGIN_DB_KEY          = self::PLUGIN_ID . '-data';
	const PLUGIN_DB_VERSION      = 202004141349;
	// config画面のslug
	const CONFIG_MENU_SLUG = self::PLUGIN_ID . '-config';
	const COMPLETE_CONFIG  = self::PLUGIN_ID . '-complete';
	const ERROR_MESSAGE  = self::PLUGIN_ID . '-alert';
	const CONTENTS_GROUP_COUNT = 10;
}
