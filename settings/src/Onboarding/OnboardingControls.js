import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useMenu from "../Menu/MenuData";
import useFields from "../Settings/FieldsData";
import useOnboardingData from "./OnboardingData";
import useProgress from "../Dashboard/Progress/ProgressData";
import {useEffect} from "@wordpress/element";
import useRiskData from "../Settings/RiskConfiguration/RiskData";
const OnboardingControls = ({isModal}) => {
    const { getProgressData} = useProgress();
    const { updateField, updateFieldsData, fetchFieldsData} = useFields();
    const { setSelectedMainMenuItem, selectedSubMenuItem} = useMenu();
    const {
        fetchFirstRun, fetchVulnerabilities
    } = useRiskData();
    const {
        dismissModal,
        activateSSL,
        certificateValid,
        networkwide,
        processing,
        steps,
        currentStepIndex,
        currentStep,
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

    const saveAndContinue = async () => {
        if (currentStep.id === 'features') {
            //loop through all items of currentStep.items
            for (const item of currentStep.items){
                console.log(item);
                if (item.activated) {
                    for (const fieldId of Object.values(item.options)) {
                        await updateField(fieldId, true);
                    }
                    if (item.id === 'hardening') {
                        await fetchFieldsData('hardening');
                        await getProgressData();
                    }

                    if  (item.id === '"vulnerability_detection"' ) {
                        await fetchFieldsData('vulnerabilities');
                        await fetchFirstRun();
                        await fetchVulnerabilities();
                        await getProgressData();
                    }
                }
            }
            setCurrentStepIndex(currentStepIndex+1)
        }

        if ( currentStep.id === 'email' ) {
            await saveEmail();
            updateField('send_notifications_email', true );
            updateField('notifications_email_address', email );
            updateFieldsData(selectedSubMenuItem);
        }
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

    let ActivateSSLText = networkwide ? __("Activate SSL networkwide", "really-simple-ssl") : __("Activate SSL", "really-simple-ssl");
    if ( currentStepIndex === 0 ) {
        return (
            <>
                { isModal && !certificateValid && <Button onClick={() => {goToLetsEncrypt()}}>{__("Install SSL", "really-simple-ssl")}</Button>}
                <Button disabled={processing || (!certificateValid && !overrideSSL) } isPrimary onClick={() => {handleActivateSSL()}}>{ActivateSSLText}</Button>
            </>
        );
    }

    if (currentStepIndex>0 && currentStepIndex<steps.length-1) {
        if (currentStep.id === 'plugins') {}
        return (
            <>
                <Button disabled={processing} onClick={() => {setCurrentStepIndex(currentStepIndex+1)}}>{__('Skip', 'really-simple-ssl')}</Button>
                <Button disabled={processing} isPrimary onClick={() => saveAndContinue() }>{__('Save and continue', 'really-simple-ssl')}</Button>
            </>
        );
    }

    //for last step only
    if ( steps.length-1 === currentStepIndex ) {
        return (
            <>
                <Button onClick={() => dismissModal(true)}>{__('Dismiss', 'really-simple-ssl')}</Button>
                <Button isPrimary onClick={() => {goToDashboard()}}>{__('Go to Dashboard', 'really-simple-ssl')}</Button>
                { certificateValid && !rsssl_settings.pro_plugin_active && <Button onClick={(e) => {window.location.href=rsssl_settings.upgrade_link}}>{__("Improve Security with PRO", "really-simple-ssl")}</Button>}
            </>
        );
    }
}

export default OnboardingControls;