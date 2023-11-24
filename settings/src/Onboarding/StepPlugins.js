import {memo} from "@wordpress/element";
import useOnboardingData from "./OnboardingData";
import CheckboxItem from "./Items/CheckboxItem";

const StepPlugins = () => {
    const {
        currentStep,
    } = useOnboardingData();


    let plugins = currentStep.items;
    return (
        <>
            <ul>
                { plugins && plugins.map( (item, index) => <CheckboxItem key={index} item={item} />) }
            </ul>
        </>
    );
}
export default memo(StepPlugins);