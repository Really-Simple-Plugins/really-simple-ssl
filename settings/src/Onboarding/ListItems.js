import {memo, useEffect} from "@wordpress/element";
import useOnboardingData from "./OnboardingData";
import ListItem from "./ListItem";

const ListItems = () => {
    const {
        currentStep,
        networkwide,
    } = useOnboardingData();

    let items = currentStep.items;
    return (
        <ul>
            { items && items.map( (item, index) => <ListItem key={index} item={item} />) }
        </ul>
    );
}
export default memo(ListItems)