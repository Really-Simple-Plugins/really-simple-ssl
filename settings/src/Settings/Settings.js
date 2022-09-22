import {Component, Fragment} from "@wordpress/element";
import Placeholder from "../Placeholder/Placeholder";
import {in_array} from "../utils/lib";
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
            noticesExpanded:true,
        };
    }

    toggleNotices(){
        const {
            noticesExpanded,
        } = this.state;

        this.setState({
            noticesExpanded:!noticesExpanded,
        });
    }

    render() {
        let isAPILoaded = this.props.isAPILoaded;
        let progress = this.props.progress;
        let selectedMenuItem = this.props.selectedMenuItem;
        let fields = this.props.fields;
        let selectedStep = this.props.selectedStep;
        let menu = this.props.menu;
        const { menu_items: menuItems } = menu;
        const {
            noticesExpanded,
        } = this.state;

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
        let btnSaveText = __('Save', 'really-simple-ssl');
        for (const menuItem of menuItems ) {
            if (menuItem.id===selectedMenuItem && menuItem.tests_only ) {
                btnSaveText = __('Refresh', 'really-simple-ssl');
            }
        }

        //convert progress notices to an array useful for the help blocks
        let notices = [];
        for (const notice of progress.notices){
            let noticeField = false;
            //notices that are linked to a field.
            if ( notice.show_with_options ) {
                noticeField = selectedFields.filter(field => notice.show_with_options && notice.show_with_options.includes(field.id) );
                if (noticeField.length===0) noticeField = false;
            }
            //notices that are linked to a menu id.
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
        return (
            <Fragment>
                <div className="rsssl-wizard-settings rsssl-column-2">
                    { groups.map((group, i) =>
                        <SettingsGroup
                            updateFields={this.props.updateFields}
                            dropItemFromModal={this.props.dropItemFromModal}
                            selectMenu={this.props.selectMenu}
                            selectMainMenu={this.props.selectMainMenu}
                            handleNextButtonDisabled={this.props.handleNextButtonDisabled}
                            menu={this.props.menu}
                            handleModal={this.props.handleModal}
                            showSavedSettingsNotice={this.props.showSavedSettingsNotice}
                            updateField={this.props.updateField}
                            getFieldValue={this.props.getFieldValue}
                            refreshTests={this.props.refreshTests}
                            resetRefreshTests={this.props.resetRefreshTests}
                            addHelp={this.props.addHelp}
                            pageProps={this.props.pageProps}
                            setPageProps={this.props.setPageProps}
                            fieldsUpdateComplete = {this.props.fieldsUpdateComplete}
                            key={i}
                            index={i}
                            highLightField={this.props.highLightField}
                            highLightedField={this.props.highLightedField}
                            selectedMenuItem={selectedMenuItem}
                            saveChangedFields={this.props.saveChangedFields}
                            group={group}
                            fields={selectedFields}/>)
                    }
                    <div className="rsssl-grid-item-footer">
                        {/*This will be shown only if current step is not the first one*/}
                        { this.props.selectedMenuItem !== menuItems[0].id &&
                            <a className="button button-secondary" href={`#${this.props.selectedMainMenuItem}/${this.props.previousMenuItem}`} onClick={ () => this.props.previousStep(true) }>
                                { __('Previous', 'really-simple-ssl') }
                            </a>
                        }
                        <button
                            className="button button-primary"
                            onClick={ this.props.save }>
                            { btnSaveText }
                        </button>
                        {/*This will be shown only if current step is not the last one*/}
                        { this.props.selectedMenuItem !== menuItems[menuItems.length-1].id &&
                            <>
                                <a disabled={this.props.nextButtonDisabled} className="button button-primary" href={`#${this.props.selectedMainMenuItem}/${this.props.nextMenuItem}`} onClick={ this.props.saveAndContinue }>
                                    { __( 'Save and Continue', 'really-simple-ssl' ) }
                                </a>
                            </>
                        }
                    </div>
                </div>
                <div className="rsssl-wizard-help">
                    <div className="rsssl-help-header">
                        <div className="rsssl-help-title rsssl-h4">
                            {__("Notifications", "really-simple-ssl")}
                        </div>
                        <div className="rsssl-help-control" onClick={ () => this.toggleNotices() }>
                            {!noticesExpanded && __("Expand all","really-simple-ssl")}
                            {noticesExpanded && __("Collapse all","really-simple-ssl")}
                        </div>
                    </div>
                    {notices.map((field, i) => <Help key={i} noticesExpanded={noticesExpanded} index={i} help={field} fieldId={field.id}/>)}
                </div>
            </Fragment>
        )
    }
}
export default Settings