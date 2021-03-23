<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>
<div class="rsssl-tips-tricks">
	<div class="tips-tricks-content">
        <div class="tips-tricks-top">
            <div class="rsssl-tips-tricks-element">
                <div class="rsssl-tips-tricks-content">
			        <?php _e("Is your site still not secure? Do the extensive site scan", "really-simple-ssl")?>
                </div>
                <div class="rsssl-tips-tricks-read-more">
                    <a href="https://really-simple-ssl.com/why-is-my-site-still-not-secure/" target="_blank"><?php _e("Read more", "really-simple-ssl");?></a>
                </div>
            </div>
			<div class="rsssl-tips-tricks-element">
				<div class="rsssl-tips-tricks-content">
					<?php _e("Improve security: Enable HTTP Strict Transport Security (HSTS)", "really-simple-ssl")?>
				</div>
				<div class="rsssl-tips-tricks-read-more">
					<a href="https://really-simple-ssl.com/hsts-http-strict-transport-security-good/" target="_blank"><?php _e("Read more", "really-simple-ssl");?></a>
				</div>
			</div>
			<div class="rsssl-tips-tricks-element">
				<div class="rsssl-tips-tricks-content">
					<?php _e("Improve security: Add security headers", "really-simple-ssl");?>
				</div>
				<div class="rsssl-tips-tricks-read-more">
					<a href="https://really-simple-ssl.com/everything-you-need-to-know-about-security-headers/" target="_blank"><?php _e("Read more", "really-simple-ssl");?></a>
				</div>
			</div>
            <div class="rsssl-tips-tricks-element">
                <div class="rsssl-tips-tricks-content">
                    <?php _e("Adding a Content Security Policy", "really-simple-ssl");?>
                </div>
                <div class="rsssl-tips-tricks-read-more">
                    <a href="https://really-simple-ssl.com/knowledge-base/how-to-use-the-content-security-policy-generator/" target="_blank"><?php _e("Read more", "really-simple-ssl");?></a>
                </div>
            </div>
            <div class="rsssl-tips-tricks-element">
                <div class="rsssl-tips-tricks-content">
			        <?php _e("Adding a Permission Policy", "really-simple-ssl");?>
                </div>
                <div class="rsssl-tips-tricks-read-more">
                    <a href="https://really-simple-ssl.com/knowledge-base/how-to-use-the-permissions-policy-header/" target="_blank"><?php _e("Read more", "really-simple-ssl");?></a>
                </div>
            </div>
            <div class="rsssl-tips-tricks-element">
                <div class="rsssl-tips-tricks-content">
                    <?php _e("Information about landing page redirects", "really-simple-ssl");?>
                </div>
                <div class="rsssl-tips-tricks-read-more">
                    <a href="https://really-simple-ssl.com/knowledge-base/avoid-landing-page-redirects/" target="_blank"><?php _e("Read more", "really-simple-ssl");?></a>
                </div>
            </div>
        </div>
        <div class="tips-tricks-bottom">
            <?php printf(
            __('Any questions? See the %sdocumentation%s or the %sWordPress Forum%s.', 'really-simple-ssl'),
            '<a href="https://really-simple-ssl.com/knowledge-base/" target="_blank">','</a>',  '<a href="https://wordpress.org/support/plugin/really-simple-ssl/" target="_blank">', '</a>' );
            ?>
        </div>
	</div>
</div>
