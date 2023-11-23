import { useEffect, useState } from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import Icon from "../utils/Icon";
import Placeholder from '../Placeholder/Placeholder';
import useFields from "../Settings/FieldsData";
import useProgress from "../Dashboard/Progress/ProgressData";
import useOnboardingData from "./OnboardingData";
import useRiskData from "../Settings/RiskConfiguration/RiskData";
import OnboardingControls from "./OnboardingControls";
import StepEmail from "./StepEmail";
import StepConfig from "./StepConfig";

const Onboarding = ({isModal}) => {
    const { fetchFieldsData, fieldsLoaded, updateField, setChangedField, saveFields} = useFields();
    const { getProgressData} = useProgress();
    const [hardeningEnabled, setHardeningEnabled] = useState(false);
    const [vulnerabilityDetectionEnabled, setVulnerabilityDetectionEnabled] = useState(false);
    const {
        fetchFirstRun, fetchVulnerabilities
    } = useRiskData();
    const {
        getSteps,
        error,
        certificateValid,
        networkwide,
        sslEnabled,
        dataLoaded,
        processing,
        currentStep,
        currentStepIndex,
        setCurrentStepIndex,
        networkActivationStatus,
        networkProgress,
        activateSSLNetworkWide,
    } = useOnboardingData();

    useEffect( () => {
        if (networkwide && networkActivationStatus==='main_site_activated') {
            //run networkwide activation with a delay
            setTimeout( () => {
                activateSSLNetworkWide();
            }, 1000);
        }
    }, [networkActivationStatus, networkProgress])

    useEffect(() => {
        if ( !fieldsLoaded ) {
            fetchFieldsData();
        }
    }, []);


    useEffect( () => {
        const run = async () => {
            await getSteps(false);
            if ( dataLoaded && sslEnabled && currentStepIndex===0) {
                console.log("ssl enabled");
                setCurrentStepIndex(1)
            }
        }
        run();
    }, [])

    useEffect( () => {
        if (currentStep && currentStep.items) {
            let hardeningItem = currentStep.items.find((item) => {
                return item.id === 'hardening';
            })
            if (hardeningItem) {
                setHardeningEnabled(hardeningItem.status === 'success');
            }
            let vulnerabilityDetection = currentStep.items.find((item) => {
                return item.id === 'vulnerability_detection';
            })
            if (vulnerabilityDetection) {
                setVulnerabilityDetectionEnabled(vulnerabilityDetection.status === 'success');
            }
        }
    }, [currentStep]);

    //ensure all fields are updated, and progress is retrieved again
    useEffect( () => {
        const runUpdate = async () => {
            //in currentStep.items, find item with id 'hardening'
            //if it has status 'completed' fetchFieldsData again.
            if ( hardeningEnabled ) {
                await fetchFieldsData('hardening');
                await getProgressData();
            }

            if (vulnerabilityDetectionEnabled) {
                await fetchFieldsData('vulnerabilities');
                await fetchFirstRun();
                await fetchVulnerabilities();
                await getProgressData();
            }
        }
        runUpdate();
    }, [hardeningEnabled, vulnerabilityDetectionEnabled])

    console.log(currentStepIndex, currentStep);

    if (error){
        return (
            <Placeholder lines="3" error={error}></Placeholder>
        )
    }
    let processingClass = processing ? 'rsssl-processing' : '';
    //get 'other_host_type' field from fields


    return (
        <>
            { !dataLoaded &&
                <>
                    <div className="rsssl-onboarding-placeholder">
                        <ul>
                            <li><Icon name = "loading" color = 'grey' />{__("Fetching next step...", "really-simple-ssl")}</li>
                        </ul>
                        <Placeholder lines="3" ></Placeholder>
                    </div>
                </>
            }
            {
                dataLoaded &&
                    <div className={ processingClass }>
                        { currentStep.id === 'activate_ssl' &&
                          <>
                              <StepConfig />
                          </>
                        }
                        { currentStep.id === 'email'&&
                            <>
                                <StepEmail />
                            </>
                        }

                        { currentStep.id === 'plugins' &&
                            <>
                                <StepPlugins />
                            </>
                        }
                        { certificateValid && currentStep.info_text && <div className="rsssl-modal-description" dangerouslySetInnerHTML={{__html: currentStep.info_text}} /> }
                        { !isModal &&
                            <OnboardingControls isModal={false}/>
                        }
                    </div>
            }
        </>
    )
}

export default Onboarding;