import {memo, useEffect, useRef} from "@wordpress/element";
import useOnboardingData from "../OnboardingData";
import License from "../../Settings/License/License";
import useFields from "../../Settings/FieldsData";
import useLicense from "../../Settings/License/LicenseData";

const StepLicense = () => {
    const {
        currentStepIndex,
        setCurrentStepIndex,
    } = useOnboardingData();
    const { getField } = useFields();
    const {licenseStatus} = useLicense();
    const pro_plugin_active = rsssl_settings.pro_plugin_active;

    //skip step if either already active, or if not pro
    useEffect( () => {
        if ( ! pro_plugin_active || licenseStatus === 'valid' ) {
            setCurrentStepIndex(currentStepIndex + 1);
        }
    }, [licenseStatus, pro_plugin_active] );

    return (
        <div className={"rsssl-license"}>
            <License
                field={getField('license')}
                isOnboarding={true}
            />
        </div>
    );
};

export default memo(StepLicense);