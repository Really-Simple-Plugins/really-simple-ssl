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
import StepFeatures from "./StepFeatures";
import StepPlugins from "./StepPlugins";

const Onboarding = ({isModal}) => {
    const { fetchFieldsData, fieldsLoaded} = useFields();
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
                setCurrentStepIndex(1)
            }
        }
        run();
    }, [])

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
                        { currentStep.id === 'features'&&
                            <>
                                <StepFeatures />
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