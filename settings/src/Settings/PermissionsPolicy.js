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

class PermissionsPolicy extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            enable_permissions_policy: 0,
        };
    }

    componentDidMount() {
        let field = this.props.fields.filter(field => field.id === 'enable_permissions_policy')[0];
        this.setState({
            enable_permissions_policy :field.value
        });
    }

    togglePermissionsPolicyStatus(e, enforce){
        console.log("enforce");
        console.log(enforce);
        let fields = this.props.fields;
        //look up permissions policy enable field //enable_permissions_policy
        let field = fields.filter(field => field.id === 'enable_permissions_policy')[0];
        //enforce this setting
        field.value=enforce;
        this.setState({
            enable_permissions_policy :enforce
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
            enable_permissions_policy,
        } = this.state;
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
            let disabled = false;
            if (item.status!=1) {
                item.value = '()';
                disabled = true;
            }
            item.valueControl = <SelectControl
                help=''
                value={item.value}
                disabled={disabled}
                options={options}
                label=''
                onChange={ ( fieldValue ) => this.onChangeHandlerDataTable( fieldValue, item, 'value' ) }
            />
            item.statusControl = <ChangeStatus item={item} onChangeHandlerDataTable={this.props.onChangeHandlerDataTable}
            />;
        }

        return (
            <div>
                <PanelBody className={ this.props.highLightClass}>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                    />
                    { enable_permissions_policy!=1 && <button className="button" onClick={ (e) => this.togglePermissionsPolicyStatus(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
                    { enable_permissions_policy==1 && <button className="button" onClick={ (e) => this.togglePermissionsPolicyStatus(e, false ) }>{__("Disable","really-simple-ssl")}</button> }
                </PanelBody>
            </div>
        )
    }
}


export default PermissionsPolicy