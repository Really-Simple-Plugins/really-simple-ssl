import {
    Component,
} from '@wordpress/element';

class SecurityFeaturesBlock extends Component {
    constructor() {
        super( ...arguments );
    }

    render() {

        let fields = this.props.fields;

        let shownFields = [
            'file_editing',
            'anyone_can_register',
        ];

        // fields.map(item => {
        //     console.log(item);
        // })

        shownFields.forEach(function (e) {
            console.log(fields.indexOf(e));
            // console.log(fields);
            if ( fields.id === e ) {

            }
        });

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