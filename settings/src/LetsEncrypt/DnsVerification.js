import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import {dispatch,} from '@wordpress/data';
import Notices from "../Settings/Notices";
import update from 'immutability-helper';
import Hyperlink from "../utils/Hyperlink";
import {useUpdateEffect} from 'react-use';
import sleeper from "../utils/sleeper";
import {
    Button,
} from '@wordpress/components';

const DnsVerification = (props) => {
    const action = props.action;
    const [tokens, setTokens] = useState(false);
     useUpdateEffect(()=> {

        if (action && action.action==='challenge_directory_reachable' && action.status==='error') {
            props.addHelp(
                props.field.id,
                 'default',
                __("The challenge directory is used to verify the domain ownership.", "really-simple-ssl"),
            );
        }
         let newTokens = action ? action.output : false;
         if ( typeof (newTokens) === "undefined" || newTokens.length === 0 ) {
             newTokens = false;
         }
         if ( newTokens ) {
             setTokens(newTokens);
         }
     });

    const handleSwitchToDir = () => {
        props.updateField('verification_type', 'dir');
        return rsssl_api.runLetsEncryptTest('update_verification_type', 'dir').then( ( response ) => {
            props.selectMenu('le-directories');
            const notice = dispatch('core/notices').createNotice(
                'success',
                __( 'Switched to directory', 'really-simple-ssl' ),
                {
                    __unstableHTML: true,
                    id: 'rsssl_switched_to_dns',
                    type: 'snackbar',
                    isDismissible: true,
                }
            ).then(sleeper(3000)).then(( response ) => {
                dispatch('core/notices').removeNotice('rsssl_switched_to_dns');
            });
        });
    }

    return (
        <>
           { tokens && tokens.length>0 &&
                <div className="rsssl-test-results">
                    <h4>{__("Next step", "really-simple-ssl")}</h4>
                    <p>{__("Add the following token as text record to your DNS records. We recommend to use a short TTL during installation, in case you need to change it.", "really-simple-ssl")}
                        <Hyperlink target="_blank" text={__("Read more", "really-simple-ssl")}
                                   url="https://really-simple-ssl.com/how-to-add-a-txt-record-to-dns"/>
                    </p>
                    <div  className="rsssl-dns-text-records">
                        <div key={0}>
                            <div className="rsssl-dns-domain">@/{__("domain", "really-simple-ssl")}</div>
                            <div className="rsssl-dns-field">{__("Value", "really-simple-ssl")}</div>
                        </div>
                        { tokens.map((tokenData, i) =>
                            <div key={i+1}>
                                <div className="rsssl-dns-">_acme-challenge.{tokenData.domain}</div>
                                <div className="rsssl-dns-field rsssl-selectable">{tokenData.token}</div>
                            </div>
                        )}
                    </div>
                </div>
            }

            <div className="rsssl-test-results">
                <p>{__("DNS verification active. You can switch back to directory verification here.","really-simple-ssl")}</p>
                <Button
                    variant="secondary"
                    onClick={() => handleSwitchToDir()}
                >{ __( 'Switch to directory verification', 'really-simple-ssl' ) }</Button>
            </div>

        </>
    )
}

export default DnsVerification;