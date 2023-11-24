import useOnboardingData from "../OnboardingData";
import {memo} from "@wordpress/element";

const CheckboxItem = ({item}) => {
    const {
        updateItemStatus,
    } = useOnboardingData();
    let { title, id, activated } = item;

    return (
        <li>
            <label className="rsssl-modal-checkbox-container">
                <input type="checkbox" checked={activated} value={id} id={id} onChange={(e) => updateItemStatus(id, null, null, e.target.checked )}/>
                <span className="rsssl-checkmark"></span>
            </label>
            {title}
        </li>
    )
}
export default memo(CheckboxItem)