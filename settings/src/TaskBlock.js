import * as rsssl_api from "./utils/api";
import {
    Component,
} from '@wordpress/element';

class TaskBlock extends Component {
    constructor() {
        super( ...arguments );
    }
    render(){
        let disabled = this.props.disabled ? 'disabled' : '';
        return (
            <button className="button-primary" disabled={disabled} onClick={this.props.onClick}>{this.props.text}</button>
        );
    }
}
export default TaskBlock;