import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import {dispatch,} from '@wordpress/data';
import Notices from "../Settings/Notices";
import update from 'immutability-helper';
import {useUpdateEffect} from 'react-use';
import sleeper from "../utils/sleeper";
import Hyperlink from "../utils/Hyperlink";

import {
    Button,
} from '@wordpress/components';
const Activate = (props) => {
    const [activationData, setActivationData] = useState(false);

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
        rsssl_api.runLetsEncryptTest('activation_data').then( ( response ) => {
            if (response) {
                setActivationData(response.data.output);
            }
        });
    });

    if (!activationData) {
        return (<></>);
    }
    return (
        <div className="rsssl-lets-encrypt-tests">
            { activationData.done &&
                <>
                    <h4>
                        { __("Your site is secured with a valid SSL certificate!", "really-simple-ssl")}
                    </h4>
                    <p>{ __("If you just activated SSL, please check for: ", 'really-simple-ssl') }</p>
                    <p>
                        <ul>
                            <li className="rsssl-warning">{ __('Http references in your .css and .js files: change any http:// into https://', 'really-simple-ssl') }</li>
                            <li className="rsssl-warning">{ __('Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.', 'really-simple-ssl') }</li>
                            <li className="rsssl-success">{ __("SSL was already activated on your website!", "really-simple-ssl") }</li>
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

         { !activationData.done &&
            <>
            	<h4>{ __("Almost ready to activate SSL!", "really-simple-ssl")}</h4>
                <p>{ __("Before you migrate, please check for: ", 'really-simple-ssl')}</p>
                <ul>
                    <li className="rsssl-warning">{ __('Http references in your .css and .js files: change any http:// into https://', 'really-simple-ssl')}</li>
                    <li className="rsssl-warning">{ __('Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.', 'really-simple-ssl')}</li>
                    <li className="rsssl-warning">
                        <Hyperlink target="_blank" text={__("We strongly recommend to create a %sbackup%s of your site before activating SSL", 'really-simple-ssl')} url="https://really-simple-ssl.com/knowledge-base/backing-up-your-site/" />
                    </li>
                    <li className="rsssl-warning">{ __("You may need to login in again.", "really-simple-ssl") }</li>
            		{ activationData.certificate_is_valid &&
                        <li className="rsssl-success">{ __("An SSL certificate has been detected", "really-simple-ssl")}</li> }
            		{ !activationData.certificate_is_valid &&
                        <li className="rsssl-error">{ __("No SSL certificate has been detected yet. In some cases this takes a few minutes.", "really-simple-ssl") }</li> }

                </ul>
                { !rsssl_settings.pro_active &&
                <p>
                    {__('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl')}
                    &nbsp;
                    <a className="button button-default" target="_blank" href="https://really-simple-ssl.com/pro">{ __("Go Pro", "really-simple-ssl")}</a>
                </p>}

            </>
            }
         </div>
    )
}

export default Activate;