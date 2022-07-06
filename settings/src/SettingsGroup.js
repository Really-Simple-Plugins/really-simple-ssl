import {Component} from "@wordpress/element";
import Field from "./Field";

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
        this.fields = this.props.fields;
    }

    getLicenseStatus(){
        if (this.props.pageProps.hasOwnProperty('licenseStatus') ){
            return this.props.pageProps['licenseStatus'];
        }
        return 'invalid';
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
        let status = this.getLicenseStatus();
        let disabled = status !=='valid' && selectedMenuItem.is_premium;
        let msg;
        if ( status === 'empty' || status === 'deactivated' ) {
            msg = rsssl_settings.messageInactive;
        } else {
            msg = rsssl_settings.messageInvalid;
        }
        //get current group, if the id exists
        let activeGroup = selectedMenuItem;

        if (selectedMenuItem.hasOwnProperty('groups')) {
            let currentGroup = selectedMenuItem.groups.filter(group => group.id === this.props.group);
            if (currentGroup.length>0) {
                activeGroup = currentGroup[0];
            }

        }
        return (

            <div className="rsssl-grid-item">
                {activeGroup && activeGroup.title && <div className="rsssl-grid-item-header"><h3 className="rsssl-h4">{activeGroup.title}</h3></div>}
                <div className="rsssl-grid-item-content">
                    {activeGroup && activeGroup.intro && <div className="rsssl-settings-block-intro">{activeGroup.intro}</div>}
                    {selectedFields.map((field, i) => <Field dropItemFromModal={this.props.dropItemFromModal} handleModal={this.props.handleModal} showSavedSettingsNotice={this.props.showSavedSettingsNotice} updateField={this.props.updateField} setPageProps={this.props.setPageProps} fieldsUpdateComplete = {this.props.fieldsUpdateComplete} key={i} index={i} highLightField={this.props.highLightField} highLightedField={this.props.highLightedField} saveChangedFields={this.props.saveChangedFields} field={field} fields={selectedFields}/>)}
                    {disabled && <div className="rsssl-locked">
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-progress-status rsssl-warning">{__("Warning","really-simple-ssl")}</span>
                            {msg}&nbsp;<a href={rsssl_settings.url}>{__("Check license", "really-simple-ssl")}</a>
                        </div>
                    </div>}
                </div>
            </div>
            // <div className="rsssl-grouped-fields">
            // 	{activeGroup && activeGroup.title && <PanelBody><h1 className="rsssl-settings-block-title">{activeGroup.title}</h1></PanelBody>}
            // 	{activeGroup && activeGroup.intro && <PanelBody><div className="rsssl-settings-block-intro">{activeGroup.intro}</div></PanelBody>}
            // 	{selectedFields.map((field, i) => <Field updateField={this.props.updateField} setPageProps={this.props.setPageProps} fieldsUpdateComplete = {this.props.fieldsUpdateComplete} key={i} index={i} highLightField={this.props.highLightField} highLightedField={this.props.highLightedField} saveChangedFields={this.props.saveChangedFields} field={field} fields={selectedFields}/>)}
            // 	{disabled && <div className="rsssl-locked">
            // 		<div className="rsssl-locked-overlay">
            // 			<span className="rsssl-progress-status rsssl-warning">{__("Warning","really-simple-ssl")}</span>
            // 			{msg}&nbsp;<a href={rsssl_settings.url}>{__("Check license", "really-simple-ssl")}</a>
            // 		</div>
            // 	</div>}
            // </div>
        )
    }
}

export default SettingsGroup