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

        let activeGroup;
        for (const item of this.props.menu.menu_items){
            if (item.id === selectedMenuItem ) {
                activeGroup = item;
            } else if (item.menu_items) {
                activeGroup = item.menu_items.filter(menuItem => menuItem.id === selectedMenuItem)[0];
            }
            if ( activeGroup ) {
                break;
            }
        }

        if ( selectedMenuItem && selectedMenuItem.hasOwnProperty('groups') ) {
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

        //if a feature can only be used on networkwide or single site setups, pass that info here.
        let networkwide_error = !rsssl_settings.networkwide_active && activeGroup.networkwide;

        this.upgrade = activeGroup.upgrade ? activeGroup.upgrade : this.upgrade;
        let helplinkText = activeGroup.helpLink_text ? activeGroup.helpLink_text : __("Instructions manual","really-simple-ssl");

        return (
            <div className={"rsssl-grid-item rsssl-"+activeGroup.id}>
                {activeGroup && activeGroup.title && <div className="rsssl-grid-item-header">
                    <h3 className="rsssl-h4">{activeGroup.title}</h3>
                    {activeGroup && activeGroup.helpLink && <div className="rsssl-grid-item-controls"><Hyperlink target="_blank" className="rsssl-helplink" text={helplinkText} url={activeGroup.helpLink}/></div>}
                </div>}
                <div className="rsssl-grid-item-content">
                    {activeGroup && activeGroup.intro && <div className="rsssl-settings-block-intro">{activeGroup.intro}</div>}
                    {selectedFields.map((field, i) =>
                        <Field key={i} index={i}
                            selectMenu={this.props.selectMenu}
                            dropItemFromModal={this.props.dropItemFromModal}
                            handleNextButtonDisabled={this.props.handleNextButtonDisabled}
                            handleModal={this.props.handleModal}
                            showSavedSettingsNotice={this.props.showSavedSettingsNotice}
                            updateField={this.props.updateField}
                            addHelp={this.props.addHelp}
                            setPageProps={this.props.setPageProps}
                            fieldsUpdateComplete = {this.props.fieldsUpdateComplete}
                            highLightField={this.props.highLightField}
                            highLightedField={this.props.highLightedField}
                            saveChangedFields={this.props.saveChangedFields}
                            field={field}
                            fields={selectedFields}
                            />)}
                    {disabled && !networkwide_error && <div className="rsssl-locked">
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-progress-status rsssl-premium">{__("Premium","really-simple-ssl")}</span>
                            { rsssl_settings.pro_plugin_active && <span>{msg}<a className="rsssl-locked-link" href="#" onClick={ () => this.handleMenuLink('license') }>{__("Check license", "really-simple-ssl")}</a></span>}
                            { !rsssl_settings.pro_plugin_active && <Hyperlink target="_blank" text={msg} url={this.upgrade}/> }
                        </div>
                    </div>}
                    {networkwide_error && <div className="rsssl-locked">
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-progress-status rsssl-warning">{__("Network feature","really-simple-ssl")}</span>
                            <span>{__("This feature is only available networkwide.","really-simple-ssl")}<Hyperlink target="_blank" text={__("Network settings","really-simple-ssl")} url={rsssl_settings.network_link}/></span>
                        </div>
                    </div>}
                </div>
            </div>
        )
    }
}

export default SettingsGroup