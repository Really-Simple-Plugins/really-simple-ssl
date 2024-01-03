<html>
<head>
	<meta charset="UTF-8">
	<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
</head>
<body>
<h1>#SSL TEST PAGE#</h1>
<p>This page is used purely to test for SSL availability.</p>
<?php
$rsssl_ssl_detected = false;
if ( isset( $_SERVER['HTTPS'] ) ) {
	if ( strtolower( $_SERVER['HTTPS'] ) === 'on' ) {

		echo '#SERVER-HTTPS-ON#' . ' (' . htmlentities( $_SERVER['HTTPS'], ENT_QUOTES, 'UTF-8' ) . ')<br>';
		$rsssl_ssl_detected = true;
	}
	if ( '1' === $_SERVER['HTTPS'] ) {
		echo '#SERVER-HTTPS-1#<br>';
		$rsssl_ssl_detected = true;
	}
}

if ( isset( $_SERVER['SERVER_PORT'] ) && '443' === $_SERVER['SERVER_PORT'] ) {
	echo '#SERVERPORT443#<br>';
	$rsssl_ssl_detected = true;
}

if ( isset( $_ENV['HTTPS'] ) && 'on' === $_ENV['HTTPS'] ) {
	echo '#ENVHTTPS#<br>';
	$rsssl_ssl_detected = true;
}

if ( ! empty( $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) {
	echo '#CLOUDFRONT#<br>';
	$rsssl_ssl_detected = true;
}

if ( ! empty( $_SERVER['HTTP_CF_VISITOR'] ) && false !== strpos( $_SERVER['HTTP_CF_VISITOR'], 'https' ) ) {
	echo '#CLOUDFLARE#<br>';
	$rsssl_ssl_detected = true;
}

if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	echo '#LOADBALANCER#<br>';
	$rsssl_ssl_detected = true;
}

if ( ! empty( $_SERVER['HTTP_X_PROTO'] ) && 'SSL' === $_SERVER['HTTP_X_PROTO'] ) {
	echo '#HTTP_X_PROTO#<br>';
	$rsssl_ssl_detected = true;
}

if ( ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && 'on' === $_SERVER['HTTP_X_FORWARDED_SSL'] ) {
	echo '#HTTP_X_FORWARDED_SSL_ON#<br>';
	$rsssl_ssl_detected = true;
}

if ( ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && '1' === $_SERVER['HTTP_X_FORWARDED_SSL'] ) {
	echo '#HTTP_X_FORWARDED_SSL_1#<br>';
	$rsssl_ssl_detected = true;
}

if ( $rsssl_ssl_detected ) {
	echo '<br>#SUCCESSFULLY DETECTED SSL#';
} else {
	echo '<br>#NO KNOWN SSL CONFIGURATION DETECTED#';
}
?>

</body>
</html>
