import {
    PanelBody,
    SelectControl,
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';
import ChangeStatus from "./ChangeStatus";
import DataTable from "react-data-table-component";
import * as rsssl_api from "../utils/api";

class subHeaderComponentMemo extends Component {
    constructor() {
        super( ...arguments );
    }
    render() {
        return (
            <select>
                <option>{__("Allowed", "really-simple-ssl")}</option>
                <option>{__("Revoked", "really-simple-ssl")}</option>
            </select>
        );
    }
}

class ContentSecurityPolicy extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            csp_enforce :0,
            csp_learning_mode :0
        };
    }

    componentDidMount() {
        let enforce_field = this.props.fields.filter(field => field.id === 'csp_enforce')[0];
        let learning_mode_field = this.props.fields.filter(field => field.id === 'csp_learning_mode')[0];
        this.setState({
            csp_enforce :enforce_field.value,
            csp_learning_mode :learning_mode_field.value
        });
    }

    toggleEnforce(e, enforce){

        let fields = this.props.fields;
        //look up permissions policy enable field //enable_permissions_policy
        let field = fields.filter(field => field.id === 'csp_enforce')[0];
        let learning_mode_field = fields.filter(field => field.id === 'csp_learning_mode')[0];
        //disable learning mode if enforced
        if (enforce==1){
            learning_mode_field.value=0;
        }
        //enforce this setting
        field.value=enforce;
        this.setState({
            csp_enforce :enforce
        });
        let saveFields = [];
        saveFields.push(field);
        saveFields.push(learning_mode_field);
        this.props.updateField(field);
        this.props.updateField(learning_mode_field);
        rsssl_api.setFields(saveFields).then(( response ) => {
            //this.props.showSavedSettingsNotice();
        });
    }

    toggleLearningMode(e, enforce){

        let fields = this.props.fields;
        //look up permissions policy enable field //enable_permissions_policy
        let field = fields.filter(field => field.id === 'csp_learning_mode')[0];
        //enforce this setting
        enforce = field.value==1 ? 0 : 1;
        field.value=enforce;
        this.setState({
            csp_learning_mode :enforce
        });
        let saveFields = [];
        saveFields.push(field);
        this.props.updateField(field);
        rsssl_api.setFields(saveFields).then(( response ) => {
            //this.props.showSavedSettingsNotice();
        });
    }

    render(){
            let field = this.props.field;
            let fieldValue = field.value;
            let options = this.props.options;
            const {
                csp_enforce,
                csp_learning_mode,
            } = this.state;
            //build our header
            columns = [];
            field.columns.forEach(function(item, i) {
                let newItem = {
                    name: item.name,
                    sortable: item.sortable,
                    width: item.width,
                    selector: row => row[item.column],
                }
                columns.push(newItem);
            });
            let data = field.value;

            if (typeof data === 'object') {
                data = Object.values(data);
            }
            if (!Array.isArray(data) ) {
                data = [];
            }
            for (const item of data){
                item.statusControl = <ChangeStatus item={item} onChangeHandlerDataTable={this.props.onChangeHandlerDataTable}
                />;
            }

            return (
                <PanelBody className={ this.highLightClass}>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                        noDataComponent={__("No results", "really-simple-ssl")}
                    />

                    { csp_enforce!=1 && <button className="button" onClick={ (e) => this.toggleEnforce(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
                    { csp_enforce==1 && <button className="button" onClick={ (e) => this.toggleEnforce(e, false ) }>{__("Disable","really-simple-ssl")}</button> }
                    <label>
                    <input type="checkbox"
                        disabled = {csp_enforce}
                        checked ={csp_learning_mode==1}
                        value = {csp_learning_mode}
                        onChange={ ( fieldValue ) => this.toggleLearningMode() }
                        subHeaderComponent={subHeaderComponentMemo}
                    />
                    {__("Enable Learning Mode","really-simple-ssl")}
                    </label>
                </PanelBody>
            )
    }
}


export default ContentSecurityPolicy