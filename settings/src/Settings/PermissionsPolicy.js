import {
    SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {useState,useEffect} from '@wordpress/element';
import Icon from "../utils/Icon";
import useFields from "./FieldsData";

const PermissionsPolicy = (props) => {
    const {fields, updateField, updateSubField, setChangedField, saveFields} = useFields();
    const [enablePermissionsPolicy, setEnablePermissionsPolicy] = useState(0);
    const [DataTable, setDataTable] = useState(null);
    const [theme, setTheme] = useState(null);
    useEffect( () => {
        import('react-data-table-component').then(({ default: DataTable, createTheme }) => {
            setDataTable(() => DataTable);
            setTheme(() => createTheme('really-simple-plugins', {
                divider: {
                    default: 'transparent',
                },
            }, 'light'));
        });

    }, []);

    useEffect( () => {
        let field = fields.filter(field => field.id === 'enable_permissions_policy')[0];
        setEnablePermissionsPolicy(field.value);
    }, [] );

    const onChangeHandler = (value, clickedItem ) => {
        let field= props.field;
        if (typeof field.value === 'object') {
            updateField(field.id, Object.values(field.value))
        }

        //the updateItemId allows us to update one specific item in a field set.
        updateSubField(field.id, clickedItem.id, value);
        setChangedField(field.id, value);
        saveFields(true, false);
    }

    const togglePermissionsPolicyStatus = (e, enforce) => {
         e.preventDefault();
        //look up permissions policy enable field //enable_permissions_policy
        let field = fields.filter(field => field.id === 'enable_permissions_policy')[0];
        //enforce setting
        setEnablePermissionsPolicy(enforce);
        updateField(field.id, enforce);
        setChangedField(field.id, field.value);
        saveFields(true, false);
    }

    let field = props.field;
    let fieldValue = field.value;
    let options = props.options;

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
    let outputData = [];
    for (const item of data){
        let itemCopy = {...item};
        itemCopy.valueControl = <SelectControl
            help=''
            value={item.value}
            disabled={disabled}
            options={options}
            label=''
            onChange={ ( fieldValue ) => onChangeHandler( fieldValue, item, 'value' ) }
        />
        outputData.push(itemCopy);
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

    if (!DataTable || !theme) return null;

    return (
        <div>
                <DataTable
                    columns={columns}
                    data={outputData}
                    dense
                    pagination={false}
                    customStyles={customStyles}
                    theme={theme}
                />
                { enablePermissionsPolicy!=1 && <button className="button button-primary" onClick={ (e) => togglePermissionsPolicyStatus(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
                { enablePermissionsPolicy==1 && <div className="rsssl-locked">
                    <div className="rsssl-shield-overlay">
                        <Icon name = "shield"  size="80px"/>
                    </div>
                    <div className="rsssl-locked-overlay">
                        <span className="rsssl-progress-status rsssl-learning-mode-enforced">{__("Enforced","really-simple-ssl")}</span>
                        { props.disabled && <>{ __("Permissions Policy is set outside Really Simple SSL.", "really-simple-ssl")}&nbsp;</>}
                        { !props.disabled && <>{__("Permissions Policy is enforced.", "really-simple-ssl")}&nbsp;</>}
                        { !props.disabled && <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => togglePermissionsPolicyStatus(e, false) }>{__("Disable", "really-simple-ssl") }</a> }
                    </div>
                </div>}
                { props.disabled && enablePermissionsPolicy!=1 && <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay">
                        <span className="rsssl-progress-status rsssl-disabled">{__("Disabled","really-simple-ssl")}</span>
                        {__("The Permissions Policy has been disabled.", "really-simple-ssl")}
                    </div>
                </div>}
        </div>
    )
}


export default PermissionsPolicy