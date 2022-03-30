import {
    Component,
} from '@wordpress/element';

class SecurityFeaturesBlock extends Component {
    constructor() {
        super( ...arguments );
    }

    status() {
        // Get valid or invalid
    }

    render(){

        let fields = this.props.fields;
        console.log(fields);
        return (
            <div className="rsssl-security-row">Security
                <div className={this.props.status}></div>
                <div className="rsssl-security-content">
                    {this.props.content}
                </div>
            </div>
        );
    }
}
export default SecurityFeaturesBlock;