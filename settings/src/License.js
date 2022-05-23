import {runTest} from "./utils/api";
import {
    PanelBody,
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
        this.state = {
            noticesLoaded: false,
            notices: [],
        };
        this.highLightClass = this.props.highLightedField===this.props.field.id ? 'rsssl-highlight' : '';

        this.getLicenseNotices().then(( response ) => {
            this.notices = response.notices;
            console.log("result notices");
            console.log(this.notices);
            this.noticesLoaded = true;
            this.setState({
                noticesLoaded: this.noticesLoaded,
                notices: this.notices,
            });
        });
    }

    getLicenseNotices(){
        return rsssl_api.runTest('licenseNotices', 'refresh').then( ( response ) => {
            return response.data;
        });
    }

    componentDidMount() {
        this.props.highLightField('');
        // this.getLicenseNotices = this.getLicenseNotices.bind(this);
        // let noticesLoaded = this.noticesLoaded;
        // let notices = this.notices;
        //
        this.setState({
            noticesLoaded: this.noticesLoaded,
            notices: this.notices,
        });
    }

    onChangeHandler(fieldValue) {
        let fields = this.props.fields;
        let field = this.props.field;
        fields[this.props.index]['value'] = fieldValue;
        this.props.saveChangedFields( field.id )
        this.setState( { fields } )
    }
    onCloseTaskHandler(){

    }
    render(){
        const {
            noticesLoaded,
            notices,
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
                               onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                        />
                    </div>
                    {noticesLoaded && notices.map((notice, i) => <TaskElement key={i} index={i} notice={notice} onCloseTaskHandler={this.onCloseTaskHandler} highLightField=""/>)}
                </div>
            </PanelBody>
        );

    }
}

export default License;