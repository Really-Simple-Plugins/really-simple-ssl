import {
    TextControl,
    RadioControl,
    SelectControl,
    TextareaControl,
    __experimentalNumberControl as NumberControl,
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import License from "./License";
import Hyperlink from "../utils/Hyperlink";
import MixedContentScan from "./MixedContentScan";
import PermissionsPolicy from "./PermissionsPolicy";
import Support from "./Support";
import LearningMode from "./LearningMode";
import ChangeStatus from "./ChangeStatus";
import {
    Component,
} from '@wordpress/element';

/*
 * https://react-data-table-component.netlify.app
 */
import DataTable from "react-data-table-component";


class Field extends Component {
    constructor() {
        super( ...arguments );
        this.highLightClass = this.props.highLightedField===this.props.field.id ? 'rsssl-field-wrap rsssl-highlight' : 'rsssl-field-wrap';
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

    /*
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
            delete item.deleteControl;
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
        let disabled = field.disabled;
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

        //if a feature can only be used on networkwide or single site setups, pass that info here.
        if ( !rsssl_settings.networkwide_active && field.networkwide ) {
            disabled = true;
            field.comment = <>{__("This feature is only available networkwide.","really-simple-ssl")}<Hyperlink target="_blank" text={__("Network settings","really-simple-ssl")} url={rsssl_settings.network_link}/></>
        }

        if ( field.conditionallyDisabled ) {
            disabled = true;
        }

        if ( !field.visible || field.type==='database' ) {
            return (
                <></>
            );
        }

        if ( field.type==='checkbox' ){
            return (
                <div className={this.highLightClass}>
                  <ToggleControl
                      disabled = {disabled}
                      checked= { field.value==1 }
                      help={ field.comment }
                      label={ field.label }
                      onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                  />
                </div>
            );
        }

        if ( field.type==='hidden' ){
            return (
                <input type="hidden" value={field.value}/>
            );
        }

        if ( field.type==='radio' ){
            return (
                <div className={this.highLightClass}>
                  <RadioControl
                      label={ field.label }
                      onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                      selected={ fieldValue }
                      options={ options }
                  />
                </div>
            );
        }
        if ( field.type==='text' ){
            return (
                <div className={this.highLightClass}>
                  <TextControl
                      help={ field.comment }
                      label={ field.label }
                      onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                      value= { fieldValue }
                  />
                </div>
            );
        }

        if ( field.type==='button' ){
            return (
                <PanelBody className={ this.highLightClass}>
                    <div className="components-base-control ">
                        <div class="components-base-control__field">
                            <Hyperlink className="button button-default" text={field.button_text} url={field.url}/>
                            <label>{field.label}</label>
                        </div>
                    </div>
                </PanelBody>
            );
        }

        if ( field.type==='textarea' ){
            return (
                <div className={this.highLightClass}>
                  <TextareaControl
                      label={ field.label }
                      help={ field.comment }
                      value= { fieldValue }
                      onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                  />
                </div>
            );
        }

        if ( field.type==='license' ){
            /*
             * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
             */
            let field = this.props.field;
            let fieldValue = field.value;
            let fields = this.props.fields;
            return (
                <div className={this.highLightClass}>
                  <License setPageProps={this.props.setPageProps} fieldsUpdateComplete = {this.props.fieldsUpdateComplete} index={this.props.index} fields={fields} field={field} fieldValue={fieldValue} saveChangedFields={this.props.saveChangedFields} highLightField={this.props.highLightField} highLightedField={this.props.highLightedField}/>
                </div>
            );
        }
        if ( field.type==='number' ){
            return (
                <div className={this.highLightClass}>
                    <NumberControl
                        onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                        help={ field.comment }
                        label={ field.label }
                        value= { fieldValue }
                    />
                </div>
            );
        }
        if ( field.type==='email' ){
            return (
                <div className={this.highLightClass}>
                  <TextControl
                      help={ field.comment }
                      label={ field.label }
                      onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                      value= { fieldValue }
                  />
                </div>
            );
        }

        if ( field.type==='select') {
            return (
                <div className={this.highLightClass}>
                  <SelectControl
                      // multiple
                      help={ field.comment }
                      label={ field.label }
                      onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                      value= { fieldValue }
                      options={ options }
                  />
                </div>
            )
        }

        if ( field.type==='support' ) {
            return (
                <div className={this.highLightClass}>
                  <Support/>
                </div>
            )
        }

        if ( field.type==='permissionspolicy' ) {
            return (
                <div className={this.highLightClass}>
                  <PermissionsPolicy onChangeHandlerDataTable={this.onChangeHandlerDataTable} updateField={this.props.updateField} field={this.props.field} options={options} highLightClass={this.highLightClass} fields={fields}/>
                </div>
            )
        }

        if ( field.type==='learningmode' ) {
            return(
                <div className={this.highLightClass}>
                  <LearningMode onChangeHandlerDataTable={this.onChangeHandlerDataTable} updateField={this.props.updateField} field={this.props.field} options={options} highLightClass={this.highLightClass} fields={fields}/>
                </div>
            )
        }

        if ( field.type === 'mixedcontentscan' ) {
            return (
                <div className={this.highLightClass}>
                  <MixedContentScan dropItemFromModal={this.props.dropItemFromModal} handleModal={this.props.handleModal} field={this.props.field} fields={this.props.selectedFields}/>
                </div>
            )
        }

        return (
            'not found field type '+field.type
        );
    }
}

export default Field;