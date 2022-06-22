import {Component, Fragment} from "@wordpress/element";
import Placeholder from "./Placeholder";
import in_array from "./utils/lib";
import SettingsGroup from "./SettingsGroup";
import Help from "./Help";
import {
    Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Renders the selected settings
 *
 */
class Settings extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            fields:this.props.fields,
            progress:this.props.progress,
            isAPILoaded: this.props.isAPILoaded,
        };
        this.fields = this.props.fields;
    }

    render() {
        const {
            fields,
            progress,
            isAPILoaded,
        } = this.state;
        let selectedMenuItem = this.props.selectedMenuItem;
        let selectedStep = this.props.selectedStep;
        let menu = this.props.menu;
        if ( ! isAPILoaded ) {
            return (
                <Placeholder></Placeholder>
            );
        }
        let selectedFields = fields.filter(field => field.menu_id === selectedMenuItem);
        let groups = [];
        for (const selectedField of selectedFields){
            if ( !in_array(selectedField.group_id, groups) ){
                groups.push(selectedField.group_id);
            }
        }

        //convert progress notices to an array useful for the help blocks
        let notices = [];
        for (const notice of progress.notices){
            if ( notice.menu_id === selectedMenuItem ) {
                let help = {};
                help.title = notice.output.title ? notice.output.title : false;
                help.label = notice.output.label;
                help.id = notice.field_id;
                help.text = notice.output.msg;
                notices.push(help);
            }
        }
        for (const notice of selectedFields.filter(field => field.help)){
            let help = notice.help;
            help.id = notice.id;
            notices.push(notice.help);
        }

        let selectedMenuItemObject;
        for (const item of menu.menu_items){
            if (item.id === selectedMenuItem ) {
                selectedMenuItemObject = item;
            } else if (item.menu_items) {
                selectedMenuItemObject = item.menu_items.filter(menuItem => menuItem.id === selectedMenuItem)[0];
            }
            if ( selectedMenuItemObject ) {
                break;
            }
        }
        return (
            <Fragment>
                <div className="rsssl-wizard-settings rsssl-column-2">
                    {groups.map((group, i) => <SettingsGroup showSavedSettingsNotice={this.props.showSavedSettingsNotice}  updateField={this.props.updateField} pageProps={this.props.pageProps} setPageProps={this.props.setPageProps} fieldsUpdateComplete = {this.props.fieldsUpdateComplete} key={i} index={i} highLightField={this.props.highLightField} highLightedField={this.props.highLightedField} selectedMenuItem={selectedMenuItemObject} saveChangedFields={this.props.saveChangedFields} group={group} fields={selectedFields}/>)}
                    <div className="rsssl-grid-item-footer">
                        <Button
                            isPrimary
                            onClick={ this.props.save }>
                            { __( 'Save', 'really-simple-ssl' ) }
                        </Button>
                    </div>
                </div>
                <div className="rsssl-wizard-help">
                    {notices.map((field, i) => <Help key={i} index={i} help={field} fieldId={field.id}/>)}
                </div>
            </Fragment>
        )
    }
}
export default Settings