import {runTest} from "./utils/api";
import {
    Placeholder,
    PanelBody,
    TextControl
} from '@wordpress/components';
import {
    Component,
} from '@wordpress/element';
import TaskElement from "./TaskElement";
import * as rsssl_api from "./utils/api";

class License extends Component {
    constructor() {
        super( ...arguments );
        this.noticesLoaded = false;
        this.fieldsUpdateComplete = false;
        this.licenseStatus = 'invalid';
        this.state = {
            licenseStatus: 'invalid',
            noticesLoaded: false,
            notices: [],
        };
        this.highLightClass = this.props.highLightedField===this.props.field.id ? 'rsssl-highlight' : '';
    }

    updateLicenseNotice(){

    }

    getLicenseNotices(){
        return rsssl_api.runTest('licenseNotices', 'refresh').then( ( response ) => {
            return response.data;
        });
    }

    componentDidMount() {
        this.props.highLightField('');
        this.getLicenseNotices = this.getLicenseNotices.bind(this);
        this.setState({
            noticesLoaded: this.noticesLoaded,
            licenseStatus: this.licenseStatus,
            notices: this.notices,
        });
    }

    componentDidUpdate(prevProps) {
        if (!this.fieldsUpdateComplete && this.props.fieldsUpdateComplete ) {
            this.getLicenseNotices().then(( response ) => {
                this.fieldsUpdateComplete = this.props.fieldsUpdateComplete;
                this.props.setPageProps('licenseStatus', response.licenseStatus);
                this.notices = response.notices;
                this.licenseStatus = response.licenseStatus;
                this.noticesLoaded = true;
                this.setState({
                    noticesLoaded: this.noticesLoaded,
                    licenseStatus: this.licenseStatus,
                    notices: this.notices,
                });
            });
        }
    }

    onChangeHandler(fieldValue) {
        this.fieldsUpdateComplete = false;
        let fields = this.props.fields;
        let field = this.props.field;
        fields[this.props.index]['value'] = fieldValue;
        this.props.saveChangedFields( field.id )
        this.setState({
            fields: fields,
        })
    }
    onCloseTaskHandler(){

    }
    render(){
        const {
            noticesLoaded,
            notices,
            licenseStatus,
        } = this.state;
        let field = this.props.field;
        let fieldValue = field.value;
        let fields = this.props.fields;

        /**
         * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
         */
        return (
            <PanelBody className={ this.highLightClass}>
                <div className="components-base-control">
                 <div className="components-base-control__field">
                     <label
                         className="components-base-control__label"
                         htmlFor={field.id}>{field.label}</label>
                     <input className="components-text-control__input"
                            type="password"
                            id={field.id}
                            value={fieldValue}
                            onChange={ ( e ) => this.onChangeHandler(e.target.value) }
                     />
                 </div>
                    {!noticesLoaded && <Placeholder></Placeholder>}
                    {noticesLoaded && notices.map((notice, i) => <TaskElement key={i} index={i} notice={notice} onCloseTaskHandler={this.onCloseTaskHandler} highLightField=""/>)}
                </div>
            </PanelBody>
        );

    }
}

export default License;