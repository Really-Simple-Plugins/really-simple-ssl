import {memo, useEffect} from "@wordpress/element";
import useOnboardingData from "../OnboardingData";
import CheckboxItem from "../Items/CheckboxItem";

const StepPlugins = () => {
    const {
        currentStep,
        currentStepIndex,
        setCurrentStepIndex,
    } = useOnboardingData();

    useEffect(()=> {
        //if all plugins are already activated, we skip the plugins step
        let plugins = currentStep.items;
        if ( plugins.filter(item => item.action !== 'none').length === 0) {
            setCurrentStepIndex(currentStepIndex+1);
        }
    }, [] );

    let plugins = currentStep.items;

    return (
        <>
            <ul>
                { plugins && plugins.map( (item, index) => <CheckboxItem key={'step-plugins'+index} item={item} disabled={item.action==='none'} />) }
            </ul>
        </>
    );
}
export default memo(StepPlugins);