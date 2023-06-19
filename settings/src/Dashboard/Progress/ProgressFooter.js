import { __ } from '@wordpress/i18n';
import Icon from "../../utils/Icon";
import useFields from "../../Settings/FieldsData";
import useOnboardingData from "../../Onboarding/OnboardingData";

const ProgressFooter = (props) => {
    const {setShowOnBoardingModal} = useOnboardingData();
    const {fields} = useFields();

    let redirectValue = fields.filter( field => field.id==='redirect' )[0].value;
    let sslEnabled = fields.filter( field => field.id==='ssl_enabled' )[0].value;
    let wpconfigFixRequired = rsssl_settings.wpconfig_fix_required;
    let hasMixedContentFixer = fields.filter( field => field.id==='mixed_content_fixer' )[0].value;
    let hasRedirect = redirectValue=== 'wp_redirect' || redirectValue=== 'htaccess';
    let sslStatusText = sslEnabled ? __( "SSL Activated", "really-simple-ssl" ) : __( "SSL not activated", "really-simple-ssl" );
    let sslStatusIcon = sslEnabled ? 'circle-check' : 'circle-times';
    let sslStatusColor = sslEnabled ? 'green' : 'red';
    let redirectIcon = hasRedirect ? 'circle-check' : 'circle-times';
    let redirectColor = hasRedirect ? 'green' : 'red';
    let mixedContentIcon = hasMixedContentFixer ? 'circle-check' : 'circle-times';
    let mixedContentColor = hasMixedContentFixer ? 'green' : 'red';
    return (
        <>
            { !sslEnabled && <button key="activate-ssl-button" disabled={wpconfigFixRequired} onClick={() => setShowOnBoardingModal(true)} className="button button-primary">{__( "Activate SSL", "really-simple-ssl" ) }</button>}
            { rsssl_settings.pro_plugin_active && <span key="progressFooterVersion" className="rsssl-footer-left">Really Simple SSL Pro {rsssl_settings.pro_version}</span>}
            { !rsssl_settings.pro_plugin_active && <a key="progressFooterGoPro" href={rsssl_settings.upgrade_link} target="_blank" className="button button-default">{ __( "Go Pro", "really-simple-ssl" ) }</a>}

            <div key="progressFooterStatus" className="rsssl-legend">
                <Icon name = {sslStatusIcon} color = {sslStatusColor} />
                <div>{sslStatusText}</div>
            </div>
            <div key="progressFooterMixed" className="rsssl-legend">
                <Icon name = {mixedContentIcon} color = {mixedContentColor} />
                <div>{__( "Mixed content", "really-simple-ssl" )}</div>
            </div>
            <div key="progressFooterRedirect" className="rsssl-legend">
                <Icon name = {redirectIcon} color = {redirectColor} />
                <div>{__( "301 redirect", "really-simple-ssl" )}</div>
            </div>
        </>
        );
    }

export default ProgressFooter;
