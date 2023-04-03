import {
    TextControl,
    RadioControl,
    SelectControl,
    TextareaControl,
    __experimentalNumberControl as NumberControl
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import License from "./License/License";
import Password from "./Password";
import Host from "./Host";
import Hyperlink from "../utils/Hyperlink";
import LetsEncrypt from "../LetsEncrypt/LetsEncrypt";
import Activate from "../LetsEncrypt/Activate";
import MixedContentScan from "./MixedContentScan/MixedContentScan";
import PermissionsPolicy from "./PermissionsPolicy";
import CheckboxControl from "./CheckboxControl";
import Support from "./Support";
import LearningMode from "./LearningMode/LearningMode";
import Button from "./Button";
import Icon from "../utils/Icon";
import { useEffect} from "@wordpress/element";
import useFields from "./FieldsData";
import PostDropdown from "./PostDropDown";

const Field = (props) => {
    let scrollAnchor = React.createRef();
    const {updateField, setChangedField, highLightField} = useFields();

    useEffect( () => {
        if ( highLightField===props.field.id && scrollAnchor.current ) {
            scrollAnchor.current.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    },[]);

    const onChangeHandler = (fieldValue) => {
        let field = props.field;
        updateField(field.id, fieldValue);

        //we can configure other fields if a field is enabled, or set to a certain value.
        let configureFieldCondition = false;
        if ( field.configure_on_activation ) {
            if ( field.configure_on_activation.hasOwnProperty('condition') && props.field.value==field.configure_on_activation.condition ) {
                configureFieldCondition = true;
            }
            let configureField = field.configure_on_activation[0];
            for (let fieldId in configureField ) {
                if ( configureFieldCondition && configureField.hasOwnProperty(fieldId) ) {
                    updateField(fieldId, configureField[fieldId] );
                }
            }
        }
        setChangedField( field.id, fieldValue );
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
    let disabled = field.disabled;
    let highLightClass = 'rsssl-field-wrap';
    if ( highLightField===props.field.id ) {
        highLightClass = 'rsssl-field-wrap rsssl-highlight';
    }

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
        field.comment = <>
            {__("This feature is only available networkwide.","really-simple-ssl")}
            <Hyperlink target="_blank" text={__("Network settings","really-simple-ssl")} url={rsssl_settings.network_link}/>
        </>
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
                  disabled={disabled}
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
                  placeholder={ field.placeholder }
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
            <div className={'rsssl-field-button ' + highLightClass} ref={scrollAnchor}>
                <label>{field.label}</label>
                <Button field={field}/>
            </div>
        );
    }

    if ( field.type==='password' ){
        return (
            <div className={ highLightClass} ref={scrollAnchor}>
                <Password
                    index={ props.index }
                    field={ field }
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
                  disabled={ field.disabled }
              />
            </div>
        );
    }

    if ( field.type==='license' ){
        let field = props.field;
        let fieldValue = field.value;
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <License index={props.index} field={field} fieldValue={fieldValue}/>
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
    if ( field.type==='email' ){
        return (
            <div className={this.highLightClass} ref={this.scrollAnchor}>
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
            <div className={highLightClass} ref={scrollAnchor}>
              <Host
                  index={props.index}
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

    if ( field.type==='postdropdown' ) {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <PostDropdown field={props.field}/>
            </div>
        )
    }
    if ( field.type==='permissionspolicy' ) {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <PermissionsPolicy disabled={disabled} field={props.field} options={options}/>
            </div>
        )
    }

    if ( field.type==='learningmode' ) {
        return(
            <div className={highLightClass} ref={scrollAnchor}>
              <LearningMode disabled={disabled} field={props.field}/>
            </div>
        )
    }

    if ( field.type === 'mixedcontentscan' ) {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
              <MixedContentScan field={props.field}/>
            </div>
        )
    }

    if ( field.type === 'letsencrypt' ) {
            return (
               <LetsEncrypt key={field.id} field={field} />
            )
    }

    if ( field.type === 'activate' ) {
            return (
               <Activate key={field.id} field={field}/>
            )
    }

    return (
        'not found field type '+field.type
    );
}

export default Field;