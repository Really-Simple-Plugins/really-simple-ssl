import {useState, useEffect} from "@wordpress/element";
import * as rsssl_api from "../utils/api";
import { __ } from '@wordpress/i18n';
import update from 'immutability-helper';
import {useUpdateEffect} from 'react-use';
import Icon from "../utils/Icon";

const ProgressFooter = (props) => {
    const [certificateIsValid, setCertificateIsValid] = useState(false);
    const [sslDataLoaded, SetSslDataLoaded] = useState(false);

    useEffect(() => {
        rsssl_api.runTest('ssl_status_data' ).then( ( response ) => {
            setCertificateIsValid(response.data.certificate_is_valid);
            SetSslDataLoaded(true);
        });
    }, [])

    const startModal = () => {
        props.setShowOnBoardingModal(true);
    }

    if ( !sslDataLoaded) {
        return (
        <></>);
    }
    let redirectValue = props.fields.filter( field => field.id==='redirect' )[0].value;
    let sslEnabled = props.fields.filter( field => field.id==='ssl_enabled' )[0].value;
    let wpconfigFixRequired = rsssl_settings.wpconfig_fix_required;
    let hasMixedContentFixer = props.fields.filter( field => field.id==='mixed_content_fixer' )[0].value;
    let hasRedirect = redirectValue=== 'wp_redirect' || redirectValue=== 'htaccess';
    let sslStatusText = sslEnabled ? __( "SSL Activated", "really-simple-ssl" ) : __( "SSL not activated", "really-simple-ssl" );
    let sslStatusIcon = sslEnabled ? 'circle-check' : 'circle-times';
    let sslStatusColor = sslEnabled ? 'green' : 'red';
    let redirectIcon = hasRedirect ? 'circle-check' : 'circle-times';
    let redirectColor = hasRedirect ? 'green' : 'red';
    let mixedContentIcon = hasMixedContentFixer ? 'circle-check' : 'circle-times';
    let mixedContentColor = hasMixedContentFixer ? 'green' : 'red';
    let disabled = wpconfigFixRequired ? 'disabled' : '';
    return (
        <>
            { !sslEnabled && <button disabled={disabled} onClick={() => startModal()} className="button button-primary">{__( "Activate SSL", "really-simple-ssl" ) }</button>}
            { rsssl_settings.pro_plugin_active && <span className="rsssl-footer-left">Really Simple SSL Pro {rsssl_settings.pro_version}</span>}
            { !rsssl_settings.pro_plugin_active && <a href={rsssl_settings.upgrade_link} target="_blank" className="button button-default">{ __( "Go Pro", "really-simple-ssl" ) }</a>}

            <div className="rsssl-legend">
                <Icon name = {sslStatusIcon} color = {sslStatusColor} />
                <div>{sslStatusText}</div>
            </div>
            <div className="rsssl-legend">
                <Icon name = {mixedContentIcon} color = {mixedContentColor} />
                <div>{__( "Mixed content", "really-simple-ssl" )}</div>
            </div>
            <div className="rsssl-legend">
                <Icon name = {redirectIcon} color = {redirectColor} />
                <div>{__( "301 redirect", "really-simple-ssl" )}</div>
            </div>
        </>
        );
    }

export default ProgressFooter;
