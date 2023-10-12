import { Button, ToggleControl } from '@wordpress/components';
import * as rsssl_api from "../utils/api";
import { __ } from '@wordpress/i18n';
import useMenu from "../Menu/MenuData";
import useFields from "../Settings/FieldsData";
import useOnboardingData from "./OnboardingData";
import useProgress from "../Dashboard/Progress/ProgressData";
const OnboardingButtons = ({isModal}) => {
    const { getProgressData} = useProgress();
    const { updateField, updateFieldsData} = useFields();
    const {
        dismissModal,
        setProcessing,
        certificateValid,
        networkwide,
        processing,
        steps,
        currentStepIndex,
        setCurrentStepIndex,
        overrideSSL,
        setNetworkActivationStatus,
        email,
        saveEmail,
    } = useOnboardingData();
    const {setSelectedMainMenuItem} = useMenu();

    const activateSSL = () => {
        setProcessing(true);
        rsssl_api.runTest('activate_ssl' ).then( async ( response ) => {
            setProcessing(false);
            setCurrentStepIndex(currentStepIndex+1);
            //change url to https, after final check
            if ( response.success ) {
                if ( response.site_url_changed ) {
                    window.location.reload();
                } else {
                    if ( networkwide ) {
                        setNetworkActivationStatus('main_site_activated');
                    }
                }
            }
        }).then( async () => {
            await getProgressData();
            await fetchFieldsData(selectedMainMenuItem )
        } );
    }

    const goToDashboard = () => {
        if ( isModal ) dismissModal(true);
        setSelectedMainMenuItem('dashboard');
    }

    const goToLetsEncrypt = () => {
        if (isModal) dismissModal(true);
        window.location.href=rsssl_settings.letsencrypt_url;
    }

    const saveEmailAndUpdateFields = async () => {
        await saveEmail();

        updateField('send_notifications_email', true );
        updateField('notifications_email_address', email );
        updateFieldsData();
    }

    let ActivateSSLText = networkwide ? __("Activate SSL networkwide", "really-simple-ssl") : __("Activate SSL", "really-simple-ssl");
    if ( currentStepIndex === 0 ) {
        return (
            <>
                <Button disabled={processing || (!certificateValid && !overrideSSL) } isPrimary onClick={() => {activateSSL()}}>{ActivateSSLText}</Button>
                { certificateValid && !rsssl_settings.pro_plugin_active && <a target="_blank" href={rsssl_settings.upgrade_link} className="button button-default" >{__("Improve Security with PRO", "really-simple-ssl")}</a>}
                { !certificateValid && <Button className="rsssl-modal-default" onClick={() => {goToLetsEncrypt()}}>{__("Install SSL", "really-simple-ssl")}</Button>}
            </>
        );
    }

    if (currentStepIndex>0 && currentStepIndex<steps.length-1) {
        return (
            <>
                <Button disabled={processing} isPrimary onClick={() => saveEmailAndUpdateFields()}>{__('Save and continue', 'really-simple-ssl')}</Button>
                <Button disabled={processing} className="rsssl-modal-default" onClick={() => {setCurrentStepIndex(currentStepIndex+1)}}>{__('Skip', 'really-simple-ssl')}</Button>
            </>
        );
    }

    //for last step only
    if ( steps.length-1 === currentStepIndex ) {
        return (
            <>
                <Button className="rsssl-modal-default" onClick={() => dismissModal(true)}>{__('Dismiss', 'really-simple-ssl')}</Button>
                <Button isPrimary onClick={() => {goToDashboard()}}>{__('Go to Dashboard', 'really-simple-ssl')}</Button>
            </>
        );
    }
}

export default OnboardingButtons;