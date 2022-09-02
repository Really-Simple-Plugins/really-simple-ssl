import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import {dispatch,} from '@wordpress/data';
import Notices from "../Settings/Notices";
import sleeper from "../utils/sleeper.js";
import {
    Button,
} from '@wordpress/components';

const Directories = (props) => {
    const action = props.action;
    useEffect(() => {
       }, [])
    if (!action) {
        return (<></>);
    }

    if ( action.status !== 'error' && action.status !== 'warning') {
        return (<></>);
    }

    const handleSwitchToDNS = () => {
        props.updateField('verification_type', 'DNS');
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

    const addHelp = () => {
        let help = {};
        help.label='default';
        help.text='test';
        help.title='title';
        props.updateField(props.field.id, props.field.value, help);
    }

    if ((action.action==='challenge_directory_reachable' && action.status==='error')) {
        addHelp(__("The challenge directory is used to verify the domain ownership.", "really-simple-ssl") );
    }

    return (
        <>
            { action.status === 'error' &&
                <div>
                    <h4>{ __("Next step", "really-simple-ssl") }</h4>
                </div>
            }

            { (action.action==='check_challenge_directory' || action.action==='challenge_directory_reachable') &&
                <div>
                    <p>

                    { __("If the challenge directory cannot be created, or is not reachable, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl")}
                    </p>
                    <Button
                        variant="secondary"
                        onClick={() => handleSwitchToDNS()}
                        >
                        { __( 'Switch to DNS verification', 'really-simple-ssl' ) }
                    </Button>
                </div>
             }
             { (action.action==='check_challenge_directory' && action.status==='error') &&
                 <div>
                     <h4>
             			{__("Create a challenge directory", "really-simple-ssl") }
             			{ addHelp(__("The challenge directory is used to verify the domain ownership.", "really-simple-ssl") )}
                     </h4>
                     <p>
             			{ __("Navigate in FTP or File Manager to the root of your WordPress installation:", "really-simple-ssl")}
                     </p>
                     <ul>
                         <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
             				{ __('Create a folder called “.well-known”', 'really-simple-ssl')}
                         </li>
                         <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
             				{ __('Inside the folder called “.well-known” create a new folder called “acme-challenge”, with 644 writing permissions.', 'really-simple-ssl')}
                         </li>
                         <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
             				{ __('Click the refresh button.', 'really-simple-ssl')}
                         </li>
                     </ul>
                     <h2>
             		    { __("Or you can switch to DNS verification", "really-simple-ssl")}
                     </h2>
                     <p>{ __("If the challenge directory cannot be created, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl")}</p>
                     <button class="button button-default" name="rsssl-switch-to-dns">{ __("Switch to DNS verification", "really-simple-ssl")}</button>

                 </div>
                 }
        </>
    )
}

export default Directories;