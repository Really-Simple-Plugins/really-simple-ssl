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

class PermissionsPolicy extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            filterValue:0,
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

    doFilter(e){
        this.setState({
            filterValue :e.target.value,
        });
    }

    render(){
        let field = this.props.field;
        let fieldValue = field.value;
        let options = this.props.options;
        const {
            enable_permissions_policy,
            filterValue,
        } = this.state;

    const Filter = () => (
      <>
        <select onChange={ ( e ) => this.doFilter(e) }>
            <option value="-1" selected={filterValue==-1}>{__("All", "really-simple-ssl")}</option>
            <option value="1" selected={filterValue==1}>{__("Allowed", "really-simple-ssl")}</option>
            <option value="0" selected={filterValue==0}>{__("Blocked", "really-simple-ssl")}</option>
        </select>
      </>
    );
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
        if (filterValue!=-1) {
            data = data.filter(item => item.status==filterValue);
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
                        pagination
                        subHeader
                        subHeaderComponent={<Filter />}
                        customStyles={customStyles}
                        theme="really-simple-plugins"
                    />
                    { enable_permissions_policy!=1 && <button className="button" onClick={ (e) => this.togglePermissionsPolicyStatus(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
                    { enable_permissions_policy==1 && <button className="button" onClick={ (e) => this.togglePermissionsPolicyStatus(e, false ) }>{__("Disable","really-simple-ssl")}</button> }
            </div>
        )
    }
}


export default PermissionsPolicy