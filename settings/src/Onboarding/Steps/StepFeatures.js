import {memo} from "@wordpress/element";
import useOnboardingData from "../OnboardingData";
import CheckboxItem from "../Items/CheckboxItem";
import PremiumItem from "../Items/PremiumItem";

const StepFeatures = () => {
    const {
        currentStep
    } = useOnboardingData();

    let items = currentStep.items ? currentStep.items : [];
    let freeItems = items.filter( (item) => !item.premium );
    let premiumItems = items.filter( (item) => item.premium );
    return (
        <>
            <ul>
                { freeItems && freeItems.map( (item, index) => <CheckboxItem key={'step-features'+index} item={item} />) }
                { premiumItems && premiumItems.map( (item, index) => <PremiumItem key={'step-features'+index} item={item} />) }
            </ul>
        </>
    );
}
export default memo(StepFeatures)