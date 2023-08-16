import {Button, TextareaControl,} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import {useState} from "@wordpress/element";

const Support = () => {
    const [message, setMessage] = useState('');
    const [sending, setSending] = useState(false);

    const onChangeHandler = (message) => {
        setMessage(message);
    }

    const onClickHandler = () => {
        setSending(true);
        return rsssl_api.runTest('supportData', 'refresh').then( ( response ) => {
            let encodedMessage = message.replace(/(?:\r\n|\r|\n)/g, '--br--');
            let url = 'https://really-simple-ssl.com/support'
            +'?customername=' + encodeURIComponent(response.customer_name)
            + '&email=' + response.email
            + '&domain=' + response.domain
            + '&scanresults=' + encodeURIComponent(response.scan_results)
            + '&licensekey=' + encodeURIComponent(response.license_key)
            + '&supportrequest=' + encodeURIComponent(encodedMessage)
            + '&htaccesscontents=' + encodeURIComponent(response.htaccess_contents)
            + '&debuglog=' + encodeURIComponent(response.system_status);
            window.location.assign(url);
        });
    }

    let disabled = sending || message.length===0;
    return (
        <>
            <TextareaControl
                    disabled={sending}
                    placeholder={__("Type your question here","really-simple-ssl")}
                    onChange={ ( message ) => onChangeHandler(message) }
            />
            <Button
                disabled={disabled}
                variant="secondary"
                onClick={ ( e ) => onClickHandler(e) }>
                { __( 'Send', 'really-simple-ssl' ) }
            </Button>
        </>
    );

}

export default Support;