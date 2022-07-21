import {Component, Fragment} from "@wordpress/element";
import {in_array} from "./utils/lib";
import * as rsssl_api from "./utils/api";
import Placeholder from "./Placeholder";
import Menu from "./Menu";
import Notices from "./Notices";
import Settings from "./Settings";
import sleeper from "./utils/sleeper.js";

import {
    dispatch,
} from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Renders the settings page with Menu and currently selected settings
 *
 */

class SettingsPage extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            fields:'',
            menu:'',
            menuItems:'',
            isAPILoaded: false,
            changedFields:'',
            progress:''
        };
    }

    componentDidMount() {
        this.save = this.save.bind(this);
        this.saveAndContinue = this.saveAndContinue.bind(this);
        this.wizardNextPrevious = this.wizardNextPrevious.bind(this);
        this.saveChangedFields = this.saveChangedFields.bind(this);
        this.updateFieldsListWithConditions = this.updateFieldsListWithConditions.bind(this);
        this.showSavedSettingsNotice = this.showSavedSettingsNotice.bind(this);
        this.updateFieldsListWithConditions();
        let fields = this.props.fields;
        let menu = this.props.menu;
        let progress = this.props.progress;
        //if count >1, it's a wizard
        let menuItems = [];
        let changedFields = [];
        menuItems = menu.menu_items;
        let selectedMenuItem = this.props.selectedMenuItem;
        this.menu = menu;
        this.menuItems = menuItems;
        this.fields = fields;
        this.selectedMenuItem = selectedMenuItem;
        this.changedFields = changedFields;
        this.setState({
            isAPILoaded: true,
            fields: this.props.fields,
            menu: this.props.menu,
            progress: this.props.progress,
            menuItems:menuItems,
            changedFields: changedFields,
        });
    }

    updateFieldsListWithConditions(){
        for (const field of this.props.fields){
            this.props.fields[this.props.fields.indexOf(field)].visible = !(field.hasOwnProperty('react_conditions') && !this.validateConditions(field.react_conditions, this.props.fields));
        }
    }

    saveChangedFields(changedField){
        this.updateFieldsListWithConditions();
        let changedFields = this.changedFields;
        if (!in_array(changedField, changedFields)) {
            changedFields.push(changedField);
        }
        this.changedFields = changedFields;
        this.setState({
            changedFields :changedFields
        });
    }


    showSavedSettingsNotice(){
        const notice = dispatch('core/notices').createNotice(
            'success',
            __( 'Settings Saved', 'really-simple-ssl' ),
            {
                __unstableHTML: true,
                id: 'rsssl_settings_saved',
                type: 'snackbar',
                isDismissible: true,
            }
        ).then(sleeper(2000)).then(( response ) => {
            dispatch('core/notices').removeNotice('rsssl_settings_saved');
        });

    }

    save(){
        const {
            fields,
        } = this.state;
        let saveFields = [];
        for (const field of fields){
            if (in_array(field.id, this.changedFields)){
                saveFields.push(field);
            }
        }
        rsssl_api.setFields(saveFields).then(( response ) => {
            this.changedFields = [];
            this.setState({
                changedFields :[]
            });
            this.showSavedSettingsNotice();
        });
    }

    wizardNextPrevious(isPrevious) {
        const { nextMenuItem, previousMenuItem } = this.props.getPreviousAndNextMenuItems()
        this.props.selectMenu(isPrevious ? previousMenuItem : nextMenuItem);
    }

    saveAndContinue() {
        this.save()

        this.wizardNextPrevious(false);
    }

    validateConditions(conditions, fields){
        let relation = conditions.relation === 'OR' ? 'OR' : 'AND';
        delete conditions['relation'];
        let conditionApplies = true;
        for (const key in conditions) {
            if ( conditions.hasOwnProperty(key) ) {
                let invert = key.indexOf('!')===0;
                let thisConditionApplies = true;
                let subConditionsArray = conditions[key];
                if ( subConditionsArray.hasOwnProperty('relation') ) {
                    thisConditionApplies = this.validateConditions(subConditionsArray, fields)
                } else {
                    for (const conditionField in subConditionsArray) {
                        if ( subConditionsArray.hasOwnProperty(conditionField) ) {
                            let conditionValue = subConditionsArray[conditionField];
                            let conditionFields = fields.filter(field => field.id === conditionField);
                            if (conditionFields.hasOwnProperty(0)){
                                if (conditionFields[0].type==='checkbox') {
                                    let actualValue = +conditionFields[0].value;
                                    conditionValue = +conditionValue;
                                    thisConditionApplies = actualValue == conditionValue;
                                } else {
                                    thisConditionApplies = conditionFields[0].value === conditionValue;
                                }
                            }
                        }
                    }
                    if ( invert ){
                        thisConditionApplies = !thisConditionApplies;
                    }
                }
                if ( relation === 'AND' ) {
                    conditionApplies = conditionApplies && thisConditionApplies;
                } else {
                    conditionApplies = conditionApplies || thisConditionApplies;
                }
            }
        }
        return conditionApplies;
    }

    render() {
        const {
            menu,
            progress,
            selectedStep,
            isAPILoaded,
            changedFields,
        } = this.state;

        if ( ! isAPILoaded ) {
            return (
                <Placeholder></Placeholder>
            );
        }

        //maybe filter step
        // if ( menu.is_wizard ){
        //     let length = menuItems.length;
        //     let temp = []
        //     for ( let i = 0; i < length; i++ ) {
        //         if ( menuItems[i]['step'] !== selectedStep ){
        //             menuItems.splice(i, 1);
        //         }
        //     }
        // }

        let fieldsUpdateComplete = changedFields.length === 0;

        return (
            <Fragment>
                <Menu
                    isAPILoaded={isAPILoaded}
                    menuItems={this.menuItems}
                    menu={this.menu}
                    selectMenu={this.props.selectMenu}
                    selectStep={this.props.selectStep}
                    selectedStep={this.props.selectedStep}
                    selectedMenuItem={this.props.selectedMenuItem}
                    getPreviousAndNextMenuItems={this.props.getPreviousAndNextMenuItems}
                />
                <Settings
                    dropItemFromModal={this.props.dropItemFromModal}
                    selectMenu={this.props.selectMenu}
                    handleModal={this.props.handleModal}
                    showSavedSettingsNotice={this.showSavedSettingsNotice}
                    updateField={this.props.updateField}
                    pageProps={this.props.pageProps}
                    setPageProps={this.props.setPageProps}
                    fieldsUpdateComplete = {fieldsUpdateComplete}
                    highLightField={this.props.highLightField}
                    highLightedField={this.props.highLightedField}
                    isAPILoaded={isAPILoaded}
                    fields={this.fields}
                    progress={progress}
                    saveChangedFields={this.saveChangedFields}
                    menu={menu}
                    save={this.save}
                    saveAndContinue={this.saveAndContinue}
                    selectedMenuItem={this.props.selectedMenuItem}
                    selectedStep={this.props.selectedStep}
                    previousStep = {this.wizardNextPrevious}
                    nextMenuItem = {this.props.nextMenuItem}
                    previousMenuItem = {this.props.previousMenuItem}/>
                <Notices className="rsssl-wizard-notices"/>
            </Fragment>
        )
    }
}
export default SettingsPage