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
import TaskElement from "./TaskElement";
import { __ } from '@wordpress/i18n';

import License from "./License";
import {
    Component,
} from '@wordpress/element';

/**
 * https://react-data-table-component.netlify.app
 */
import DataTable from "react-data-table-component";
import * as rsssl_api from "./utils/api";
import in_array from "./utils/lib";

class ChangeStatus extends Component {
    constructor() {
        super( ...arguments );
    }
    render(){
        let statusClass = this.props.status==1 ? 'rsssl-status-allowed' : 'rsssl-status-revoked';
        let label = this.props.status==1 ? __("Revoke", "really-simple-ssl") : __("Allow", "really-simple-ssl");
        return (
            <button className={statusClass}>{label}</button>
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
    }

    onChangeHandler(fieldValue) {
        console.log("default changehandler");
         let fields = this.props.fields;
        let field = this.props.field;
        fields[this.props.index]['value'] = fieldValue;
        this.props.saveChangedFields( field.id )
        this.setState( { fields } )
    }

    onChangeHandlerDataTable(enabled, clickedItem, type) {

        // let field = this.props.field;
        let field = this.props.field;
        console.log(field);
        //find this item in the field list
        for (const item of field.value){
            if (item.id === clickedItem.id) {
                item[type] = enabled;
            }
            delete item.owndomainControl;
            delete item.statusControl;
        }

        console.log("current datatable value ");
        console.log(this.props.fields);
        console.log(this.props.field);

        let saveFields = [];
        saveFields.push(field);
        console.log("save fields");
        console.log(saveFields);
//        this.setState( { fields } );
        rsssl_api.setFields(saveFields).then(( response ) => {
            //this.changedFields = [];
        });
    }
    onCloseTaskHandler(){

    }
    render(){
        let field = this.props.field;
        let fieldValue = field.value;
        let fields = this.props.fields;
        let options = [];
        if ( field.type==='radio' || field.type==='select' ) {
            for (var key in field.options) {
                if (field.options.hasOwnProperty(key)) {
                    let item = new Object;
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
            console.log(this.props.field);
            //build our header
            columns = [];
            field.columns.forEach(function(item, i) {
                let newItem = {
                    name: item.name,
                    sortable: item.sortable,
                    selector: row => row[item.column],
                }
                columns.push(newItem);
            });

            let data = field.value;

            for (const item of data){
                item.owndomainControl = <ToggleControl
                                 checked= {item.owndomain==1}
                                 label=''
                                 onChange={ ( fieldValue ) => this.onChangeHandlerDataTable( fieldValue, item, 'owndomain' ) }
                             />
                item.statusControl = <ChangeStatus status={item.status} />;
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

        return (
            'not found field type '+field.type
        );
    }
}

export default Field;