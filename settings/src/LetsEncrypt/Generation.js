import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import {dispatch,} from '@wordpress/data';
import sleeper from "../utils/sleeper";
import Hyperlink from "../utils/Hyperlink";

import {
    Button,
} from '@wordpress/components';
import useFields from "../Settings/FieldsData";

const Generation = (props) => {
    let action = props.action;

    if (!action) {
        return (<></>);
    }

    const handleSkipDNS = () => {
        return rsssl_api.runLetsEncryptTest('skip_dns_check').then( ( response ) => {
            props.restartTests();
            const notice = dispatch('core/notices').createNotice(
                'success',
                __( 'Skip DNS verification', 'really-simple-ssl' ),
                {
                    __unstableHTML: true,
                    id: 'rsssl_skip_dns',
                    type: 'snackbar',
                    isDismissible: true,
                }
            ).then(sleeper(3000)).then(( response ) => {
                dispatch('core/notices').removeNotice('rsssl_skip_dns');
            });
        });
    }

    return (
        <div className="rsssl-test-results">
            { (action.status === 'error' && action.action==='verify_dns' ) &&
                <>
                    <p>{ __("We could not check the DNS records. If you just added the record, please check in a few minutes.","really-simple-ssl")}&nbsp;
                                    <Hyperlink target="_blank" rel="noopener noreferrer" text={__("You can manually check the DNS records in an %sonline tool%s.","really-simple-ssl")}
                                    url="https://mxtoolbox.com/SuperTool.aspx"/>
                        { __("If you're sure it's set correctly, you can click the button to skip the DNS check.","really-simple-ssl")}&nbsp;
                    </p>
                    <Button
                        variant="secondary"
                        onClick={() => handleSkipDNS()}
                        >
                        { __( 'Skip DNS check', 'really-simple-ssl' ) }
                    </Button>
                </>
            }
        </div>
    );
}

export default Generation;