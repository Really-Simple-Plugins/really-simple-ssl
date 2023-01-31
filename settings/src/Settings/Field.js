import {useEffect} from "@wordpress/element";
import {
    TextControl,
    RadioControl,
    SelectControl,
    TextareaControl,
    __experimentalNumberControl as NumberControl
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
import CheckboxControl from "./CheckboxControl";
import Support from "./Support";
import LearningMode from "./LearningMode";
import Button from "./Button";
import Icon from "../utils/Icon";
const Field = (props) => {
    let scrollAnchor = React.createRef();
    useEffect( () => {
        if ( props.highLightedField===props.field.id && scrollAnchor.current ) {
            scrollAnchor.current.scrollIntoView()
        }
    });

    const onChangeHandler = (fieldValue) => {
        let fields = props.fields;
        let field = props.field;
        fields[props.index]['value'] = fieldValue;

        //we can configure other fields if a field is enabled, or set to a certain value.
        let configureFieldCondition = false;
        if (field.configure_on_activation) {
            if ( field.configure_on_activation.hasOwnProperty('condition') && props.field.value==field.configure_on_activation.condition ) {
                configureFieldCondition = true;
            }
            let configureField = field.configure_on_activation[0];
            for (let fieldId in configureField ) {
                if ( configureFieldCondition && configureField.hasOwnProperty(fieldId) ) {
                    props.updateField(fieldId, configureField[fieldId] );
                }
            }
        }
        props.saveChangedFields( field.id )
    }

    /*
     * Handle data update for a datatable, for the status only (true/false)
     * @param enabled
     * @param clickedItem
     * @param type
     */
    const onChangeHandlerDataTableStatus = (enabled, clickedItem, type ) => {
        let field=props.field;
        enabled = enabled==1 ? 0 : 1;
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
        props.updateField(field.id, field.value);
        rsssl_api.setFields(saveFields).then(( response ) => {
            //props.showSavedSettingsNotice();
        });
    }

    const labelWrap = (field) => {
        let tooltipColor = field.warning ? 'red': 'black';
        return (
            <>
                <div className="cmplz-label-text">{field.label}</div>
                {field.tooltip && <Icon name = "info-open" tooltip={field.tooltip} color = {tooltipColor} />}
            </>
        )
    }

    let field = props.field;
    let fieldValue = field.value;
    let fields = props.fields;
    let disabled = field.disabled;
    let highLightClass = props.highLightedField===props.field.id ? 'rsssl-field-wrap rsssl-highlight' : 'rsssl-field-wrap';

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
            <div className={highLightClass} ref={scrollAnchor}>
                <CheckboxControl
                  label={labelWrap(field)}
                  field={field}
                  onChangeHandler={ ( fieldValue ) => onChangeHandler(fieldValue) }
                />

                {field.comment && <div className="rsssl-comment" dangerouslySetInnerHTML={{__html:field.comment}}></div>}
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
            <div className={highLightClass} ref={scrollAnchor}>
              <RadioControl
                  label={labelWrap(field)}
                  onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
                  selected={ fieldValue }
                  options={ options }
              />
            </div>
        );
    }

    if ( field.type==='text' || field.type==='email' ){
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <TextControl
                  required={ field.required }
                  disabled={ disabled }
                  help={ field.comment }
                  label={labelWrap(field)}
                  onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
                  value= { fieldValue }
              />
            </div>
        );
    }

    if ( field.type==='button' ){
        return (
            <div className={'rsssl-field-button ' + highLightClass}>
                <Button addNotice={props.addNotice} field={field} fields={props.fields} />
            </div>
        );
    }

    if ( field.type==='password' ){
        return (
            <div className={ highLightClass}>
                <Password
                    index={ props.index }
                    field={ field }
                    fields={ props.fields }
                    saveChangedFields={props.saveChangedFields}
                />
            </div>
        );
    }

    if ( field.type==='textarea' ){
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <TextareaControl
                  label={ field.label }
                  help={ field.comment }
                  value= { fieldValue }
                  onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
              />
            </div>
        );
    }

    if ( field.type==='license' ){
        let field = props.field;
        let fieldValue = field.value;
        let fields = props.fields;
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <License setPageProps={props.setPageProps} fieldsUpdateComplete = {props.fieldsUpdateComplete} index={props.index} fields={fields} field={field} fieldValue={fieldValue} saveChangedFields={props.saveChangedFields} highLightField={props.highLightField} highLightedField={props.highLightedField}/>
            </div>

        );
    }
    if ( field.type==='number' ){
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <NumberControl
                    onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
                    help={ field.comment }
                    label={ field.label }
                    value= { fieldValue }
                />
            </div>
        );
    }

    if ( field.type==='host') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <Host
                   index={props.index}
                   saveChangedFields={props.saveChangedFields}
                   handleNextButtonDisabled={props.handleNextButtonDisabled}
                   updateFields={props.updateFields}
                  fields={props.fields}
                  field={props.field}
              />
            </div>
        )
    }

    if ( field.type==='select') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <SelectControl
                  disabled={ disabled }
                  help={ field.comment }
                  label={labelWrap(field)}
                  onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
                  value= { fieldValue }
                  options={ options }
              />
            </div>
        )
    }

    if ( field.type==='support' ) {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <Support/>
            </div>
        )
    }
    if ( field.type==='permissionspolicy' ) {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <PermissionsPolicy disabled={disabled} updateField={props.updateField} field={props.field} options={options} highLightClass={highLightClass} fields={fields}/>
            </div>
        )
    }

    if ( field.type==='learningmode' ) {
        return(
            <div className={highLightClass} ref={scrollAnchor}>
              <LearningMode disabled={disabled} onChangeHandlerDataTableStatus={onChangeHandlerDataTableStatus} updateField={props.updateField} field={props.field} options={options} highLightClass={highLightClass} fields={fields}/>
            </div>
        )
    }

    if ( field.type === 'mixedcontentscan' ) {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <MixedContentScan dropItemFromModal={props.dropItemFromModal} handleModal={props.handleModal} field={props.field} fields={props.selectedFields}/>
            </div>
        )
    }

    if ( field.type === 'letsencrypt' ) {
            return (
               <LetsEncrypt key={field.id} resetRefreshTests={props.resetRefreshTests} refreshTests={props.refreshTests} getFieldValue={props.getFieldValue} save={props.save} selectMenu={props.selectMenu} addHelp={props.addHelp} updateField={props.updateField} fields={props.fields} field={field} handleNextButtonDisabled={props.handleNextButtonDisabled}/>
            )
    }

    if ( field.type === 'activate' ) {
            return (
               <Activate key={field.id} selectMainMenu={props.selectMainMenu} resetRefreshTests={props.resetRefreshTests} refreshTests={props.refreshTests} getFieldValue={props.getFieldValue} save={props.save} selectMenu={props.selectMenu} addHelp={props.addHelp} updateField={props.updateField} fields={props.fields} field={field} handleNextButtonDisabled={props.handleNextButtonDisabled}/>
            )
    }

    return (
        'not found field type '+field.type
    );
}

export default Field;