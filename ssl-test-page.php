<?php
/**
 * SSL test page
 *
 * @file       ssl-test-page.php*
 * @package    really-simple-ssl
 * Detection of SSL config
 *
 * Loading this file will output the used $_SERVER headers, which we can use to configure the SSL setup
 */

?>
<html lang="en_US">
<head>
	<meta charset="UTF-8">
	<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
	<title>SSL Test Page</title>
</head>
<body>
<h1>#SSL TEST PAGE#</h1>
<p>This page is used purely to test for SSL availability.</p>
<?php
$rsssl_ssl = false;
if ( isset( $_SERVER['HTTPS'] ) ) {
	if ( strtolower( wp_unslash( $_SERVER['HTTPS'] ) ) === 'on' ) {//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, is a compare.
		echo '#SERVER-HTTPS-ON# ( ' . htmlentities( sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ), ENT_QUOTES, 'UTF-8' ) . ')<br>';//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, is sanitized.
		$rsssl_ssl = true;
	}
	if ( '1' === $_SERVER['HTTPS'] ) {
		echo '#SERVER-HTTPS-1#<br>';
		$rsssl_ssl = true;
	}
}

if ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' === $_SERVER['SERVER_PORT'] ) ) {
	echo '#SERVERPORT443#<br>';
	$rsssl_ssl = true;
}

if ( isset( $_ENV['HTTPS'] ) && ( 'on' === $_ENV['HTTPS'] ) ) {
	echo '#ENVHTTPS#<br>';
	$rsssl_ssl = true;
}

if ( ! empty( $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) && ( 'https' === $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) ) {
	echo '#CLOUDFRONT#<br>';
	$rsssl_ssl = true;
}

if ( ! empty( $_SERVER['HTTP_CF_VISITOR'] ) && ( false !== strpos( wp_unslash( $_SERVER['HTTP_CF_VISITOR'] ), 'https' ) ) ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, just a strpos
	echo '#CLOUDFLARE#<br>';
	$rsssl_ssl = true;
}

if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
	echo '#LOADBALANCER#<br>';
	$rsssl_ssl = true;
}

if ( ! empty( $_SERVER['HTTP_X_PROTO'] ) && ( 'SSL' === $_SERVER['HTTP_X_PROTO'] ) ) {
	echo '#HTTP_X_PROTO#<br>';
	$rsssl_ssl = true;
}

if ( ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && ( 'on' === $_SERVER['HTTP_X_FORWARDED_SSL'] ) ) {
	echo '#HTTP_X_FORWARDED_SSL_ON#<br>';
	$rsssl_ssl = true;
}

if ( ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && ( '1' === $_SERVER['HTTP_X_FORWARDED_SSL'] ) ) {
	echo '#HTTP_X_FORWARDED_SSL_1#<br>';
	$rsssl_ssl = true;
}

if ( $rsssl_ssl ) {
	echo '<br>#SUCCESSFULLY DETECTED SSL#';
} else {
	echo '<br>#NO KNOWN SSL CONFIGURATION DETECTED#';
}
?>

</body>
</html>
