import * as rsssl_api from "../../utils/api";
import useFields from "../FieldsData";
import {__} from "@wordpress/i18n";
import {useEffect, useState} from "react";
import useRiskData from "./RiskData";

const NotificationTester = (props) => {
    const {
        fetchVulnerabilities,riskLevels
    } = useRiskData();
    const {field} = props;
    const [disabled, setDisabled] = useState(true);
    const [mailNotificationsEnabled, setMailNotificationsEnabled] = useState(true);
    const [vulnerabilitiesEnabled, setVulnerabilitiesEnabled] = useState(false);
    const [vulnerabilitiesSaved, setVulnerabilitiesSaved] = useState(false);
    const {addHelpNotice, fields, getFieldValue, updateField, setChangedField, fieldAlreadyEnabled, fetchFieldsData, updateFieldAttribute} = useFields();
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

    //ensure that risk levels are enabled cascading
    useEffect( () => {
        let dashboardRiskLevel = getFieldValue('vulnerability_notification_dashboard');
        dashboardRiskLevel = riskLevels.hasOwnProperty(dashboardRiskLevel) ? riskLevels[dashboardRiskLevel] : 0;
        // let siteWideRiskLevel = getFieldValue('vulnerability_notification_sitewide');
        //the sitewide risk level should be at least as high as the dashboard risk level. Disable lower risk levels in sitewide
        //create an array of ints from 1 to dashboardRiskLevel, we drop the * from the array
        let priorDashboardRiskLevel = dashboardRiskLevel>0 ? dashboardRiskLevel-1 :dashboardRiskLevel;
        let dashboardRiskLevels = Array.from(Array(priorDashboardRiskLevel).keys()).map(x => x );
        //convert these integers back to risk levels
        //find the integer value in the riskLevels object, and return the key
        dashboardRiskLevels = dashboardRiskLevels.map( (level) => {
            return Object.keys(riskLevels).find(key => riskLevels[key] === level  );
        });

        if (dashboardRiskLevels.length > 0) {
            updateFieldAttribute('vulnerability_notification_sitewide', 'disabled', dashboardRiskLevels);
            //if the current value is below the dashboardRisk Level, set it to the dashboardRiskLevel
            let siteWideRiskLevel = getFieldValue('vulnerability_notification_sitewide');
            siteWideRiskLevel = riskLevels.hasOwnProperty(siteWideRiskLevel) ? riskLevels[siteWideRiskLevel] : 0;
            if (siteWideRiskLevel<dashboardRiskLevel) {
                let newRiskLevel = Object.keys(riskLevels).find(key => riskLevels[key] === dashboardRiskLevel  );
                updateField('vulnerability_notification_sitewide', newRiskLevel);
                setChangedField('vulnerability_notification_sitewide', newRiskLevel);
            }
        } else {
            updateFieldAttribute('vulnerability_notification_sitewide', 'disabled', false);
        }
    },[getFieldValue('vulnerability_notification_dashboard')])

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