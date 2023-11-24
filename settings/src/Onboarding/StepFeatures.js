import {memo} from "@wordpress/element";
import useOnboardingData from "./OnboardingData";
import CheckboxItem from "./CheckboxItem";

const StepFeatures = () => {
    const {
        currentStep
    } = useOnboardingData();
    let items = currentStep.items ? currentStep.items : [];
    return (
        <>
            <ul>
                { items && items.map( (item, index) => <CheckboxItem key={index} item={item} />) }
            </ul>
        </>
    );
}
export default memo(StepFeatures)