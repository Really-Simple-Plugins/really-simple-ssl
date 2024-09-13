import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useMenu from "../Menu/MenuData";
import useFields from "../Settings/FieldsData";
import useOnboardingData from "./OnboardingData";
import useProgress from "../Dashboard/Progress/ProgressData";
import useRiskData from "../Settings/RiskConfiguration/RiskData";
import useSslLabs from "../Dashboard/SslLabs/SslLabsData";
import useLicense from "../Settings/License/LicenseData";

const OnboardingControls = ({isModal}) => {
    const { getProgressData} = useProgress();
    const { updateField, setChangedField, updateFieldsData, fetchFieldsData, saveFields, getFieldValue} = useFields();
    const { setSelectedMainMenuItem, selectedSubMenuItem} = useMenu();
    const { licenseStatus, toggleActivation } = useLicense();
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

                if ( ! item.premium || ! item.activated ) {
                    for (const fieldId of Object.values(item.options)) {
                        const value = item.value || item.activated;
                        updateField(fieldId, value);
                        setChangedField(fieldId, value);
                    }
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

        if (currentStep.id === 'pro') {
            if (rsssl_settings.pro_plugin_active) {
                setProcessing(true);
                //loop through all items of currentStep.items
                for (const item of currentStep.items) {
                    if (item.activated) {
                        if (item.id === 'advanced_headers') {
                            for (const option of item.options) {
                                if (typeof option === 'string') {
                                    // Single option
                                    updateField(option, true);
                                    setChangedField(option, true);
                                } else if (Array.isArray(option)) {
                                    // [option -> value] pair
                                    const [fieldId, value] = option;
                                    updateField(fieldId, value);
                                    setChangedField(fieldId, value);
                                }
                            }
                        } else {
                            for (const fieldId of Object.values(item.options)) {
                                const value = item.value || item.activated;
                                updateField(fieldId, value);
                                setChangedField(fieldId, value);
                            }
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

        if ( currentStep.id === 'activate_license' ) {
            if ( licenseStatus !== 'valid' ) {
                await toggleActivation(getFieldValue('license'));
                //if the license is valid, allow the user to go to the next step
                if ( licenseStatus === 'valid' ) {
                    setCurrentStepIndex( currentStepIndex + 1 );
                }
            }

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
    if (currentStep.id === 'activate_ssl') {
        return (
            <>
                {isModal && !certificateValid && (
                    <Button onClick={() => { goToLetsEncrypt() }}>
                        {__("Install SSL", "really-simple-ssl")}
                    </Button>
                )}
                <Button
                    disabled={processing || (!certificateValid && !overrideSSL)}
                    isPrimary
                    onClick={() => { handleActivateSSL() }}
                >
                    {ActivateSSLText}
                </Button>
            </>
        );
    }

    if (currentStep.id === 'activate_license') {
        return (
            <>
                <Button isPrimary onClick={() => saveAndContinue()}>
                    {currentStep.button || __('Activate', 'really-simple-ssl')}
                </Button>
            </>
        );
    }

    if (currentStepIndex>0 && currentStepIndex<steps.length-1 ) {
        return (
            <>
                {currentStep.id !== 'activate_license' && <Button  onClick={() => {setCurrentStepIndex(currentStepIndex+1)}}>{__('Skip', 'really-simple-ssl')}</Button> }
                <Button isPrimary onClick={() => saveAndContinue() }>
                    {currentStep.button}
                </Button>
            </>
        );
    }

    //for last step only
    if ( steps.length-1 === currentStepIndex ) {
        let upgradeText = rsssl_settings.is_bf ? __("Get 40% off", "really-simple-ssl") : __("Get Pro", "really-simple-ssl");

        return (
            <>
                <Button
                    isPrimary
                    onClick={() => saveAndContinue()}
                    disabled={ rsssl_settings.pro_plugin_active && licenseStatus !== 'valid' }
                >
                    {__('Finish', 'really-simple-ssl')}
                </Button>
                { !rsssl_settings.pro_plugin_active &&
                    <Button
                        rel="noreferrer noopener"
                        target="_blank"
                        isPrimary
                        href={rsssl_settings.upgrade_link}
                    >
                        {upgradeText}
                    </Button>
                }
            </>
        );
    }
}

export default OnboardingControls;