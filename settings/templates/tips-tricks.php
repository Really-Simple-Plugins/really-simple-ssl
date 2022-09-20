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
                        <div class="rsssl-icon">
	                        <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" height="15">
								<path fill="var(--rsp-grey-300)" d="M256 512c141.4 0 256-114.6 256-256S397.4 0 256 0S0 114.6 0 256S114.6 512 256 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-144c-17.7 0-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32s-14.3 32-32 32z"/>
	                        </svg>
						</div>
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
