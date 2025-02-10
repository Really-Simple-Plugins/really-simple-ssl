import { useEffect } from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import Icon from "../utils/Icon";
import Placeholder from '../Placeholder/Placeholder';
import useFields from "../Settings/FieldsData";
import useOnboardingData from "./OnboardingData";
import OnboardingControls from "./OnboardingControls";
import StepEmail from "./Steps/StepEmail";
import StepConfig from "./Steps/StepConfig";
import StepLicense from "./Steps/StepLicense";
import StepFeatures from "./Steps/StepFeatures";
import StepPlugins from "./Steps/StepPlugins";
import StepPro from "./Steps/StepPro";
import './PremiumItem.scss';
import './checkbox.scss';
import './onboarding.scss';
import DOMPurify from 'dompurify';
const Onboarding = ({isModal}) => {
    const { fetchFieldsData, fieldsLoaded} = useFields();
    const {
        getSteps,
        error,
        networkwide,
        sslEnabled,
        dataLoaded,
        processing,
        currentStep,
        currentStepIndex,
        networkActivationStatus,
        networkProgress,
        activateSSLNetworkWide,
        emailVerified
    } = useOnboardingData();

    // Single effect to initialize
    useEffect(() => {
        getSteps(false);
    }, []); // Empty dependency array

    if (error){
        return (
            <Placeholder lines="3" error={error}></Placeholder>
        )
    }

    let processingClass = '';

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
                <div className={ processingClass+" rsssl-"+currentStep.id }>
                    { currentStep.id === 'activate_ssl' &&
                        <>
                            <StepConfig isModal={isModal}/>
                        </>
                    }
                    { currentStep.id === 'activate_license' &&
                        <>
                            <StepLicense />
                        </>
                    }
                    { currentStep.id === 'features'&&
                        <>
                            <StepFeatures />
                        </>
                    }
                    {currentStep.id === 'email' && !emailVerified && (
                        <StepEmail />
                    )}
                    { currentStep.id === 'plugins' &&
                        <>
                            <StepPlugins />
                        </>
                    }
                    { currentStep.id === 'pro' &&
                        <>
                            <StepPro />
                        </>
                    }
                    { !isModal &&
                        <OnboardingControls isModal={false}/>
                    }
                </div>
            }
        </>
    )
}

export default Onboarding;