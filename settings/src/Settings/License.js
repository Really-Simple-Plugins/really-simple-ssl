import {
    Component,
} from '@wordpress/element';
import TaskElement from "../DashBoard/TaskElement";
import Placeholder from '../Placeholder/Placeholder';
import * as rsssl_api from "../utils/api";
import { __ } from '@wordpress/i18n';


class License extends Component {
    constructor() {
        super( ...arguments );
        this.noticesLoaded = false;
        this.fieldsUpdateComplete = false;
        this.licenseStatus = 'invalid';
        this.getLicenseNotices = this.getLicenseNotices.bind(this);

        this.state = {
            licenseStatus: 'invalid',
            noticesLoaded: false,
            notices: [],
        };
    }

    getLicenseNotices(){
        return rsssl_api.doAction('license_notices').then( ( response ) => {
            return response;
        });
    }

    componentDidMount() {
        this.props.highLightField('');
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

    toggleActivation(){
         this.setState({
            noticesLoaded: false,
        });

        const {
            licenseStatus,
        } = this.state;
        if ( licenseStatus==='valid' ) {
            rsssl_api.doAction('deactivate_license').then( ( response ) => {
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
        } else {
            let data = {};
            data.license = this.props.field.value;
            rsssl_api.doAction('activate_license', data).then( ( response ) => {
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
                <div className="components-base-control">
                 <div className="components-base-control__field">
                     <label
                         className="components-base-control__label"
                         htmlFor={field.id}>{field.label}</label>
                      <div className="rsssl-license-field">
                         <input className="components-text-control__input"
                                type="password"
                                id={field.id}
                                value={fieldValue}
                                onChange={ ( e ) => this.onChangeHandler(e.target.value) }
                         />
                         <button className="button button-default" onClick={ () => this.toggleActivation() }>
                         {licenseStatus==='valid' && <>{__("Deactivate","really-simple-ssl")}</>}
                         {licenseStatus!=='valid' && <>{__("Activate","really-simple-ssl")}</>}
                         </button>
                     </div>
                 </div>
                    {!noticesLoaded && <Placeholder></Placeholder>}
                    {noticesLoaded && notices.map((notice, i) => <TaskElement key={i} index={i} notice={notice} onCloseTaskHandler={this.onCloseTaskHandler} highLightField=""/>)}
                </div>
        );

    }
}

export default License;