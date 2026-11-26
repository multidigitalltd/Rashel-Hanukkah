#!/usr/bin/env bash
#
# Build a throwaway WordPress on SQLite with the theme activated, and serve it
# on 127.0.0.1:8089 for the end-to-end scripts.
#
# Usage: bash tests/e2e/setup.sh <work-dir>
# Needs: PHP 8.3 with pdo_sqlite, git.
#
# WordPress core and the SQLite integration come from their public GitHub
# mirrors at pinned tags. Set RSH_E2E_WP_SRC / RSH_E2E_SQLITE_SRC to reuse
# checkouts that are already on disk.
#
set -euo pipefail

work="$1"
repo="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
here="$repo/tests/e2e"
wp="$work/wp"
wp_tag="7.1.5"
sqlite_tag="v3.1.3"
port="${RSH_E2E_PORT:-8089}"

mkdir -p "$work/cache" "$work/mails" "$work/shots"

wp_src="${RSH_E2E_WP_SRC:-$work/cache/wordpress}"
sqlite_src="${RSH_E2E_SQLITE_SRC:-$work/cache/sqlite}"

if [ ! -f "$wp_src/wp-load.php" ]; then
	git clone --quiet --depth 1 --branch "$wp_tag" https://github.com/WordPress/WordPress "$wp_src"
fi
if [ ! -d "$sqlite_src/packages" ]; then
	git clone --quiet --depth 1 --branch "$sqlite_tag" https://github.com/WordPress/sqlite-database-integration "$sqlite_src"
fi

rm -rf "$wp"
mkdir -p "$wp"
( cd "$wp_src" && git archive HEAD ) | tar -x -C "$wp"
rm -rf "$wp/wp-content/themes/"* "$wp/wp-content/plugins/"*
mkdir -p "$wp/wp-content/mu-plugins" "$wp/wp-content/database"

# The plugin links the SQLite driver with a symlink: -L copies the real files.
cp -rL "$sqlite_src/packages/plugin-sqlite-database-integration" "$wp/wp-content/plugins/sqlite-database-integration"
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$wp/wp-content/plugins/sqlite-database-integration#" \
	-e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
	"$wp/wp-content/plugins/sqlite-database-integration/db.copy" > "$wp/wp-content/db.php"

ln -s "$repo/rashel-hanukkah" "$wp/wp-content/themes/rashel-hanukkah"

salts="$(php -r 'foreach (array("AUTH_KEY","SECURE_AUTH_KEY","LOGGED_IN_KEY","NONCE_KEY","AUTH_SALT","SECURE_AUTH_SALT","LOGGED_IN_SALT","NONCE_SALT") as $k) { echo "define( \"$k\", \"" . bin2hex( random_bytes( 32 ) ) . "\" );\n"; }')"

cat > "$wp/wp-config.php" <<CONFIG
<?php
define( 'DB_NAME', 'wp_e2e' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'DB_DIR', '$wp/wp-content/database' );
define( 'DB_FILE', 'e2e.sqlite' );
$salts
\$table_prefix = 'wp_';
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', '$work/debug.log' );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_HOME', 'http://127.0.0.1:$port' );
define( 'WP_SITEURL', 'http://127.0.0.1:$port' );
define( 'DISABLE_WP_CRON', true );
// No outbound HTTP (update checks would stall the single-threaded server).
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
require_once ABSPATH . 'wp-settings.php';
CONFIG

# Outgoing mail is written to files the scripts read (no SMTP in tests).
cat > "$wp/wp-content/mu-plugins/e2e-mail-capture.php" <<MAIL
<?php
add_filter( 'pre_wp_mail', static function ( \$short, array \$atts ) {
	file_put_contents( '$work/mails/' . gmdate( 'His' ) . '-' . substr( md5( wp_json_encode( \$atts ) . microtime() ), 0, 6 ) . '.json', wp_json_encode( \$atts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	return true;
}, 10, 2 );
MAIL

# No update checks: they need WordPress.org, which a sandbox may not reach.
# PHP's built-in server sends pages uncompressed; every production server
# compresses them, so the performance budget is measured the same way.
cat > "$wp/wp-content/mu-plugins/e2e-gzip.php" <<'GZIP'
<?php
add_action(
	'template_redirect',
	static function () {
		if ( ! headers_sent() && extension_loaded( 'zlib' ) ) {
			ob_start( 'ob_gzhandler' );
		}
	},
	0
);
GZIP

cat > "$wp/wp-content/mu-plugins/e2e-no-updates.php" <<'NOUPDATES'
<?php
add_action(
	'init',
	static function () {
		remove_action( 'admin_init', '_maybe_update_core' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_themes' );
		remove_action( 'wp_version_check', 'wp_version_check' );
		remove_action( 'wp_update_plugins', 'wp_update_plugins' );
		remove_action( 'wp_update_themes', 'wp_update_themes' );
	}
);
add_filter( 'pre_site_transient_update_core', '__return_null' );
add_filter( 'pre_site_transient_update_plugins', '__return_null' );
add_filter( 'pre_site_transient_update_themes', '__return_null' );
NOUPDATES

php "$here/install.php" "$wp" core
php "$here/install.php" "$wp" theme

if [ -f "$work/server.pid" ] && kill -0 "$(cat "$work/server.pid")" 2>/dev/null; then
	kill "$(cat "$work/server.pid")" || true
fi

PHP_CLI_SERVER_WORKERS=4 nohup php -S "127.0.0.1:$port" -t "$wp" "$here/router.php" > "$work/server.log" 2>&1 &
echo $! > "$work/server.pid"

for _ in $(seq 1 40); do
	if curl -fs -o /dev/null "http://127.0.0.1:$port/"; then
		echo "WordPress is serving on http://127.0.0.1:$port (work dir: $work)"
		exit 0
	fi
	sleep 0.5
done

echo "The server did not start; see $work/server.log" >&2
exit 1
