import {memo} from "@wordpress/element";
const PremiumItem = ({item}) => {
    let { title } = item;
    return (
        <li>
            <div className="rsssl-modal-premium-container">
                PRO
            </div>
            {title}
        </li>
    )
}
export default memo(PremiumItem)