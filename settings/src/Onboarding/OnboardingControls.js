import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useMenu from "../Menu/MenuData";
import useFields from "../Settings/FieldsData";
import useOnboardingData from "./OnboardingData";
import useProgress from "../Dashboard/Progress/ProgressData";
const OnboardingControls = ({isModal}) => {
    const { getProgressData} = useProgress();
    const { updateField, updateFieldsData, fetchFieldsData} = useFields();
    const { setSelectedMainMenuItem, selectedSubMenuItem} = useMenu();

    const {
        dismissModal,
        activateSSL,
        certificateValid,
        networkwide,
        processing,
        steps,
        currentStepIndex,
        setCurrentStepIndex,
        overrideSSL,
        email,
        saveEmail,
    } = useOnboardingData();

    const goToDashboard = () => {
        if ( isModal ) {
            dismissModal(true);
        }
        setSelectedMainMenuItem('dashboard');
    }

    const handleActivateSSL = async () => {
        await activateSSL();
        await getProgressData();
        await fetchFieldsData( );
    }

    const goToLetsEncrypt = () => {
        if (isModal) dismissModal(true);
        window.location.href=rsssl_settings.letsencrypt_url;
    }

    const saveEmailAndUpdateFields = async () => {
        await saveEmail();
        updateField('send_notifications_email', true );
        updateField('notifications_email_address', email );
        updateFieldsData(selectedSubMenuItem);
    }

    let ActivateSSLText = networkwide ? __("Activate SSL networkwide", "really-simple-ssl") : __("Activate SSL", "really-simple-ssl");
    if ( currentStepIndex === 0 ) {
        return (
            <>
                <Button disabled={processing || (!certificateValid && !overrideSSL) } isPrimary onClick={() => {handleActivateSSL()}}>{ActivateSSLText}</Button>
                { isModal && !certificateValid && <Button onClick={() => {goToLetsEncrypt()}}>{__("Install SSL", "really-simple-ssl")}</Button>}
                { certificateValid && !rsssl_settings.pro_plugin_active && <Button onClick={(e) => {window.location.href=rsssl_settings.upgrade_link}}>{__("Improve Security with PRO", "really-simple-ssl")}</Button>}
            </>
        );
    }

    if (currentStepIndex>0 && currentStepIndex<steps.length-1) {
        return (
            <>
                <Button disabled={processing} onClick={() => {setCurrentStepIndex(currentStepIndex+1)}}>{__('Skip', 'really-simple-ssl')}</Button>
                <Button disabled={processing} isPrimary onClick={() => saveEmailAndUpdateFields()}>{__('Save and continue', 'really-simple-ssl')}</Button>
            </>
        );
    }

    //for last step only
    if ( steps.length-1 === currentStepIndex ) {
        return (
            <>
                <Button onClick={() => dismissModal(true)}>{__('Dismiss', 'really-simple-ssl')}</Button>
                <Button isPrimary onClick={() => {goToDashboard()}}>{__('Go to Dashboard', 'really-simple-ssl')}</Button>
            </>
        );
    }
}

export default OnboardingControls;