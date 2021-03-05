<div class="rsssl-wizard-wrap" id="rsssl-wizard">
	<?php //this header is a placeholder to ensure notices do not end up in the middle of our code ?>
    <h1 class="rsssl-notice-hook-element"></h1>
	<div id="rsssl-{page}">
		<div id="rsssl-header">
<!--			<img src="--><?php //echo trailingslashit(rsssl_url)?><!--assets/images/rsssl-logo.svg" alt="rsssl - Terms & conditions">-->
            <div class="rsssl-header-right">
<!--                <a href="https://rsssl.io/docs/" class="link-black" target="_blank">--><?php //_e("Documentation", 'rsssl-wizard ')?><!--</a>-->
<!--                <a href="https://rsssl.io/support" class="button button-black" target="_blank">--><?php //echo _e("Support", 'rsssl-wizard ') ?><!--</a>-->
            </div>
		</div>
		<div id="rsssl-content-area">
			{content}
		</div>
	</div>
</div>
