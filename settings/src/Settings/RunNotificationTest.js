import {
    Component,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {Button} from "@wordpress/components";
import Notices from "./Notices";
import Help from "./Help";


/**
 * Runs the notification test for vulnerabilities.
 */
class RunNotificationTest extends Component {
    constructor() {
        super(...arguments);
        this.state = {
            notifications: []
        };
        // this.addHelp('vulnerabilities', 'Test Notification', 'This will send a test notification to the configured email address.', 'TEster');
    }

    onClickHandler(action) {
       let HelpDom = document.getElementsByClassName('rsssl-wizard-help')[0];
        let field = this.props.field;
        let fields = this.props.fields;
        //now we remove the id vulnerabitlities_test from the fields
        fields = fields.filter(function (field) {
            if (field.id !== 'vulnerabilities_test') {
                return field;
            }
        });
        fields.forEach((field, i) => {
            this.addHelp(field.id, field, field.label, HelpDom);
        })
    }

    addHelp(id, field, fieldName, DOM) {
        console.log(field, fieldName);
        let newElement = document.createElement('div');
        let help = {
            label: 'default',
            title: 'default',
            text: 'default',
        };
        ReactDOM.render(<Help key={id} noticesExpanded={true} index={id} help={help} fieldId={fieldName}/>, newElement);
        DOM.appendChild(newElement);
    }

    render() {
        if ( this.highLightClass ) {
            this.scrollAnchor = React.createRef();
        }
        let field = this.props.field;
        field.title = 'test';
        let fields = this.props.fields;
        let disabled = this.props.disabled;

        return (
            <div className={'rsssl-field-button ' + this.highLightClass} ref={this.scrollAnchor}>
                <label>{field.label}</label>
                <Button isPrimary={true} onClick={() => this.onClickHandler()}>{field.button_text}</Button>

            </div>
        );
    }

}

export default RunNotificationTest;