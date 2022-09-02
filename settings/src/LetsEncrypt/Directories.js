import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';

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
        return rsssl_api.runTest('switch_to_dns', 'refresh').then( ( response ) => {
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
    console.log(action);
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
                        onClick={ this.handleSwitchToDNS }>
                        { __( 'Switch to DNS verification', 'really-simple-ssl' ) }
                    </Button>
                </div>
             }
        </>
    )
}

export default Directories;