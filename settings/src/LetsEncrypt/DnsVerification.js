import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import {dispatch,} from '@wordpress/data';
import Notices from "../Settings/Notices";
import update from 'immutability-helper';
import Hyperlink from "../utils/Hyperlink";
import {useUpdateEffect} from 'react-use';
import sleeper from "../utils/sleeper";

const DnsVerification = (props) => {
    const action = props.action;
     useUpdateEffect(()=> {

        if (action && action.action==='challenge_directory_reachable' && action.status==='error') {
            props.addHelp(
                props.field.id,
                 'default',
                __("The challenge directory is used to verify the domain ownership.", "really-simple-ssl"),
            );
        }
     });

    if (!action) {
        return (<></>);
    }

    if ( action.status !== 'success' ) {
        return (<></>);
    }

    const handleSwitchToDNS = () => {
        props.updateField('verification_type', 'dns');
        return rsssl_api.runLetsEncryptTest('switch_to_dns', 'dns').then( ( response ) => {
            props.selectMenu('le-dns-verification');
            const notice = dispatch('core/notices').createNotice(
                'success',
                __( 'Switched to DNS', 'really-simple-ssl' ),
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

    var tokens = action.output;
    if (!tokens) return (<></>)
    console.log(tokens);
    return (
        <div className="rsssl-test-results">
            <h2>{__("Next step", "really-simple-ssl") }</h2>
            <p>{__("Add the following token as text record to your DNS records. We recommend to use a short TTL during installation, in case you need to change it.", "really-simple-ssl")}
                <Hyperlink target="_blank" text={__("Read more","really-simple-ssl")} url="https://really-simple-ssl.com/how-to-add-a-txt-record-to-dns"/>
             </p>
            <div id="rsssl-dns-text-records">
                {tokens.map((token, i) =>
                    <>
                        <div className="rsssl-dns-label" >@/{ __("domain","really-simple-ssl") }</div>
                        <div className="rsssl-dns-field rsssl-selectable">_acme-challenge{i}</div>
                        <div className="rsssl-dns-label" >{__("Value","really-simple-ssl") }</div>
                        <div className="rsssl-dns-field rsssl-selectable">{token}</div>
                    </>
                )}

            </div>
        </div>
    )
}

export default DnsVerification;