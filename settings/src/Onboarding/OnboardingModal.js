import {useEffect} from "@wordpress/element";
import Onboarding from "./Onboarding";
import Placeholder from '../Placeholder/Placeholder';
import { __ } from '@wordpress/i18n';
import Icon from "../utils/Icon";
import useOnboardingData from "./OnboardingData";
import useFields from "../Settings/FieldsData";
import './onboarding.scss';
import RssslModal from "../../../modal/src/components/Modal/RssslModal";
import OnboardingControls from "./OnboardingControls";

const OnboardingModal = () => {
    const {networkwide, networkProgress, currentStepIndex, showOnboardingModal, fetchOnboardingModalStatus, modalStatusLoaded, currentStep, dismissModal} = useOnboardingData();
    const {fieldsLoaded} = useFields();

    useEffect(() => {
        if ( !modalStatusLoaded ) {
            fetchOnboardingModalStatus();
        }
    }, []);

    useEffect(()=> {
        if ( showOnboardingModal ) {
            dismissModal(false);
        }
    }, [showOnboardingModal]);

    const modalContent = () => {
        return (
            <>
                { !fieldsLoaded &&
                    <>
                        <ul>
                            <li><Icon name = "loading" />{__("Please wait while we detect your setup", "really-simple-ssl")}</li>
                        </ul>
                        <Placeholder lines="3"></Placeholder>
                    </>
                }
                { fieldsLoaded && <Onboarding isModal={true} /> }
            </>
        )
    }

    /**
     * On the email step, show a progress bar for the networkwide activation, if multisite
     * @returns {JSX.Element|boolean}
     */
    const multisiteProgress = () => {
        if ( currentStepIndex===0 ) {
            return false;
        }
        if ( !networkwide ) {
            return false;
        }
        let progress = networkProgress;
        if ( typeof progress === 'undefined' ) {
            progress = 0
        }

        if ( currentStepIndex>1 && progress>=100) {
            return false;
        }

        return (
            <>
               { progress<100 && <Icon name = "loading" color = 'grey' /> }
               { progress>=100 && <Icon name="circle-check" color='green'/> }
               {__("%d% of subsites activated.").replace('%d', progress)}</>
        );
    }
    const setOpen = (open) => {
        if ( !open ) {
            dismissModal(true);
        }
    }

    return (
        <>
                <RssslModal
                    className={"rsssl-onboarding-modal"}
                    title={currentStep.title}
                    subTitle={currentStep.subtitle}
                    content={modalContent()}
                    isOpen={showOnboardingModal}
                    setOpen={setOpen}
                    buttons=<OnboardingControls isModal={true} />
                    footer = {multisiteProgress()}
                />
        </>
    )
}

export default OnboardingModal;