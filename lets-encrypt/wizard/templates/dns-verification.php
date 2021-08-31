<?php
defined( 'ABSPATH' ) or die(  );
rsssl_progress_add('dns-verification');
?>
<div class="rsssl-section">
	<div class="rsssl-hidden rsssl-get_dns_token rsssl-show-on-success">
        <h2><?php _e("Next step", "really-simple-ssl"); ?></h2>
        <p><?php echo __("Add the following token as text record to your DNS records. We recommend to use a short TTL during installation, in case you need to change it.", "really-simple-ssl").
                 rsssl_read_more("https://really-simple-ssl.com/how-to-add-a-txt-record-to-dns/"); ?></p>
        <div id="rsssl-dns-text-records"></div>
		<script>
            jQuery(document).ready(function ($) {
                $(document).on("rsssl_le_response", rsssl_dns_data_handler);
                function rsssl_dns_data_handler(response) {
                    if (response.detail.status==='success') {
                        var tokens = JSON.parse(response.detail.output);
                          for (var identifier in tokens) {
                            if (tokens.hasOwnProperty(identifier)) {
                                var token = tokens[identifier];
                                var inputField = '<div class="rsssl-dns-label" >@/<?php _e("domain","really-simple-ssl") ?></div><div class="rsssl-dns-field rsssl-selectable">_acme-challenge.'+identifier+'</div>';
                                inputField += '<div class="rsssl-dns-label" ><?php _e("Value","really-simple-ssl") ?></div><div class="rsssl-dns-field rsssl-selectable">'+token+'</div>';
                                $("#rsssl-dns-text-records").append(inputField);
                            }
                        }
                    }
                }
            });
		</script>
	</div>
</div>
