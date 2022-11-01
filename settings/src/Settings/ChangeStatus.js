import {
    Component,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
class ChangeStatus extends Component {
    constructor() {
        super( ...arguments );
    }
    render(){
        let statusClass = this.props.item.status==1 ? 'button button-primary rsssl-status-allowed' : 'button button-default rsssl-status-revoked';
        let label = this.props.item.status==1 ? __("Revoke", "really-simple-ssl") : __("Allow", "really-simple-ssl");
        return (
            <button onClick={ () => this.props.onChangeHandlerDataTableStatus( this.props.item.status, this.props.item, 'status' ) } className={statusClass}>{label}</button>
        )
    }
}
export default ChangeStatus