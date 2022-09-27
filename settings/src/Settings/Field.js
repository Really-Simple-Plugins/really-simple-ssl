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
import Password from "./Password";
import Host from "./Host";
import Hyperlink from "../utils/Hyperlink";
import LetsEncrypt from "../LetsEncrypt/LetsEncrypt";
import Activate from "../LetsEncrypt/Activate";
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
        this.onChangeHandlerDataTable = this.onChangeHandlerDataTable.bind(this);
        this.onChangeHandler = this.onChangeHandler.bind(this);
    }

    onChangeHandler(fieldValue) {
        let fields = this.props.fields;
        let field = this.props.field;
        fields[this.props.index]['value'] = fieldValue;
        this.props.saveChangedFields( field.id )
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
        console.log(this);
        this.props.updateField(field.id, field.value);
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
        if ( !rsssl_settings.networkwide_active && field.networkwide_required ) {
            disabled = true;
            field.comment = <>{__("This feature is only available networkwide.","really-simple-ssl")}<Hyperlink target="_blank" text={__("Network settings","really-simple-ssl")} url={rsssl_settings.network_link}/></>
        }

        if ( field.conditionallyDisabled ) {
            disabled = true;
        }

        if ( !field.visible ) {
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
                      label={ field.label }
                      onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                  />
                  {field.comment && <div dangerouslySetInnerHTML={{__html:field.comment}}></div>}
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

        if ( field.type==='text' || field.type==='email' ){
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
                <div className={'rsssl-field-button ' + this.highLightClass}>
                    <label>{field.label}</label>
                    <Hyperlink className="button button-default" text={field.button_text} url={field.url}/>
                </div>
            );
        }

        if ( field.type==='password' ){
            return (
                <div className={ this.highLightClass}>
                    <Password
                        index={ this.props.index }
                        field={ field }
                        fields={ this.props.fields }
                        saveChangedFields={this.props.saveChangedFields}
                    />
                </div>
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

        if ( field.type==='host') {
            return (
                <div className={this.highLightClass}>
                  <Host
                       index={this.props.index}
                       saveChangedFields={this.props.saveChangedFields}
                       handleNextButtonDisabled={this.props.handleNextButtonDisabled}
                       updateFields={this.props.updateFields}
                      fields={this.props.fields}
                      field={this.props.field}
                  />
                </div>
            )
        }

        if ( field.type==='select') {
            return (
                <div className={this.highLightClass}>
                  <SelectControl
                      disabled={ disabled }
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

        if ( field.type === 'letsencrypt' ) {
                return (
                   <LetsEncrypt key={field.id} resetRefreshTests={this.props.resetRefreshTests} refreshTests={this.props.refreshTests} getFieldValue={this.props.getFieldValue} save={this.props.save} selectMenu={this.props.selectMenu} addHelp={this.props.addHelp} updateField={this.props.updateField} fields={this.props.fields} field={field} handleNextButtonDisabled={this.props.handleNextButtonDisabled}/>
                )
        }

        if ( field.type === 'activate' ) {
                return (
                   <Activate key={field.id} selectMainMenu={this.props.selectMainMenu} resetRefreshTests={this.props.resetRefreshTests} refreshTests={this.props.refreshTests} getFieldValue={this.props.getFieldValue} save={this.props.save} selectMenu={this.props.selectMenu} addHelp={this.props.addHelp} updateField={this.props.updateField} fields={this.props.fields} field={field} handleNextButtonDisabled={this.props.handleNextButtonDisabled}/>
                )
        }

        return (
            'not found field type '+field.type
        );
    }
}

export default Field;