import * as rsssl_api from "../../utils/api";
import useFields from "../FieldsData";
import {__} from "@wordpress/i18n";
import {useEffect, useState} from "react";
import useRiskData from "./RiskData";

const NotificationTester = (props) => {
    const {
        fetchVulnerabilities
    } = useRiskData();
    const {field} = props;
    const [disabled, setDisabled] = useState(true);
    const [mailNotificationsEnabled, setMailNotificationsEnabled] = useState(true);
    const [vulnerabilitiesEnabled, setVulnerabilitiesEnabled] = useState(false);
    const [vulnerabilitiesSaved, setVulnerabilitiesSaved] = useState(false);
    const {addHelpNotice, fields, getFieldValue, fieldAlreadyEnabled, fetchFieldsData} = useFields();
    useEffect ( () => {
        let mailEnabled = getFieldValue('send_notifications_email') == 1;
        let vulnerabilities = fieldAlreadyEnabled('enable_vulnerability_scanner');
        setMailNotificationsEnabled(mailEnabled);
        let enableButton = mailEnabled && vulnerabilities;
        setDisabled(!enableButton);
        setMailNotificationsEnabled(mailEnabled);
        setVulnerabilitiesSaved(vulnerabilities);
        setVulnerabilitiesEnabled(getFieldValue('enable_vulnerability_scanner') == 1)
    },[fields])

    const doTestNotification = async () => {
        //Test the notifications
        setDisabled(true);
        rsssl_api.doAction( 'vulnerabilities_test_notification' ).then( () => {
            setDisabled(false);
            fetchFieldsData('vulnerabilities');
            fetchVulnerabilities();
            addHelpNotice(
                field.id,
                'success',
                __('All notifications are triggered successfully, please check your email to double-check if you can receive emails.','really-simple-ssl'),
                __('Test notifications','really-simple-ssl'),
                false
            );
        });

    }
    let fieldCopy = {...field};
    if (!mailNotificationsEnabled) {
        fieldCopy.tooltip = __('You have not enabled the email notifications in the general settings.','really-simple-ssl');
        fieldCopy.warning = true;
    } else if (vulnerabilitiesEnabled && !vulnerabilitiesSaved) {
        fieldCopy.tooltip = __('The notification test only works if you save the setting first.','really-simple-ssl');
        fieldCopy.warning = true;
    }
    return (
        <>
            <label>{props.labelWrap(fieldCopy)}</label>
            <button onClick={ () => doTestNotification()} disabled={ disabled } className="button button-default">{field.button_text}</button>
        </>
    )
}

export default NotificationTester