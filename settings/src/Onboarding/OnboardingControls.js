import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useMenu from "../Menu/MenuData";
import useFields from "../Settings/FieldsData";
import useOnboardingData from "./OnboardingData";
import useProgress from "../Dashboard/Progress/ProgressData";
import useRiskData from "../Settings/RiskConfiguration/RiskData";
import useSslLabs from "../Dashboard/SslLabs/SslLabsData";

const OnboardingControls = ({isModal}) => {
    const { getProgressData} = useProgress();
    const { updateField, setChangedField, updateFieldsData, fetchFieldsData, saveFields} = useFields();
    const { setSelectedMainMenuItem, selectedSubMenuItem} = useMenu();
    const {
        fetchFirstRun, fetchVulnerabilities
    } = useRiskData();
    const {
        setSslScanStatus,
    } = useSslLabs();
    const {
        dismissModal,
        activateSSL,
        certificateValid,
        setFooterStatus,
        networkwide,
        processing,
        setProcessing,
        steps,
        currentStepIndex,
        currentStep,
        setCurrentStepIndex,
        overrideSSL,
        email,
        saveEmail,
        pluginInstaller,
    } = useOnboardingData();

    const goToDashboard = () => {
        if ( isModal ) {
           dismissModal(true);
        }
        setSelectedMainMenuItem('dashboard');
    }

    const saveAndContinue = async () => {
        let vulnerabilityDetectionEnabled = false;
        if (currentStep.id === 'features') {
            setCurrentStepIndex(currentStepIndex+1);
            setProcessing(true);
            //loop through all items of currentStep.items
            for (const item of currentStep.items){
                if ( item.id=== 'health_scan' && item.activated ) {
                    setFooterStatus(__("Starting SSL health scan...", "really-simple-ssl") );
                    setSslScanStatus('active');
                }

                for (const fieldId of Object.values(item.options)) {
                    updateField(fieldId, item.activated);
                    setChangedField(fieldId, item.activated);
                }

                if  ( item.id === 'vulnerability_detection' ) {
                    vulnerabilityDetectionEnabled = item.activated;
                }
            }
            setFooterStatus(__("Activating options...", "really-simple-ssl") );
            await saveFields(true, false);
            if (vulnerabilityDetectionEnabled) {
                setFooterStatus(__("Initializing vulnerability detection...", "really-simple-ssl") );
                await fetchFirstRun();
                setFooterStatus(__("Scanning for vulnerabilities...", "really-simple-ssl") );
                await fetchVulnerabilities();
            }

            setFooterStatus(__("Updating dashboard...", "really-simple-ssl") );
            await getProgressData();
            setFooterStatus( '' );
            setProcessing(false);
        }
        if ( currentStep.id === 'email' ) {
            await saveEmail();
            setCurrentStepIndex(currentStepIndex+1);
            updateField('send_notifications_email', true );
            updateField('notifications_email_address', email );
            updateFieldsData(selectedSubMenuItem);
        }

        if ( currentStep.id === 'plugins' ) {
            setCurrentStepIndex(currentStepIndex+1)
            for (const item of currentStep.items) {
                if (item.action !== 'none' && item.action !== null ) {
                    // Add the promise returned by pluginInstaller to the array
                    await pluginInstaller(item.id, item.action, item.title );
                }
            }
            setFooterStatus('')
        }

        if ( currentStep.id === 'pro' ) {
            if ( rsssl_settings.is_premium ) {
                setProcessing(true);
                //loop through all items of currentStep.items
                for (const item of currentStep.items) {
                    if (item.activated) {
                        for (const fieldId of Object.values(item.options)) {
                            updateField(fieldId, true);
                            setChangedField(fieldId, true);
                        }
                    }
                }
                setFooterStatus(__("Activating options...", "really-simple-ssl"));
                await saveFields(true, false);

                setFooterStatus(__("Updating dashboard...", "really-simple-ssl"));
                await getProgressData();
                setFooterStatus('');
                setProcessing(false);
            }
            goToDashboard();
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
        return (
            <>
                <Button  onClick={() => {setCurrentStepIndex(currentStepIndex+1)}}>{__('Skip', 'really-simple-ssl')}</Button>
                <Button isPrimary onClick={() => saveAndContinue() }>
                    {currentStep.button}
                </Button>
            </>
        );
    }

    //for last step only
    if ( steps.length-1 === currentStepIndex ) {
        let upgradeText = rsssl_settings.is_bf ? __("Get 40% off", "really-simple-ssl") : __("Get PRO", "really-simple-ssl");
        return (
            <>
                <Button isPrimary onClick={() => saveAndContinue() }>{__('Finish', 'really-simple-ssl')}</Button>
                { !rsssl_settings.pro_plugin_active && <Button rel="noreferrer noopener" target="_blank" isPrimary href={rsssl_settings.upgrade_link} >{upgradeText}</Button>}
            </>
        );
    }
}

export default OnboardingControls;