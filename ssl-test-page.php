<html>
<head>
 <meta charset="UTF-8">
 <META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
</head>
<body>
<h1>#SSL TEST PAGE#</h1>
<p>This page is used purely to test for ssl availability.</p>
<?php
	$ssl = FALSE;
	if (isset($_SERVER['HTTPS']) ) {
		if ( strtolower($_SERVER['HTTPS']) == 'on') {

			echo "#SERVER-HTTPS-ON#"." (".htmlentities($_SERVER['HTTPS'], ENT_QUOTES, 'UTF-8').")<br>";
			$ssl = TRUE;
		}
		if ( '1' == $_SERVER['HTTPS'] ) {
			echo "#SERVER-HTTPS-1#<br>";
			$ssl = TRUE;
		}
	}

	if (isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] )) {
			echo "#SERVERPORT443#<br>";
			$ssl = TRUE;
	}

  if (isset($_ENV['HTTPS']) && ( 'on' == $_ENV['HTTPS'] )) {
      echo "#ENVHTTPS#<br>";
      $ssl = TRUE;
  }

  if (!empty($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']) && ($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] == 'https')){
    echo "#CLOUDFRONT#<br>";
    $ssl = TRUE;
  }

  if(!empty($_SERVER['HTTP_CF_VISITOR']) && ($_SERVER['HTTP_CF_VISITOR'] == 'https')){
    echo "#CLOUDFLARE#<br>";
    $ssl = TRUE;
  }

  if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
    echo "#LOADBALANCER#<br>";
    $ssl = TRUE;
  }

  if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && ($_SERVER['HTTP_X_FORWARDED_SSL'] == 'on' || $_SERVER['HTTP_X_FORWARDED_SSL'] == 1)){
    echo "#CDN#<br>";
    $ssl = TRUE;
  }

	if ($ssl) {
		echo "<br>#SUCCESFULLY DETECTED SSL#";
	} else {
		echo "<br>#NO KNOWN SSL CONFIGURATION DETECTED#";
	}
?>

</body>
</html>
