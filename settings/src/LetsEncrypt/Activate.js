import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import update from 'immutability-helper';
import {useUpdateEffect} from 'react-use';
import Icon from "../utils/Icon";

import {
    Button,
} from '@wordpress/components';
const Activate = (props) => {
    const [activationData, setActivationData] = useState(false);
    const [refreshSSLActive, setRefreshSSLActive] = useState(false);

    useUpdateEffect(()=> {
        if (activationData && !activationData.certificate_is_valid) {
            props.addHelp(
                props.field.id,
                 'default',
                 __("In some cases it takes a few minutes for the certificate to get detected. In that case, check back in a few minutes.", "really-simple-ssl"),
                 __("Certificate not detected", "really-simple-ssl")
              );
        }
    });

    useEffect(()=> {
        if (!refreshSSLActive) getActivationData();
    });

    const refreshSSLStatus = (e) => {
        e.preventDefault();
        setRefreshSSLActive(true);
        //we delay the SSL test, otherwise users don't feel anything has been done.
        setTimeout(function(){
            getActivationData()
        }, 1000);
    }

    const getActivationData = () => {
        rsssl_api.runLetsEncryptTest('activation_data').then( ( response ) => {
            if ( response.data.status === 'success' ) {
                //only do state update if anything has changed
                if ( response.data.output.ssl_enabled !== activationData.ssl_enabled
                    || response.data.output.certificate_is_valid !== activationData.certificate_is_valid){
                    setActivationData(response.data.output);

                }
                setRefreshSSLActive(false);
            }
        });
    }

    const activateSSL = (e) => {
        let sslUrl = window.location.href.replace("http://", "https://");
        rsssl_api.activateSSL().then((response) => {
            if ( response.data.success ) {
                window.location.reload();
            }
        });
    }

    if (!activationData) {
        return (<></>);
    }

    return (
        <div className="rsssl-lets-encrypt-tests">
            { activationData.ssl_enabled &&
                <>
                    <h4>
                        { __("Your site is secured with a valid SSL certificate!", "really-simple-ssl")}
                    </h4>
                    <p>{ __("If you just activated SSL, please check for: ", 'really-simple-ssl') }</p>
                    <p>
                        <ul>
                            <li><Icon name="circle-times" color="grey" />{ __('Http references in your .css and .js files: change any http:// into https://', 'really-simple-ssl') }</li>
                            <li><Icon name="circle-times" color="grey" />{ __('Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.', 'really-simple-ssl') }</li>
                            <li><Icon name="circle-check" color="green" />{ __("SSL was already activated on your website!", "really-simple-ssl") }</li>
                        </ul>
                    </p>
                    <p>
                        { !rsssl_settings.pro_active &&
                            <>
                                {__('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl') }
                                <a href="https://really-simple-ssl.com/pro">{ __("Check out Really Simple SSL Pro", "really-simple-ssl")} </a>
                            </>
                        }
                    </p>
                </>
             }

         { !activationData.ssl_enabled &&
            <>
            	<h4>{ __("Almost ready to activate SSL!", "really-simple-ssl")}</h4>
                <p>{ __("Before you migrate, please check for: ", 'really-simple-ssl')}</p>
                <ul>
                    <li><Icon name="circle-times" color="grey" />{ __('Http references in your .css and .js files: change any http:// into https://', 'really-simple-ssl')}</li>
                    <li><Icon name="circle-times" color="grey" />{ __('Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.', 'really-simple-ssl')}</li>
                    <li><Icon name="circle-times" color="grey" />{ __("You may need to login in again.", "really-simple-ssl") }</li>
            		{ activationData.certificate_is_valid &&
                        <li><Icon name="circle-check" color = "green" />{ __("An SSL certificate has been detected", "really-simple-ssl")}</li> }
            		{ !refreshSSLActive && !activationData.certificate_is_valid &&
                        <li><Icon name="circle-times" color = "red" />{ __("No SSL certificate has been detected yet. In some cases this takes a few minutes.", "really-simple-ssl") }</li> }
            		{ refreshSSLActive && !activationData.certificate_is_valid &&
                        <li><Icon name="file-download" color = "red" />{ __("Re-checking SSL certificate, please wait...","really-simple-ssl") }</li> }

                </ul>
                { !rsssl_settings.pro_active &&
                <p>
                    {__('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl')}
                    &nbsp;
                    <a className="button button-default" target="_blank" href="https://really-simple-ssl.com/pro">{ __("Go Pro", "really-simple-ssl")}</a>
                </p>}
            </>
            }
            <div className="rsssl-activation-buttons">
                { activationData.certificate_is_valid && activationData.ssl_enabled &&
                    <a className="button button-default" href={rsssl_settings.dashboard_url} >{ __("Go to dashboard", "really-simple-ssl") }</a> }
                { activationData.certificate_is_valid && !activationData.ssl_enabled &&
                    <button className="button button-primary" onClick={ (e) => activateSSL(e) }>{ __("Go ahead, activate SSL!", "really-simple-ssl")}</button> }
                { !activationData.certificate_is_valid && !activationData.ssl_enabled &&
                    <button className="button button-primary" onClick={ (e) => refreshSSLStatus(e) }>{ __("Refresh SSL status", "really-simple-ssl")}</button> }
            </div>
         </div>
    )
}

export default Activate;