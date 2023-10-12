import {useEffect, useState} from "@wordpress/element";
import Onboarding from "./Onboarding";
import Placeholder from '../Placeholder/Placeholder';
import { __ } from '@wordpress/i18n';
import Icon from "../utils/Icon";
import useOnboardingData from "./OnboardingData";
import useFields from "../Settings/FieldsData";
import './onboarding.scss';
import RssslModal from "../../../modal/src/components/Modal/RssslModal";
import OnboardingButtons from "./OnboardingButtons";

const OnboardingModal = () => {
    const {showOnboardingModal, fetchOnboardingModalStatus, modalStatusLoaded, currentStep, dismissModal} = useOnboardingData();
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
    const setOpen = (open) => {
        if ( !open ) {
            dismissModal();
        }
    }

    return (
        <>
                <RssslModal
                    title={currentStep.title}
                    subTitle={currentStep.subtitle}
                    content={modalContent()}
                    isOpen={showOnboardingModal}
                    setOpen={setOpen}
                    buttons=<OnboardingButtons />
                />
        </>
    )
}

export default OnboardingModal;