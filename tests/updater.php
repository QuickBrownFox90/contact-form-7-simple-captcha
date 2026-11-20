<?php
/**
 * Checks of the updater against a faked GitHub: which release is offered, what is refused, what is cached.
 * Nothing leaves the machine and nothing stays behind (the cache of the updater is removed at the end).
 *
 * Run on a TEST installation only:   wp eval-file wp-content/plugins/contact-form-7-simple-captcha/tests/updater.php
 */

error_reporting( E_ALL );

require_once dirname( __DIR__ ) . '/inc/updater.php';

$ok = 0; $bad = 0; $calls = 0; $answer = array( 'code' => 200, 'body' => '[]' );

$check = function ( $label, $cond, $info = '' ) use ( &$ok, &$bad ) { $cond ? $ok++ : $bad++; printf( "%-4s %s%s\n", $cond ? 'ok' : 'FAIL', $label, '' !== $info ? "  | $info" : '' ); };

add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$calls, &$answer ) {

	if ( false === strpos( $url, 'api.github.com/repos/QuickBrownFox90/contact-form-7-simple-captcha/releases' ) )
		return $pre;

	$calls++;

	return array( 'headers' => array(), 'body' => $answer['body'], 'response' => array( 'code' => $answer['code'], 'message' => '' ), 'cookies' => array(), 'filename' => null );
}, 10, 3 );

$asset = function ( $name = 'contact-form-7-simple-captcha.zip', $url = null, $tag = 'v0.2.9' ) {
	return array( 'name' => $name, 'browser_download_url' => $url ?? "https://github.com/QuickBrownFox90/contact-form-7-simple-captcha/releases/download/$tag/$name" );
};

$release = function ( $tag, $assets, $extra = array() ) {
	return array_merge( array( 'tag_name' => $tag, 'draft' => false, 'prerelease' => true, 'body' => "Notes of $tag <script>alert(1)</script>\nsecond line", 'published_at' => '2026-11-02T10:00:00Z', 'assets' => $assets ), $extra );
};

$updater = new WPCF7_SimpleCaptcha\Updater( 'QuickBrownFox90/contact-form-7-simple-captcha', dirname( __DIR__ ) . '/contact-form-7-simple-captcha.php' );
$key     = 'wpcf7sc_updater_' . md5( 'QuickBrownFox90/contact-form-7-simple-captcha' );
$reset   = function () use ( $key, &$calls ) { delete_site_transient( $key ); $calls = 0; };
$ttl     = function () use ( $key ) { return (int) get_option( '_site_transient_timeout_' . $key ) - time(); };

echo "-- which release is offered\n";
$answer['body'] = json_encode( array(
	$release( 'v0.3.0', array( $asset( 'contact-form-7-simple-captcha.zip', null, 'v0.3.0' ) ), array( 'draft' => true ) ),                                   // a draft: ignored
	$release( 'v0.2.10', array( $asset( 'other.zip', null, 'v0.2.10' ) ) ),                                                                // newer, but without the ZIP: pending
	$release( 'v0.2.9', array( $asset() ) ),                                                                                               // the one to offer
	$release( 'v1.0.0', array( $asset( 'contact-form-7-simple-captcha.zip', 'https://evil.example/QuickBrownFox90/contact-form-7-simple-captcha/releases/download/v1.0.0/contact-form-7-simple-captcha.zip', 'v1.0.0' ) ) ), // other host: ignored
	$release( 'v1.1.0', array( $asset( 'contact-form-7-simple-captcha.zip', 'https://github.com/someone-else/contact-form-7-simple-captcha/releases/download/v1.1.0/contact-form-7-simple-captcha.zip', 'v1.1.0' ) ) ),   // other repository: ignored
	$release( 'v1.2.0', array( $asset( 'contact-form-7-simple-captcha.zip', 'http://github.com/QuickBrownFox90/contact-form-7-simple-captcha/releases/download/v1.2.0/contact-form-7-simple-captcha.zip', 'v1.2.0' ) ) ),  // not https: ignored
	$release( 'latest', array( $asset( 'contact-form-7-simple-captcha.zip', null, 'latest' ) ) ),                                                              // not a version: ignored
	$release( 'v0.2.8', array( $asset( 'contact-form-7-simple-captcha.zip', null, 'v0.2.8' ) ) ),
) );
$reset(); $r = $updater->get_release_info();
$check( 'newest release with the ZIP, pre-releases included', is_object( $r ) && '0.2.9' === $r->version, $r ? $r->version : 'false' );
$check( 'download link is the ZIP of the repository', $r && 'https://github.com/QuickBrownFox90/contact-form-7-simple-captcha/releases/download/v0.2.9/contact-form-7-simple-captcha.zip' === $r->download_url );
$check( 'a newer release without the ZIP makes the answer short-lived (pending)', $ttl() > 0 && $ttl() <= WPCF7_SimpleCaptcha\Updater::CACHE_TTL_PENDING, $ttl() . ' s' );
$updater->get_release_info(); $updater->get_release_info();
$check( 'the cache answers, GitHub was asked once', 1 === $calls, $calls . ' request(s)' );

$answer['body'] = json_encode( array( $release( 'v0.2.9', array( $asset() ) ), $release( 'v0.2.8', array( $asset( 'contact-form-7-simple-captcha.zip', null, 'v0.2.8' ) ) ) ) );
$reset(); $r = $updater->get_release_info();
$check( 'all releases fine: kept for a day', '0.2.9' === $r->version && $ttl() > WPCF7_SimpleCaptcha\Updater::CACHE_TTL - 10 && $ttl() <= WPCF7_SimpleCaptcha\Updater::CACHE_TTL, $ttl() . ' s' );

add_filter( 'wpcf7sc_updater_prereleases', '__return_false' );
$reset(); $r = $updater->get_release_info();
$check( 'without pre-releases nothing is offered (all are pre-releases)', false === $r );
$updater->get_release_info(); $updater->get_release_info();
$check( 'and "nothing" is cached for an hour, no request on every read', 1 === $calls && $ttl() > WPCF7_SimpleCaptcha\Updater::CACHE_TTL_MISS - 10 && $ttl() <= WPCF7_SimpleCaptcha\Updater::CACHE_TTL_MISS, $calls . ' request(s), ' . $ttl() . ' s' );
remove_filter( 'wpcf7sc_updater_prereleases', '__return_false' );

echo "\n-- GitHub answers with an error (repository not public, rate limit, garbage)\n";
foreach ( array( array( 404, '{"message":"Not Found"}' ), array( 403, '{"message":"rate limit"}' ), array( 200, 'not json' ), array( 200, '{"a":1}' ) ) as $i => $case ) {
	$answer = array( 'code' => $case[0], 'body' => $case[1] ); $reset();
	$r = $updater->get_release_info(); $updater->get_release_info();
	$check( "HTTP {$case[0]} / " . substr( $case[1], 0, 12 ) . ': nothing offered, one request, kept an hour', false === $r && 1 === $calls && $ttl() > WPCF7_SimpleCaptcha\Updater::CACHE_TTL_MISS - 10, $calls . ' request(s)' );
}

echo "\n-- the update data of WordPress\n";
$answer = array( 'code' => 200, 'body' => json_encode( array( $release( 'v0.2.9', array( $asset() ) ) ) ) ); $reset();
$base   = 'contact-form-7-simple-captcha/contact-form-7-simple-captcha.php';
$t      = $updater->check_update( (object) array( 'checked' => array( $base => '0.2.3' ), 'response' => array(), 'no_update' => array() ) );
$check( 'older version installed: update entry with package', isset( $t->response[ $base ] ) && '0.2.9' === $t->response[ $base ]->new_version && 0 === strpos( $t->response[ $base ]->package, 'https://github.com/QuickBrownFox90/' ) && ! isset( $t->no_update[ $base ] ) );
$t      = $updater->check_update( (object) array( 'checked' => array( $base => '0.2.9' ), 'response' => array(), 'no_update' => array() ) );
$check( 'same version installed: listed as "no update" without a package (switch for automatic updates)', ! isset( $t->response[ $base ] ) && isset( $t->no_update[ $base ] ) && '' === $t->no_update[ $base ]->package );
$t      = $updater->check_update( (object) array( 'checked' => array( $base => '0.3.0' ), 'response' => array(), 'no_update' => array() ) );
$check( 'newer version installed: no update', ! isset( $t->response[ $base ] ) );
$check( 'no data of WordPress yet: returned unchanged', false === $updater->check_update( false ) && 'x' === $updater->check_update( (object) array( 'a' => 'x' ) )->a );
$reset(); $answer = array( 'code' => 404, 'body' => '' );
$t = $updater->check_update( (object) array( 'checked' => array( $base => '0.2.3' ), 'response' => array(), 'no_update' => array() ) );
$check( 'GitHub not reachable: no entry at all', empty( $t->response ) && empty( $t->no_update ) );

echo "\n-- the dialog \"View details\"\n";
$answer = array( 'code' => 200, 'body' => json_encode( array( $release( 'v0.2.9', array( $asset() ) ) ) ) ); $reset();
$info = $updater->plugin_info( false, 'plugin_information', (object) array( 'slug' => 'contact-form-7-simple-captcha' ) );
$check( 'data of the release', is_object( $info ) && '0.2.9' === $info->version && 'contact-form-7-simple-captcha' === $info->slug && '8.1' === $info->requires_php && false !== strpos( $info->download_link, 'v0.2.9/contact-form-7-simple-captcha.zip' ) );
$check( 'changelog is escaped (no script) and keeps line breaks', false === strpos( $info->sections['changelog'], '<script' ) && false !== strpos( $info->sections['changelog'], '&lt;script&gt;' ) && false !== strpos( $info->sections['changelog'], '<br' ) );
$check( 'another plugin / another action: passed through', 'x' === $updater->plugin_info( 'x', 'plugin_information', (object) array( 'slug' => 'other' ) ) && 'y' === $updater->plugin_info( 'y', 'query_plugins', (object) array( 'slug' => 'contact-form-7-simple-captcha' ) ) );

echo "\n-- after the update and the link\n";
$updater->get_release_info(); $check( 'cache is there', false !== get_site_transient( $key ) );
$updater->purge_cache( null, array( 'action' => 'update', 'type' => 'plugin', 'plugins' => array( 'other/other.php' ) ) );
$check( 'update of another plugin: cache stays', false !== get_site_transient( $key ) );
$updater->purge_cache( null, array( 'action' => 'update', 'type' => 'plugin', 'plugin' => $base ) );
$check( 'single update of this plugin: cache removed', false === get_site_transient( $key ) );
$updater->get_release_info(); $updater->purge_cache( null, array( 'action' => 'update', 'type' => 'plugin', 'plugins' => array( $base ) ) );
$check( 'bulk update of this plugin: cache removed', false === get_site_transient( $key ) );
$links = $updater->action_links( array() );
$check( 'link "Check for updates" with a nonce', 1 === count( $links ) && false !== strpos( $links[0], 'action=wpcf7sc_check_updates' ) && false !== strpos( $links[0], '_wpnonce=' ) );

echo "\n-- the folder of the unpacked update\n";
$tmp = sys_get_temp_dir() . '/sp-upd-' . getmypid(); mkdir( $tmp . '/QuickBrownFox90-contact-form-7-simple-captcha-abc123', 0777, true ); file_put_contents( $tmp . '/QuickBrownFox90-contact-form-7-simple-captcha-abc123/contact-form-7-simple-captcha.php', '<?php' );
require_once ABSPATH . 'wp-admin/includes/file.php'; WP_Filesystem(); global $wp_filesystem;
$res = $updater->fix_source_folder( $tmp . '/QuickBrownFox90-contact-form-7-simple-captcha-abc123/', $tmp, null, array( 'plugin' => $base ) );
$check( 'folder with another name is renamed to the slug', $tmp . '/contact-form-7-simple-captcha/' === $res && is_file( $tmp . '/contact-form-7-simple-captcha/contact-form-7-simple-captcha.php' ) && ! is_dir( $tmp . '/QuickBrownFox90-contact-form-7-simple-captcha-abc123' ), is_string( $res ) ? basename( $res ) : 'error' );
$check( 'folder that is right is left alone', $tmp . '/contact-form-7-simple-captcha/' === $updater->fix_source_folder( $tmp . '/contact-form-7-simple-captcha/', $tmp, null, array( 'plugin' => $base ) ) );
$check( 'another plugin is not touched', '/x/y/' === $updater->fix_source_folder( '/x/y/', '/x', null, array( 'plugin' => 'other/other.php' ) ) && '/x/y/' === $updater->fix_source_folder( '/x/y/', '/x', null, array() ) );
exec( 'rm -rf ' . escapeshellarg( $tmp ) );

delete_site_transient( $key );
echo "\n$ok ok, $bad failed\n";
