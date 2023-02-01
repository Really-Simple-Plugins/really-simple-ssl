import { __ } from '@wordpress/i18n';
const ChangeStatus = (props) => {
    let statusClass = props.item.status==1 ? 'button button-primary rsssl-status-allowed' : 'button button-default rsssl-status-revoked';
    let label = props.item.status==1 ? __("Revoke", "really-simple-ssl") : __("Allow", "really-simple-ssl");
    return (
        <button onClick={ () => props.onChangeHandlerDataTableStatus( props.item.status, props.item, 'status' ) } className={statusClass}>{label}</button>
    )
}
export default ChangeStatus