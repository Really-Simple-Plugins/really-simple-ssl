import {
    TextControl,
    RadioControl,
    TextareaControl,
    __experimentalNumberControl as NumberControl
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import License from "./License/License";
import Password from "./Password";
import SelectControl from "./SelectControl";
import Host from "./Host/Host";
import Hyperlink from "../utils/Hyperlink";
import LetsEncrypt from "../LetsEncrypt/LetsEncrypt";
import Activate from "../LetsEncrypt/Activate";
import MixedContentScan from "./MixedContentScan/MixedContentScan";
import PermissionsPolicy from "./PermissionsPolicy";
import CheckboxControl from "./CheckboxControl";
import Support from "./Support";
import LearningMode from "./LearningMode/LearningMode";
import RiskComponent from "./RiskConfiguration/RiskComponent";
import VulnerabilitiesOverview from "./RiskConfiguration/vulnerabilitiesOverview";
import IpAddressDatatable  from "./LimitLoginAttempts/IpAddressDatatable";
import TwoFaRolesDropDown from "./TwoFA/TwoFaRolesDropDown";
import Button from "./Button";
import Icon from "../utils/Icon";
import { useEffect, useState } from "@wordpress/element";
import useFields from "./FieldsData";
import PostDropdown from "./PostDropDown";
import NotificationTester from "./RiskConfiguration/NotificationTester";
import getAnchor from "../utils/getAnchor";
import useMenu from "../Menu/MenuData";
import UserDatatable from "./LimitLoginAttempts/UserDatatable";
import CountryDatatable from "./LimitLoginAttempts/CountryDatatable";
// import DynamicDataTable from "./DynamicDataTable/DynamicDataTable";
import TwoFaDataTable from "./TwoFA/TwoFaDataTable";
import EventLogDataTable from "./EventLog/EventLogDataTable";
import DOMPurify from "dompurify";
import RolesDropDown from "./RolesDropDown";
import GeoDatatable from "./GeoBlockList/GeoDatatable";
import WhiteListDatatable from "./GeoBlockList/WhiteListDatatable";
import Captcha from "./Captcha/Captcha";
import CaptchaKey from "./Captcha/CaptchaKey";
const Field = (props) => {
    let scrollAnchor = React.createRef();
    const {updateField, setChangedField, highLightField} = useFields();
    const [anchor, setAnchor] = useState(null);
    const {selectedFilter, setSelectedFilter} = useMenu();


    const handleFilterChange = (value) => {
        setSelectedFilter(value); // Update selectedFilter when the filter value changes
    };
    useEffect( () => {
        //check if the url contains the query variable 'anchor'
        setAnchor(getAnchor('anchor'))
        handleAnchor();
        if ( highLightField===props.field.id && scrollAnchor.current ) {
            scrollAnchor.current.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        //if the field is a captcha provider, scroll to the captcha provider is a temp fix cause i can't get the scroll to work properly.
        if (highLightField === 'enabled_captcha_provider' && props.fields) {
            let captchaField = document.getElementsByClassName('rsssl-highlight')[0];
            if (captchaField) {
                captchaField.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

    },[]);

    useEffect( () => {
        handleAnchor();
    },[anchor]);

    window.addEventListener('hashchange', (e) => {
        setAnchor(getAnchor('anchor'));
    });
    const handleAnchor = () => {
        if ( anchor && anchor === props.field.id ) {
            scrollAnchor.current.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    const onChangeHandler = (fieldValue) => {
        let field = props.field;
        if (field.pattern) {
            const regex = new RegExp(field.pattern, 'g');
            const allowedCharactersArray = fieldValue.match(regex);
            fieldValue = allowedCharactersArray ? allowedCharactersArray.join('') : '';
        }
        updateField(field.id, fieldValue);

        // we can configure other fields if a field is enabled, or set to a certain value.
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
            <Hyperlink target="_blank" rel="noopener noreferrer" text={__("Network settings","really-simple-ssl")} url={rsssl_settings.network_link}/>
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

    if ( field.type==='checkbox' ) {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <CheckboxControl
                    label={labelWrap(field)}
                    field={field}
                    disabled={disabled}
                    onChangeHandler={ ( fieldValue ) => onChangeHandler( fieldValue ) }
                />
                { field.comment &&
                    <div className="rsssl-comment" dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(field.comment) }} />
                    /* nosemgrep: react-dangerouslysetinnerhtml */
                }
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

    if (field.type==='email'){
        const sendVerificationEmailField = props.fields.find(field => field.id === 'send_verification_email');
        const emailIsVerified = sendVerificationEmailField && sendVerificationEmailField.disabled;

        return (
            <div className={highLightClass} ref={scrollAnchor} style={{position: 'relative'}}>
                <TextControl
                    required={ field.required }
                    placeholder={ field.placeholder }
                    disabled={ disabled }
                    help={ field.comment }
                    label={labelWrap(field)}
                    onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
                    value= { fieldValue }
                />
                { sendVerificationEmailField &&
                    <div className="rsssl-email-verified" >
                        {emailIsVerified
                            ? <Icon name='circle-check' color={'green'} />
                            : <Icon name='circle-times' color={'red'} />}
                    </div>
                }
            </div>
        );
    }

    if (field.type==='captcha_key') {
        return (
            <div className={highLightClass} ref={scrollAnchor} style={{position: 'relative'}}>
                <CaptchaKey field={field} fields={props.fields} label={labelWrap(field)} />
            </div>
            )
    }

    if (field.type==='text' ) {
        return (
            <div className={highLightClass} ref={scrollAnchor} style={{position: 'relative'}}>
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
                    label={labelWrap(field)}
                    onChangeHandler={ ( fieldValue ) => onChangeHandler(fieldValue) }
                    value= { fieldValue }
                    options={ options }
                    field={field}
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

    if (field.type==='captcha') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <Captcha field={field} label={labelWrap(field)} />
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

    if ( field.type==='riskcomponent' ) {
        return (<div className={highLightClass} ref={scrollAnchor}>
            <RiskComponent field={props.field}/>
        </div>)
    }

    if ( field.type === 'mixedcontentscan' ) {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <MixedContentScan field={props.field}/>
            </div>
        )
    }

    if (field.type === 'vulnerabilitiestable') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <VulnerabilitiesOverview field={props.field} />
            </div>
        )
    }

    if (field.type === 'two_fa_roles') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <label htmlFor="rsssl-two-fa-dropdown-{field.id}">
                    {labelWrap(field)}
                </label>
                <TwoFaRolesDropDown field={props.field}
                />
            </div>
        );
    }

    if (field.type === 'eventlog-datatable') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <EventLogDataTable
                    field={props.field}
                    action={props.field.action}
                />
            </div>
        )
    }
    if (field.type === 'twofa-datatable') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <TwoFaDataTable
                    field={props.field}
                    action={props.field.action}
                />
            </div>
        )
    }
    // if (field.type === 'dynamic-datatable') {
    //     return (
    //         <div className={highLightClass} ref={scrollAnchor}>
    //             <DynamicDataTable
    //                 field={props.field}
    //                 action={props.field.action}
    //             />
    //         </div>
    //     )
    // }

    if (field.type === 'ip-address-datatable') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <IpAddressDatatable
                    field={props.field}
                    action={props.field.action}
                />
            </div>
        )
    }

    if (field.type === 'user-datatable') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <UserDatatable
                    field={props.field}
                    action={props.field.action}
                />
            </div>
        )
    }

    if (field.type === 'country-datatable') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <CountryDatatable
                    field={props.field}
                    action={props.field.action}
                />
            </div>
        )
    }

    if (field.type === 'geo-datatable') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <GeoDatatable
                    field={props.field}
                    action={props.field.action}
                />
            </div>
        )
    }

    if (field.type === 'geo-ip-datatable') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <WhiteListDatatable
                    field={props.field}
                    action={props.field.action}
                />
            </div>
        )
    }

    if (field.type === 'roles_dropdown') {
        return (
            <div className={highLightClass} ref={scrollAnchor}>
                <label htmlFor="rsssl-roles-dropdown-{field.id}">
                    {labelWrap(field)}
                </label>
                <RolesDropDown field={props.field}
                />
            </div>
        );
    }

    if(field.type === 'notificationtester') {
        return (
            <div className={'rsssl-field-button ' + highLightClass} ref={scrollAnchor}>
                <NotificationTester field={props.field} labelWrap={labelWrap}/>
            </div>
        )
    }

    if ( field.type === 'letsencrypt' ) {
        return (
            <LetsEncrypt field={field} />
        )
    }

    if ( field.type === 'activate' ) {
        return (
            <Activate field={field}/>
        )
    }

    return (

        'not found field type '+field.type
    );
}

export default Field;