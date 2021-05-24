<?php defined( 'ABSPATH' ) or die( "you do not have access to this page!" ); ?>

<div class="rsssl-section">
	<div class="rsssl-template-intro">
		<p>

		</p>
	</div>

    <div class="rsssl-hidden rsssl-verify_dns rsssl-show-on-warning">
        <p><?php _e("We could not check the DNS records. Possibly your hosting company blocks the check.","really-simple-ssl")?>&nbsp;
        <?php sprintf(__("You can manually check the DNS records in an %sonline tool%s.","really-simple-ssl"),'<a target="_blank" href="https://mxtoolbox.com/SuperTool.aspx">','</a>')?>
	    <?php _e("If you're sure it's set correctly, you can click the button to skip the DNS check.","really-simple-ssl")?>&nbsp;
        </p>
        <br>
        <input class="button button-default" type="submit" value="<?php _e("Skip DNS check",'really-simple-ssl')?>" name="rsssl-skip-dns-check">
    </div>
</div>
