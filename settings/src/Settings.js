import {Component, Fragment} from "@wordpress/element";
import Placeholder from "./Placeholder";
import {in_array} from "./utils/lib";
import SettingsGroup from "./SettingsGroup";
import Help from "./Help";
import {
    Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "./utils/api";

/**
 * Renders the selected settings
 *
 */
class Settings extends Component {
    constructor() {
        super( ...arguments );
    }

    render() {
        let isAPILoaded = this.props.isAPILoaded;
        let progress = this.props.progress;
        let selectedMenuItem = this.props.selectedMenuItem;
        let fields = this.props.fields;
        let selectedStep = this.props.selectedStep;
        let menu = this.props.menu;
        const { menu_items: menuItems } = menu;

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
            let noticeField = false;
            if ( notice.show_with_options ) {
                noticeField = selectedFields.filter(field => notice.show_with_options && notice.show_with_options.includes(field.id) );
            }

            if ( noticeField || notice.menu_id === selectedMenuItem ) {
                let help = {};
                help.title = notice.output.title ? notice.output.title : false;
                help.label = notice.output.label;
                help.id = notice.field_id;
                help.text = notice.output.msg;
                help.linked_field = notice.show_with_option;
                notices.push(help);
            }
        }

        for (const notice of selectedFields.filter(field => field.help)){
            let help = notice.help;
            help.id = notice.id;
            notices.push(notice.help);
        }
        notices = notices.filter(notice => notice.label.toLowerCase()!=='completed');

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
                    { groups.map((group, i) =>
                        <SettingsGroup
                            dropItemFromModal={this.props.dropItemFromModal}
                            selectMenu={this.props.selectMenu}
                            handleModal={this.props.handleModal}
                            showSavedSettingsNotice={this.props.showSavedSettingsNotice}
                            updateField={this.props.updateField}
                            pageProps={this.props.pageProps}
                            setPageProps={this.props.setPageProps}
                            fieldsUpdateComplete = {this.props.fieldsUpdateComplete}
                            key={i}
                            index={i}
                            highLightField={this.props.highLightField}
                            highLightedField={this.props.highLightedField}
                            selectedMenuItem={selectedMenuItemObject}
                            saveChangedFields={this.props.saveChangedFields}
                            group={group}
                            fields={selectedFields}/>)
                    }
                    <div className="rsssl-grid-item-footer">
                        {/*This will be shown only if current step is not the first one*/}
                        { this.props.selectedMenuItem !== menuItems[0].id &&
                            <a href={`#settings/${this.props.previousMenuItem}`} onClick={ () => this.props.previousStep(true) }>
                                { __('Previous', 'really-simple-ssl') }
                            </a>
                        }

                        <Button
                            isPrimary
                            onClick={ this.props.save }>
                            { __( 'Save', 'really-simple-ssl' ) }
                        </Button>

                        {/*This will be shown only if current step is not the last one*/}
                        { this.props.selectedMenuItem !== menuItems[menuItems.length-1].id &&
                            <a href={`#settings/${this.props.nextMenuItem}`} onClick={ this.props.saveAndContinue }>
                                { __( 'Save and Continue', 'really-simple-ssl' ) }
                            </a>
                        }
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