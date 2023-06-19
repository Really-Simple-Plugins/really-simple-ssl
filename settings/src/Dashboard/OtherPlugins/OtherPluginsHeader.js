import { __ } from '@wordpress/i18n';
const OtherPluginsHeader = () => {
    return (
        <>
            <h3 className="rsssl-grid-title rsssl-h4">{  __( "Other Plugins", 'really-simple-ssl' ) }</h3>
            <div className="rsssl-grid-item-controls">
                <span className="rsssl-header-html">
                    <a className="rsp-logo" href="https://really-simple-plugins.com/"><img src={rsssl_settings.plugin_url+"assets/img/really-simple-plugins.svg"} alt="Really Simple Plugins"/></a>
                </span>
            </div>
        </>
    )
}

export default OtherPluginsHeader;