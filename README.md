# layout-optimizer-plugin

### 設定

	DBのパスワード等を環境変数に設定
	cp .env.sample .env
	docker-compose build
    docker-compose up -d
	docker-compose exec wordpress bash -c "cd /var/www/html/wp-content/plugins/my-plugin; composer install"
	docker-compose exec wordpress bash -c "cd /var/www/html/wp-content/plugins/my-plugin; npm install"	
	docker-compose exec wordpress bash -c "cd /var/www/html/wp-content/plugins/my-plugin; npx webpack --mode=development"	

### WordPressの設定

ブラウザでWordPressにアクセスして手動でインストールしてもOK。

    docker-compose exec wordpress wp core install --allow-root --url=http://localhost/ --title=testing --admin_user=admin --admin_email=admin@example.com
	
### テスト環境の準備

ユニットテストの実行に必要なファイルがコンテナ内の/tmp以下に展開され、テスト用DBが作成される。
localボリュームに永続化しているので、1回だけやればOK
MySQLのrootパスワードは.envファイルで設定したMYSQL_ROOT_PASSWORDを指定する。

    docker-compose exec wordpress bash -c "/var/www/html/wp-content/plugins/layout-optimizer/bin/install-wp-tests.sh wordpress_test root 'wordpress' db latest"


### PHPUnitの実行

    docker-compose exec wordpress bash -c "cd /var/www/html/wp-content/plugins/my-plugin; vendor/bin/phpunit"

### phpcsの実行

    docker-compose exec wordpress bash -c "cd /var/www/html/wp-content/plugins/my-plugin; composer phpcs"

### phpcbf(自動フォーマット)の実行

    docker-compose exec wordpress bash -c "cd /var/www/html/wp-content/plugins/my-plugin; composer phpcbf"

### リリース方法

- layout-optimizer.phpのVersionコメントを修正してマージ
- [Releases · designrule/layout-optimizer-plugin](https://github.com/designrule/layout-optimizer-plugin/releases)からタグを作成

