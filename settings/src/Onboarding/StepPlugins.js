import {memo} from "@wordpress/element";
import useOnboardingData from "./OnboardingData";

const StepPlugins = () => {
    const {
        currentStep,
    } = useOnboardingData();
    let plugins = currentStep.items;
    return (
        <>
            { plugins && plugins.map( (plugin, index ) =>
                <>
                    <input type="checkbox" value={plugin.slug} key={index} />
                    <b>{plugin.title}</b>&nbsp;-&nbsp;{plugin.description}
                </>)}

</>
    );
}
export default memo(StepPlugins);