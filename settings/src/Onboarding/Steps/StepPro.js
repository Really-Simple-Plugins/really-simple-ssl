import {memo} from "@wordpress/element";
import useOnboardingData from "../OnboardingData";
import CheckboxItem from "../Items/CheckboxItem";
import PremiumItem from "../Items/PremiumItem";

const StepPro = () => {
    const {
        currentStep,
    } = useOnboardingData();

    let premiumItems = currentStep.items;
    return (
        <>
            <ul>
                {!rsssl_settings.pro_plugin_active && premiumItems && (
                    <div className="rsssl-premium-items">
                        {premiumItems.map((item, index) => (
                            <PremiumItem key={'step-pro' + index} item={item}/>
                        ))}
                    </div>
                )}
                {rsssl_settings.pro_plugin_active && premiumItems && (
                    <div className="rsssl-checkbox-items">
                        {premiumItems.map((item, index) => (
                            <CheckboxItem key={'step-pro' + index} item={item}/>
                        ))}
                    </div>
                )}
            </ul>
        </>
    );
}
export default memo(StepPro);