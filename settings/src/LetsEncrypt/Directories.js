import {__} from '@wordpress/i18n';
import Hyperlink from "../utils/Hyperlink";
import {
    Button,
} from '@wordpress/components';
import useFields from "../Settings/FieldsData";
import useMenu from "../Menu/MenuData";
import {useEffect} from '@wordpress/element';
import useLetsEncryptData from "./letsEncryptData";

const Directories = (props) => {
    const {updateVerificationType} = useLetsEncryptData();
    const {addHelpNotice, updateField, setChangedField, saveFields, fetchFieldsData} = useFields();
    const { setSelectedSubMenuItem} = useMenu();

    let action = props.action;

    useEffect(() => {
        if ((action && action.action === 'challenge_directory_reachable' && action.status === 'error')) {
            addHelpNotice(
                props.field.id,
                'default',
                __("The challenge directory is used to verify the domain ownership.", "really-simple-ssl"),
            );
        }

        if ((action && action.action === 'check_key_directory' && action.status === 'error')) {
            addHelpNotice(
                props.field.id,
                'default',
                __("The key directory is needed to store the generated keys.", "really-simple-ssl") + ' ' + __("By placing it outside the root folder, it is not publicly accessible.", "really-simple-ssl"),
            );
        }

        if ((action && action.action === 'check_certs_directory' && action.status === 'error')) {
            addHelpNotice(
                props.field.id,
                'default',
                __("The certificate will get stored in this directory.", "really-simple-ssl") + ' ' + __("By placing it outside the root folder, it is not publicly accessible.", "really-simple-ssl"),
            );
        }
    }, [action]);


    if ( !action ) {
        return (<></>);
    }

    const handleSwitchToDNS = async () => {
        updateField('verification_type', 'dns');
        setChangedField('verification_type', 'dns');
        await saveFields(true, true);
        await updateVerificationType('dns');
        await setSelectedSubMenuItem('le-dns-verification');
        await fetchFieldsData('le-directories');
    }

    return (
        <div className="rsssl-test-results">
            {action.status === 'error' && <h4>{__("Next step", "really-simple-ssl")}</h4>}

            {(action.status === 'error' && action.action === 'challenge_directory_reachable') &&
                <div>
                    <p>
                        {__("If the challenge directory cannot be created, or is not reachable, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl")}
                    </p>
                    <Button
                        variant="secondary"
                        onClick={() => handleSwitchToDNS()}
                    >
                        {__('Switch to DNS verification', 'really-simple-ssl')}
                    </Button>
                </div>
            }
            {rsssl_settings.hosting_dashboard === 'cpanel' &&
                <><p>
                    <Hyperlink target="_blank" rel="noopener noreferrer"
                               text={__("If you also want to secure subdomains like mail.domain.com, cpanel.domain.com, you have to use the %sDNS%s challenge.", "really-simple-ssl")}
                               url="https://really-simple-ssl.com/lets-encrypt-authorization-with-dns"/>
                    &nbsp;{__("Please note that auto-renewal with a DNS challenge might not be possible.", "really-simple-ssl")}
                </p>
                    <Button
                        variant="secondary"
                        onClick={() => handleSwitchToDNS()}
                    >{__('Switch to DNS verification', 'really-simple-ssl')}</Button></>
            }
            {(action.status === 'error' && action.action === 'check_challenge_directory') &&
                <div>
                    <h4>
                        {__("Create a challenge directory", "really-simple-ssl")}
                    </h4>
                    <p>
                        {__("Navigate in FTP or File Manager to the root of your WordPress installation:", "really-simple-ssl")}
                    </p>
                    <ul>
                        <li className="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                            {__('Create a folder called “.well-known”', 'really-simple-ssl')}
                        </li>
                        <li className="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                            {__('Inside the folder called “.well-known” create a new folder called “acme-challenge”, with 644 writing permissions.', 'really-simple-ssl')}
                        </li>
                        <li className="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                            {__('Click the refresh button.', 'really-simple-ssl')}
                        </li>
                    </ul>
                    <h4>
                        {__("Or you can switch to DNS verification", "really-simple-ssl")}
                    </h4>
                    <p>{__("If the challenge directory cannot be created, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl")}</p>
                    <Button
                        variant="secondary"
                        onClick={() => handleSwitchToDNS()}
                    >
                        {__('Switch to DNS verification', 'really-simple-ssl')}
                    </Button>
                </div>
            }

            {(action.status === 'error' && action.action === 'check_key_directory') &&
                <div>
                    <h4>
                        {__("Create a key directory", "really-simple-ssl")}
                    </h4>
                    <p>
                        {__("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl")}
                    </p>
                    <ul>
                        <li className="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                            {__('Create a folder called “ssl”', 'really-simple-ssl')}
                        </li>
                        <li className="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                            {__('Inside the folder called “ssl” create a new folder called “keys”, with 644 writing permissions.', 'really-simple-ssl')}
                        </li>
                        <li className="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                            {__('Click the refresh button.', 'really-simple-ssl')}
                        </li>
                    </ul>
                </div>
            }

            {(action.status === 'error' && action.action === 'check_certs_directory') &&
                <div>
                    <h4>
                        {__("Create a certs directory", "really-simple-ssl")}
                    </h4>
                    <p>
                        {__("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl")}
                    </p>
                    <ul>
                        <li className="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                            {__('Create a folder called “ssl”', 'really-simple-ssl')}
                        </li>
                        <li className="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                            {__('Inside the folder called “ssl” create a new folder called “certs”, with 644 writing permissions.', 'really-simple-ssl')}
                        </li>
                        <li className="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                            {__('Click the refresh button.', 'really-simple-ssl')}
                        </li>
                    </ul>
                </div>
            }
        </div>
    )
}

export default Directories;