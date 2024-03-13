import useOnboardingData from "../OnboardingData";
import {memo} from "@wordpress/element";

const CheckboxItem = ({item, disabled}) => {
    const {
        updateItemStatus,
        currentStep
    } = useOnboardingData();
    let { title, description, id, activated } = item;
    return (
        <li>
            <label className="rsssl-modal-checkbox-container">
                <input type="checkbox" disabled={disabled} checked={activated} value={id} id={id} onChange={(e) => updateItemStatus(currentStep.id, id, null, null, e.target.checked )}/>
                <span className="rsssl-checkmark"></span>
            </label>
            {title}
            {description && <> - {description}</>}
        </li>
    )
}
export default memo(CheckboxItem)