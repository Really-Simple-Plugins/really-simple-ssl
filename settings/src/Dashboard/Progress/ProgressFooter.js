import { __ } from '@wordpress/i18n';
import Icon from "../../utils/Icon";
import useFields from "../../Settings/FieldsData";
import useOnboardingData from "../../Onboarding/OnboardingData";

const ProgressFooter = (props) => {
    const {setShowOnBoardingModal} = useOnboardingData();
    const {fields} = useFields();

    let vulnerabilityScanValue = fields.filter( field => field.id==='enable_vulnerability_scanner' )[0].value;
    let sslEnabled = fields.filter( field => field.id==='ssl_enabled' )[0].value;
    let wpconfigFixRequired = rsssl_settings.wpconfig_fix_required;
    let firewallEnabled = fields.filter( field => field.id==='enable_firewall' )[0].value;
    let sslStatusText = sslEnabled ? __( "SSL", "really-simple-ssl" ) : __( "SSL", "really-simple-ssl" );
    let sslStatusIcon = sslEnabled ? 'circle-check' : 'circle-times';
    let sslStatusColor = sslEnabled ? 'green' : 'red';
    let vulnerabilityIcon = vulnerabilityScanValue ? 'circle-check' : 'circle-times';
    let vulnerabilityColor = vulnerabilityScanValue ? 'green' : 'red';
    let firewallIcon = firewallEnabled ? 'circle-check' : 'circle-times';
    let firewallColor = firewallEnabled ? 'green' : 'red';
    return (
        <>
            {!sslEnabled && <button disabled={wpconfigFixRequired} onClick={() => setShowOnBoardingModal(true)}
                                    className="button button-primary">{__("Activate SSL", "really-simple-ssl")}</button>}
            {rsssl_settings.pro_plugin_active &&
                <span className="rsssl-footer-left">Really Simple Security Pro {rsssl_settings.pro_version}</span>}
            {!rsssl_settings.pro_plugin_active &&
                <a href={rsssl_settings.upgrade_link} target="_blank" rel="noopener noreferrer"
                   className="button button-default">{__("Go Pro", "really-simple-ssl")}</a>}

            <div className="rsssl-legend">
                <Icon name={sslStatusIcon} color={sslStatusColor}/>
                <div className={"rsssl-progress-footer-link"}>
                    <a href="#settings/encryption">
                        {sslStatusText}
                    </a>
                </div>
            </div>
            <div className="rsssl-legend">
                <Icon name={firewallIcon} color={firewallColor}/>
                <div className={"rsssl-progress-footer-link"}>
                    {firewallEnabled ? (
                        <a href="#settings/rules">
                            {__("Firewall", "really-simple-ssl")}
                        </a>
                    ) : (
                        <a href="#settings/firewall&highlightfield=enable_firewall">
                            {__("Firewall", "really-simple-ssl")}
                        </a>
                    )}
                </div>
            </div>
            <div className="rsssl-legend">
                <Icon name={vulnerabilityIcon} color={vulnerabilityColor}/>
                <div className={"rsssl-progress-footer-link"}>
                    {vulnerabilityScanValue ? (
                        <a href="#settings/vulnerabilities">
                            {__("Vulnerability scan", "really-simple-ssl")}
                        </a>
                    ) : (
                        <a href="#settings/vulnerabilities&highlightfield=enable_vulnerability_scanner">
                            {__("Vulnerability scan", "really-simple-ssl")}
                        </a>
                    )}
                </div>
            </div>
        </>
    );
}

export default ProgressFooter;
