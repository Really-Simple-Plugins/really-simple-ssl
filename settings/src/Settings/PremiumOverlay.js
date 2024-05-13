import {__} from "@wordpress/i18n";
import useLicense from "./License/LicenseData";

const PremiumOverlay = ({msg, title, url, upgrade}) => {
    const {licenseStatus} = useLicense();
    let pro_plugin_active = rsssl_settings.pro_plugin_active === '1'
    let target = pro_plugin_active ? '_self' : '_blank';
    let upgradeButtonText = pro_plugin_active ? __("Check license", "really-simple-ssl") : __("Go Pro", "really-simple-ssl");
    let upgradeUrl = upgrade ? upgrade : 'https://really-simple-ssl.com/pro/?mtm_campaign=fallback&mtm_source=free&mtm_content=upgrade';
    if (pro_plugin_active) {
        upgradeUrl = '#settings/license';
    }
    let message = msg ? msg : __("Learn more about %sPremium%s", "really-simple-ssl");
    if ( pro_plugin_active ) {
        if (licenseStatus === 'empty' || licenseStatus === 'deactivated') {
            message = rsssl_settings.messageInactive;
        } else {
            message = rsssl_settings.messageInvalid;
        }
    }

    return (
        <div className="rsssl-locked rsssl-locked-premium">
            <div className="rsssl-locked-overlay rsssl-premium">
                {/* header */}
                <div className="rsssl-locked-header">
                    <h5 className={'rsssl-locked-header-title'}>{title}</h5>
                </div>
                <div className="rsssl-locked-content">
                    <span>{message}&nbsp;</span>
                </div>
                <div className="rsssl-locked-footer">
                    {/* We place a button on the left side */}
                    <div className="rsssl-grid-item-footer-buttons">
                        <a
                            className="button button-primary left"
                            href={upgradeUrl} target={target}>{upgradeButtonText}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default PremiumOverlay;