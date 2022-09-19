<?php defined( 'ABSPATH' ) or die();
$items = array(
	1 => array(
		'content' => "Improve security: Add Cross-Site Protection Headers to prevent malicious attacks",
		'link'    => 'https://really-simple-ssl.com/cross-origin-security-headers/',
	),
	2 => array(
		'content' => "Improve security: Enable HTTP Strict Transport Security (HSTS)",
		'link'    => 'https://really-simple-ssl.com/hsts-http-strict-transport-security-good/',
	),
	3 => array(
		'content' => "Improve security: Add security headers",
		'link'    => 'https://really-simple-ssl.com/everything-you-need-to-know-about-security-headers/',
	),
	4 => array(
		'content' => "Adding a Content Security Policy (CSP)",
		'link'    => 'https://really-simple-ssl.com/knowledge-base/how-to-use-the-content-security-policy-generator/',
	),
	5 => array(
		'content' => "Adding a Permission Policy",
		'link'    => 'https://really-simple-ssl.com/knowledge-base/how-to-use-the-permissions-policy-header/',
	),
	6 => array(
		'content' => "Information about landing page redirects",
		'link'    => 'https://really-simple-ssl.com/knowledge-base/avoid-landing-page-redirects/',
	),
);

$container = '<div class="rsssl-tips-tricks-element">
                    <a href="{link}" target="_blank" title="{content}">
                        <div class="rsssl-bullet"></div>
                        <div class="rsssl-tips-tricks-content">{content}</div>
                    </a>
                </div>';

$output    = '<div class="rsssl-tips-tricks-container">';
foreach ( $items as $item ) {
	$output .= str_replace( array(
		'{link}',
		'{content}',
	), array(
		$item['link'],
		$item['content'],
	), $container );
}
$output .= '</div>';
echo $output;
?>
