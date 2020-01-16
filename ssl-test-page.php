<html>
<head>
    <meta charset="UTF-8">
    <META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
</head>
<body>
<h1>#SSL TEST PAGE#</h1>
<p>This page is used purely to test for SSL availability.</p>
<?php
$ssl = FALSE;
if (isset($_SERVER['HTTPS'])) {
    if (strtolower($_SERVER['HTTPS']) == 'on') {

        echo "#SERVER-HTTPS-ON#" . " (" . htmlentities($_SERVER['HTTPS'], ENT_QUOTES, 'UTF-8') . ")<br>";
        $ssl = TRUE;
    }
    if ('1' == $_SERVER['HTTPS']) {
        echo "#SERVER-HTTPS-1#<br>";
        $ssl = TRUE;
    }
}

if (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
    echo "#SERVERPORT443#<br>";
    $ssl = TRUE;
}

if (isset($_ENV['HTTPS']) && ('on' == $_ENV['HTTPS'])) {
    echo "#ENVHTTPS#<br>";
    $ssl = TRUE;
}

if (!empty($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']) && ($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] == 'https')) {
    echo "#CLOUDFRONT#<br>";
    $ssl = TRUE;
}

if (!empty($_SERVER['HTTP_CF_VISITOR']) && (strpos($_SERVER["HTTP_CF_VISITOR"], "https") !== false)) {
    echo "#CLOUDFLARE#<br>";
    $ssl = TRUE;
}

if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
    echo "#LOADBALANCER#<br>";
    $ssl = TRUE;
}

if (!empty($_SERVER['HTTP_X_PROTO']) && ($_SERVER['HTTP_X_PROTO'] == 'SSL')) {
    echo "#HTTP_X_PROTO#<br>";
    $ssl = TRUE;
}

if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && ($_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')) {
    echo "#HTTP_X_FORWARDED_SSL_ON#<br>";
    $ssl = TRUE;
}

if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && ($_SERVER['HTTP_X_FORWARDED_SSL'] == '1')) {
    echo "#HTTP_X_FORWARDED_SSL_1#<br>";
    $ssl = TRUE;
}

if ($ssl) {
    echo "<br>#SUCCESSFULLY DETECTED SSL#";
} else {
    echo "<br>#NO KNOWN SSL CONFIGURATION DETECTED#";
}
?>

</body>
</html>
