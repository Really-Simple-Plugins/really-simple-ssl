import { __ } from '@wordpress/i18n';
import useLearningMode from "./LearningModeData";
const ChangeStatus = (props) => {
    const {updateStatus} = useLearningMode();

    let statusClass = props.item.status==1 ? 'button button-primary rsssl-status-allowed' : 'button button-default rsssl-status-revoked';
    let label = props.item.status==1 ? __("Revoke", "really-simple-ssl") : __("Allow", "really-simple-ssl");
    return (
        <button onClick={ () => updateStatus( props.item.status, props.item, props.field.id ) } className={statusClass}>{label}</button>
    )
}
export default ChangeStatus