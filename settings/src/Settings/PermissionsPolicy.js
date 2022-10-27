import {
    SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';
import ChangeStatus from "./ChangeStatus";
import DataTable, {createTheme} from 'react-data-table-component';
import * as rsssl_api from "../utils/api";
import Icon from "../utils/Icon";

class PermissionsPolicy extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            enable_permissions_policy: 0,
        };
    }

    componentDidMount() {
        this.togglePermissionsPolicyStatus = this.togglePermissionsPolicyStatus.bind(this);
        this.onChangeHandler = this.onChangeHandler.bind(this);
        let field = this.props.fields.filter(field => field.id === 'enable_permissions_policy')[0];
        this.setState({
            enable_permissions_policy :field.value
        });
    }

    onChangeHandler(value, clickedItem ) {
        let field=this.props.field;
        if (typeof field.value === 'object') {
            field.value = Object.values(field.value);
        }
        //find this item in the field list
        for (const item of field.value){
            if (item.id === clickedItem.id) {
                item['value'] = value;
            }
            delete item.valueControl;
            delete item.statusControl;
            delete item.deleteControl;
        }
        //the updateItemId allows us to update one specific item in a field set.
        field.updateItemId = clickedItem.id;
        let saveFields = [];
        saveFields.push(field);
        this.props.updateField(field.id, field.value);
        rsssl_api.setFields(saveFields).then(( response ) => {
            //this.props.showSavedSettingsNotice();
        });
    }

    togglePermissionsPolicyStatus(e, enforce){
         e.preventDefault();
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
        this.props.updateField(field.id, field.value);
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
        let disabled = false;
        for (const item of data){
            item.valueControl = <SelectControl
                help=''
                value={item.value}
                disabled={disabled}
                options={options}
                label=''
                onChange={ ( fieldValue ) => this.onChangeHandler( fieldValue, item, 'value' ) }
            />
        }

        const customStyles = {
            headCells: {
                style: {
                    paddingLeft: '0', // override the cell padding for head cells
                    paddingRight: '0',
                },
            },
            cells: {
                style: {
                    paddingLeft: '0', // override the cell padding for data cells
                    paddingRight: '0',
                },
            },
        };

        createTheme('really-simple-plugins', {
            divider: {
                default: 'transparent',
            },
        }, 'light');

        return (
            <div className={ this.props.highLightClass}>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination={false}
                        customStyles={customStyles}
                        theme="really-simple-plugins"
                    />
                    { enable_permissions_policy!=1 && <button className="button button-primary" onClick={ (e) => this.togglePermissionsPolicyStatus(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
                    { enable_permissions_policy==1 && <div className="rsssl-locked">
                        <div className="rsssl-shield-overlay">
                              <Icon name = "shield"  size="80px"/>
                        </div>
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-progress-status rsssl-learning-mode-enforced">{__("Enforced","really-simple-ssl")}</span>
                            {__("Permissions Policy is enforced.", "really-simple-ssl")}&nbsp;
                            <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => this.togglePermissionsPolicyStatus(e, false) }>{__("Disable", "really-simple-ssl") }</a>
                        </div>
                    </div>}
            </div>
        )
    }
}


export default PermissionsPolicy