import * as rsssl_api from "../../utils/api";
import useFields from "../FieldsData";
import {__} from "@wordpress/i18n";
import {useEffect, useState} from "react";

const NotificationTester = (props) => {

    const {field} = props;
    const [disabled, setDisabled] = useState(true);
    const {addHelpNotice, fields, fieldAlreadyEnabled} = useFields();
    useEffect ( () => {
        if (fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            setDisabled(false);
        }
    },[fields])
    const doTestNotification = async () => {
        //Test the notifications
        setDisabled(true);
        rsssl_api.doAction( 'vulnerabilities_test_notification' ).then( (response) => {
            setDisabled(false);
            addHelpNotice(
                field.id,
                'success',
                __('All notifications are triggered successfully, please check your email to double-check if you can receive emails.','really-simple-ssl'),
                __('Test notifications','really-simple-ssl'),
                false
            );
        });

    }

    return (
        <>
            <button onClick={ () => doTestNotification()} disabled={ disabled } className="button button-default">{field.button_text}</button>
        </>
    )
}

export default NotificationTester