import {memo} from "@wordpress/element";
import useOnboardingData from "./OnboardingData";
import CheckboxItem from "./Items/CheckboxItem";
import PremiumItem from "./Items/PremiumItem";

const StepPro = () => {
    const {
        currentStep,
    } = useOnboardingData();

    let premiumItems = currentStep.items;
    return (
        <>
            <ul>
                { !rsssl_settings.pro_plugin_active && premiumItems && premiumItems.map( (item, index) => <PremiumItem key={index} item={item} />) }
                { rsssl_settings.pro_plugin_active && premiumItems && premiumItems.map( (item, index) => <CheckboxItem key={index} item={item} />) }
            </ul>
        </>
    );
}
export default memo(StepPro);