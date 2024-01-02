import {__} from "@wordpress/i18n";
import Hyperlink from "../utils/Hyperlink";
import useLicense from "./License/LicenseData";

const PremiumOverlay = ({msg, title, url, upgrade}) => {
    const {licenseStatus} = useLicense();
    let pro_plugin_active = rsssl_settings.pro_plugin_active;
    let upgradeUrl = upgrade ? upgrade : 'https://really-simple-ssl.com/pro/?mtm_campaign=fallback&mtm_source=free&mtm_content=upgrade';
    let message = msg ? msg : __("Learn more about %sPremium%s", "really-simple-ssl");
    if (rsssl_settings.pro_plugin_active) {
        if (licenseStatus === 'empty' || licenseStatus === 'deactivated') {
            message = rsssl_settings.messageInactive;
        } else {
            message = rsssl_settings.messageInvalid;
        }
        if (rsssl_settings.pro_incompatible) {
            message = __("You are using an incompatible version of Really Simple SSL pro. Please update to the latest version.", "really-simple-ssl");
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
                    {pro_plugin_active && <span>{message}&nbsp;
                        <a className="rsssl-locked-link"
                           rel="noopener noreferrer"
                           href="#settings/license">{__("Check license", "really-simple-ssl")}
                        </a>
                    </span>}
                    {!pro_plugin_active && <span>{message}</span>}
                </div>
                <div className="rsssl-locked-footer">
                    {/* We place a button on the left side */}
                    <div className="rsssl-grid-item-footer-buttons">
                        <a
                            target="_blank"
                            className="button button-primary left"
                            href={url ? url : "https://really-simple-ssl.com/pro/"}
                        >{__("Go Pro", "really-simple-ssl")}</a>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default PremiumOverlay;