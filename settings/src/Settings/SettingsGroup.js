import {Component} from "@wordpress/element";
import Field from "./Field";
import Hyperlink from "../utils/Hyperlink";
import { __ } from '@wordpress/i18n';

/**
 * Render a grouped block of settings
 */
class SettingsGroup extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            fields:this.props.fields,
            isAPILoaded: this.props.isAPILoaded,
        };
        this.upgrade='https://really-simple-ssl.com/pro';
        this.fields = this.props.fields;
    }

    componentDidMount() {
        this.getLicenseStatus = this.getLicenseStatus.bind(this);
    }

    getLicenseStatus(){
        if (this.props.pageProps.hasOwnProperty('licenseStatus') ){
            return this.props.pageProps['licenseStatus'];
        }
        return 'invalid';
    }

    handleMenuLink(id){
        this.props.selectMenu(id);
    }

    render(){
        let selectedMenuItem = this.props.selectedMenuItem;
        let selectedFields = [];
        //get all fields with group_id this.props.group_id
        for (const selectedField of this.props.fields){
            if (selectedField.group_id === this.props.group ){
                selectedFields.push(selectedField);
            }
        }
        //set group default to current menu item
        let activeGroup = selectedMenuItem;
        if ( selectedMenuItem.hasOwnProperty('groups') ) {
            let currentGroup = selectedMenuItem.groups.filter(group => group.id === this.props.group);
            if (currentGroup.length>0) {
                activeGroup = currentGroup[0];
            }
        }

        let status = 'invalid';
        let msg = activeGroup.premium_text ? activeGroup.premium_text : __("Learn more about %sPremium%s", "really-simple-ssl");
        if ( rsssl_settings.pro_plugin_active ) {
            status = this.getLicenseStatus();
            if ( status === 'empty' || status === 'deactivated' ) {
                msg = rsssl_settings.messageInactive;
            } else {
                msg = rsssl_settings.messageInvalid;
            }
        }


        let disabled = status !=='valid' && activeGroup.premium;

        this.upgrade = activeGroup.upgrade ? activeGroup.upgrade : this.upgrade;
        return (
            <div className="rsssl-grid-item">
                {activeGroup && activeGroup.title && <div className="rsssl-grid-item-header">
                    <h3 className="rsssl-h4">{activeGroup.title}</h3>
                    {activeGroup && activeGroup.helpLink && <div className="rsssl-grid-item-controls"><Hyperlink target="_blank" className="rsssl-helplink" text={__("Instructions manual","really-simple-ssl")} url={activeGroup.helpLink}/></div>}
                </div>}
                <div className="rsssl-grid-item-content">
                    {activeGroup && activeGroup.intro && <div className="rsssl-settings-block-intro">{activeGroup.intro}</div>}
                    {selectedFields.map((field, i) => <Field dropItemFromModal={this.props.dropItemFromModal} handleModal={this.props.handleModal} showSavedSettingsNotice={this.props.showSavedSettingsNotice} updateField={this.props.updateField} setPageProps={this.props.setPageProps} fieldsUpdateComplete = {this.props.fieldsUpdateComplete} key={i} index={i} highLightField={this.props.highLightField} highLightedField={this.props.highLightedField} saveChangedFields={this.props.saveChangedFields} field={field} fields={selectedFields}/>)}
                    {disabled && <div className="rsssl-locked">
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-progress-status rsssl-premium">{__("Premium","really-simple-ssl")}</span>
                            { rsssl_settings.pro_plugin_active && <span>{msg}<a className="rsssl-locked-link" href="#" onClick={ () => this.handleMenuLink('license') }>{__("Check license", "really-simple-ssl")}</a></span>}
                            { !rsssl_settings.pro_plugin_active && <Hyperlink target="_blank" text={msg} url={this.upgrade}/> }
                        </div>
                    </div>}
                </div>
            </div>
        )
    }
}

export default SettingsGroup