import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import {dispatch} from '@wordpress/data';
import Hyperlink from "../utils/Hyperlink";
import {useUpdateEffect} from 'react-use';
import {
    Button,
} from '@wordpress/components';
import useFields from "../Settings/FieldsData";
import useMenu from "../Menu/MenuData";
import * as rsssl_api from "../utils/api";
import sleeper from "../utils/sleeper";

const DnsVerification = (props) => {
    const {fields, addHelpNotice, updateField, setChangedField, saveFields, fetchFieldsData, getFieldValue} = useFields();
    const {selectedSubMenuItem, setSelectedSubMenuItem} = useMenu();
    const [tokens, setTokens] = useState(false);
    let action = props.action;

     useUpdateEffect(()=> {
        if (action && action.action==='challenge_directory_reachable' && action.status==='error') {
            addHelpNotice(
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

    const handleSwitchToDir = async () => {
        await setSelectedSubMenuItem('le-directories');
        await updateField('verification_type', 'dir');
        await setChangedField('verification_type', 'dir');
        await saveFields(true, true);
        await rsssl_api.runLetsEncryptTest('update_verification_type', 'dir').then((response) => {
            const notice = dispatch('core/notices').createNotice(
                'success',
                __('Switched to Directory', 'really-simple-ssl'),
                {
                    __unstableHTML: true,
                    id: 'rsssl_switched_to_dns',
                    type: 'snackbar',
                    isDismissible: true,
                }
            ).then(sleeper(3000)).then((response) => {
                dispatch('core/notices').removeNotice('rsssl_switched_to_dns');
            });
        });
        await fetchFieldsData('le-directories');
    }

    const handleSwitchToDNS = async () => {

        await rsssl_api.runLetsEncryptTest('update_verification_type', 'dns').then((response) => {

            const notice = dispatch('core/notices').createNotice(
                'success',
                __('Switched to DNS', 'really-simple-ssl'),
                {
                    __unstableHTML: true,
                    id: 'rsssl_switched_to_dns',
                    type: 'snackbar',
                    isDismissible: true,
                }
            ).then(sleeper(3000)).then((response) => {
                dispatch('core/notices').removeNotice('rsssl_switched_to_dns');
            });
        });
        await setSelectedSubMenuItem('le-dns-verification');
    }


    let verificationType = getFieldValue('verification_type');
    if (verificationType==='dir') {
        return (<></>);
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
                        <div>
                            <div className="rsssl-dns-domain">@/{__("domain", "really-simple-ssl")}</div>
                            <div className="rsssl-dns-field">{__("Value", "really-simple-ssl")}</div>
                        </div>
                        { tokens.map((tokenData, i) =>
                            <div>
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