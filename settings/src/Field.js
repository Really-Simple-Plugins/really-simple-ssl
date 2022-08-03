import {
    Button,
    Icon,
    Panel,
    PanelBody,
    PanelRow,
    Placeholder,
    Spinner,
    TextControl,
    RadioControl,
    SelectControl,
    __experimentalNumberControl as NumberControl,
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import License from "./License";
import MixedContentScan from "./MixedContentScan";
import {
    Component,
} from '@wordpress/element';

/**
 * https://react-data-table-component.netlify.app
 */
import DataTable from "react-data-table-component";
import * as rsssl_api from "./utils/api";
class ChangeStatus extends Component {
    constructor() {
        super( ...arguments );
    }

    render(){
        let statusClass = this.props.item.status==1 ? 'rsssl-status-allowed' : 'rsssl-status-revoked';
        let label = this.props.item.status==1 ? __("Revoke", "really-simple-ssl") : __("Allow", "really-simple-ssl");
        return (
            <button onClick={ () => this.props.onChangeHandlerDataTable(!this.props.item.status, this.props.item, 'status' ) } className={statusClass}>{label}</button>
        )
    }
}

class Field extends Component {
    constructor() {
        super( ...arguments );
        this.highLightClass = this.props.highLightedField===this.props.field.id ? 'rsssl-highlight' : '';
    }

    componentDidMount() {
        this.props.highLightField('');
        this.onChangeHandlerDataTable = this.onChangeHandlerDataTable.bind(this);
    }

    onChangeHandler(fieldValue) {
         let fields = this.props.fields;
        let field = this.props.field;
        fields[this.props.index]['value'] = fieldValue;
        this.props.saveChangedFields( field.id )
        this.setState( { fields } )
    }

    /**
     * Handle data update for a datatable
     * @param enabled
     * @param clickedItem
     * @param type
     */
    onChangeHandlerDataTable(enabled, clickedItem, type ) {
        let field=this.props.field;
        if (typeof field.value === 'object') {
            field.value = Object.values(field.value);
        }
        //find this item in the field list
        for (const item of field.value){
            if (item.id === clickedItem.id) {
                item[type] = enabled;
            }
            delete item.valueControl;
            delete item.statusControl;
        }
        //the updateItemId allows us to update one specific item in a field set.
        field.updateItemId = clickedItem.id;
        let saveFields = [];
        saveFields.push(field);
        this.props.updateField(field);
        rsssl_api.setFields(saveFields).then(( response ) => {
            //this.props.showSavedSettingsNotice();
        });
    }
    onCloseTaskHandler(){

    }

    render(){
        let field = this.props.field;
        let fieldValue = field.value;
        let fields = this.props.fields;
        let options = [];
        if ( field.options ) {
            for (var key in field.options) {
                if (field.options.hasOwnProperty(key)) {
                    let item = {};
                    item.label = field.options[key];
                    item.value = key;
                    options.push(item);
                }
            }
        }
        if ( !field.visible ) {
            return (
                <span></span>
            );
        }

        if ( field.type==='checkbox' ){
            return (
                <PanelRow className={ this.highLightClass}>
                    <ToggleControl
                        disabled = {field.disabled}
                        checked= { field.value==1 }
                        help={ field.comment }
                        label={ field.label }
                        onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                    />
                </PanelRow>
            );
        }
        if ( field.type==='radio' ){
            return (
                <PanelRow className={ this.highLightClass}>
                    <RadioControl
                        label={ field.label }
                        onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                        selected={ fieldValue }
                        options={ options }
                    />
                </PanelRow>			);
        }
        if ( field.type==='text' ){
            return (
                <PanelBody className={ this.highLightClass}>
                    <TextControl
                        help={ field.comment }
                        label={ field.label }
                        onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                        value= { fieldValue }
                    />
                </PanelBody>
            );
        }

        if ( field.type==='license' ){
            /**
             * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
             */
            let field = this.props.field;
            let fieldValue = field.value;
            let fields = this.props.fields;
            return (
                <License setPageProps={this.props.setPageProps} fieldsUpdateComplete = {this.props.fieldsUpdateComplete} index={this.props.index} fields={fields} field={field} fieldValue={fieldValue} saveChangedFields={this.props.saveChangedFields} highLightField={this.props.highLightField} highLightedField={this.props.highLightedField}/>
            );
        }
        if ( field.type==='number' ){
            return (
                <PanelBody className={ this.highLightClass}>
                    <NumberControl
                        onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                        help={ field.comment }
                        label={ field.label }
                        value= { fieldValue }
                    />
                </PanelBody>
            );
        }
        if ( field.type==='email' ){
            return (
                <PanelBody className={ this.highLightClass}>
                    <TextControl
                        help={ field.comment }
                        label={ field.label }
                        onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                        value= { fieldValue }
                    />
                </PanelBody>
            );
        }

        if ( field.type==='select') {
            return (
                <PanelBody className={ this.highLightClass}>
                    <SelectControl
                        // multiple
                        help={ field.comment }
                        label={ field.label }
                        onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                        value= { fieldValue }
                        options={ options }
                    />
                </PanelBody>
            )
        }

        if ( field.type==='permissionspolicy' ) {
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
                console.log(item);
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
                item.statusControl = <ChangeStatus item={item} onChangeHandlerDataTable={this.onChangeHandlerDataTable}
                />;
            }

            return (
                <PanelBody className={ this.highLightClass}>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                    />
                </PanelBody>
            )
        }

        if ( field.type==='contentsecuritypolicy' ) {
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
                item.statusControl = <ChangeStatus item={item} onChangeHandlerDataTable={this.onChangeHandlerDataTable}
                />;
            }
            return (
                <PanelBody className={ this.highLightClass}>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                        noDataComponent={__("No results", "really-simple-ssl")} //or your component
                    />
                </PanelBody>
            )
        }

        if ( field.type === 'mixedcontentscan' ) {
            return (
               <MixedContentScan dropItemFromModal={this.props.dropItemFromModal} handleModal={this.props.handleModal} field={this.props.field} fields={this.props.selectedFields}/>
            )
        }

        return (
            'not found field type '+field.type
        );
    }
}

export default Field;