import {Component, Fragment} from "@wordpress/element";
import {in_array} from "../utils/lib";
import * as rsssl_api from "../utils/api";
import Placeholder from "../Placeholder/Placeholder";
import Menu from "../Menu/Menu";
import Notices from "./Notices";
import Settings from "./Settings";
import sleeper from "../utils/sleeper.js";
import {dispatch,} from '@wordpress/data';
import {__} from '@wordpress/i18n';

/*
 * Renders the settings page with Menu and currently selected settings
 *
 */

class SettingsPage extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            fields:'',
            isAPILoaded: false,
            changedFields:'',
            progress:'',
            nextButtonDisabled: false,
        };
    }

    componentDidMount() {
        this.save = this.save.bind(this);
        this.saveAndContinue = this.saveAndContinue.bind(this);
        this.wizardNextPrevious = this.wizardNextPrevious.bind(this);
        this.saveChangedFields = this.saveChangedFields.bind(this);
        this.addVisibleToMenuItems = this.addVisibleToMenuItems.bind(this);
        this.updateFieldsListWithConditions = this.updateFieldsListWithConditions.bind(this);
        this.filterMenuItems = this.filterMenuItems.bind(this);
        this.showSavedSettingsNotice = this.showSavedSettingsNotice.bind(this);
        this.handleNextButtonDisabled = this.handleNextButtonDisabled.bind(this);
        this.checkRequiredFields = this.checkRequiredFields.bind(this);
        let fields = this.props.fields;
        let progress = this.props.progress;
        //if count >1, it's a wizard
        let changedFields = [];
        let selectedMenuItem = this.props.selectedMenuItem;
        this.selectedMenuItem = selectedMenuItem;
        this.changedFields = changedFields;
        this.checkRequiredFields();
        this.setState({
            isAPILoaded: true,
            fields: this.props.fields,
            progress: this.props.progress,
            changedFields: changedFields,
            selectedMainMenuItem: this.props.selectedMainMenuItem,
        });
    }

    addVisibleToMenuItems(menuItems) {
        const newMenuItems = menuItems;
        for (const [index, menuItem] of menuItems.entries()) {
            menuItem.visible = true;
            if( menuItem.hasOwnProperty('menu_items') ) {
                menuItem.menu_items = this.addVisibleToMenuItems(menuItem.menu_items);
            }
            newMenuItems[index] = menuItem;
        }

        return newMenuItems;
    }
    /*
    * Set next button to disabled from the fields
    */
    handleNextButtonDisabled(disable) {
        this.setState({
            nextButtonDisabled:disable,
        });
    }

    //check if all required fields have been enabled. If so, enable save/continue button
    checkRequiredFields(){
        let fieldsOnPage = [];
        //get all fields with group_id this.props.group_id
        for (const field of this.props.fields){
            if (field.menu_id === this.props.selectedMenuItem ){
                fieldsOnPage.push(field);
            }
        }
        let requiredFields = fieldsOnPage.filter(field => field.required && (field.value.length==0 || !field.value) );
        if ( requiredFields.length>0) {
            this.handleNextButtonDisabled(true);
        } else {
            this.handleNextButtonDisabled(false);
        }
    }

    filterMenuItems(menuItems) {
        const newMenuItems = menuItems;
        for (const [index, menuItem] of menuItems.entries()) {
            const searchResult = this.props.fields.filter((field) => {
                return (field.menu_id === menuItem.id && field.visible)
            });
            if(searchResult.length === 0) {
                newMenuItems[index].visible = false;
            } else {
                newMenuItems[index].visible = true;
                if(menuItem.hasOwnProperty('menu_items')) {
                    newMenuItems[index].menu_items = this.filterMenuItems(menuItem.menu_items);
                }
            }
        }
        return newMenuItems;
    }

    updateFieldsListWithConditions(){
        for (const field of this.props.fields){

          let enabled = !(field.hasOwnProperty('react_conditions') && !this.validateConditions(field.react_conditions, this.props.fields));

          this.props.fields[this.props.fields.indexOf(field)].conditionallyDisabled = !enabled;
          if (!enabled && field.type==='letsencrypt') {
            this.props.fields[this.props.fields.indexOf(field)].visible = false;
          } else {
            this.props.fields[this.props.fields.indexOf(field)].visible = true;
          }
        }
        this.filterMenuItems(this.props.menu.menu_items)
    }

    saveChangedFields(changedField){
        this.updateFieldsListWithConditions();
        let changedFields = this.changedFields;
        if (!in_array(changedField, changedFields)) {
            changedFields.push(changedField);
        }
        this.changedFields = changedFields;
        this.setState({
            changedFields:changedFields,
        });
        this.checkRequiredFields();
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
            if ( in_array(field.id, this.changedFields) ){
                saveFields.push(field);
            }
        }

        rsssl_api.setFields(saveFields).then(( response ) => {
            this.changedFields = [];
            this.setState({
                changedFields :[],
                progress: response.data.progress,
            });
            this.showSavedSettingsNotice();
        });
    }

    wizardNextPrevious(isPrevious) {
        const { nextMenuItem, previousMenuItem } = this.props.getPreviousAndNextMenuItems()
        this.props.selectMenu(isPrevious ? previousMenuItem : nextMenuItem);
    }

    saveAndContinue() {
        this.wizardNextPrevious(false);
        this.save()
    }

    validateConditions(conditions, fields){
        let relation = conditions.relation === 'OR' ? 'OR' : 'AND';
        let conditionApplies = true;
        for (const key in conditions) {
            if ( conditions.hasOwnProperty(key) ) {
                let thisConditionApplies = true;
                let subConditionsArray = conditions[key];
                if ( subConditionsArray.hasOwnProperty('relation') ) {
                    thisConditionApplies = this.validateConditions(subConditionsArray, fields)
                } else {
                    for (let conditionField in subConditionsArray) {
                        let invert = conditionField.indexOf('!')===0;
                        if ( subConditionsArray.hasOwnProperty(conditionField) ) {
                            let conditionValue = subConditionsArray[conditionField];
                            conditionField = conditionField.replace('!','');
                            let conditionFields = fields.filter(field => field.id === conditionField);
                            if (conditionFields.hasOwnProperty(0)){
                                if (conditionFields[0].type==='checkbox') {
                                    let actualValue = +conditionFields[0].value;
                                    conditionValue = +conditionValue;
                                    thisConditionApplies = actualValue === conditionValue;
                                } else {
                                    thisConditionApplies = conditionFields[0].value.toLowerCase() === conditionValue.toLowerCase();
                                }
                            }
                        }

                        if ( invert ){
                            thisConditionApplies = !thisConditionApplies;
                        }

                    }

                }
                if ( relation === 'AND' ) {
                    conditionApplies = conditionApplies && thisConditionApplies;
                } else {
                    conditionApplies = conditionApplies || thisConditionApplies;
                }
            }
        }
        return conditionApplies ? 1 : 0;
    }

    render() {
        const {
            progress,
            selectedStep,
            isAPILoaded,
            changedFields,
            nextButtonDisabled,
        } = this.state;


        if ( ! isAPILoaded ) {
            return (
                <Placeholder></Placeholder>
            );
        }
        this.props.menu.menu_items = this.addVisibleToMenuItems(this.props.menu.menu_items);
        this.updateFieldsListWithConditions();
        let fieldsUpdateComplete = changedFields.length === 0;
        return (
            <Fragment>
                <Menu
                    isAPILoaded={isAPILoaded}
                    menu={this.props.menu}
                    selectMenu={this.props.selectMenu}
                    selectStep={this.props.selectStep}
                    selectedStep={this.props.selectedStep}
                    selectedMenuItem={this.props.selectedMenuItem}
                    selectedMainMenuItem={this.props.selectedMainMenuItem}
                    getPreviousAndNextMenuItems={this.props.getPreviousAndNextMenuItems}
                />
                <Settings
                    dropItemFromModal={this.props.dropItemFromModal}
                    selectMenu={this.props.selectMenu}
                    nextButtonDisabled={nextButtonDisabled}
                    handleNextButtonDisabled={this.handleNextButtonDisabled}
                    getDefaultMenuItem={this.props.getDefaultMenuItem}
                    handleModal={this.props.handleModal}
                    showSavedSettingsNotice={this.showSavedSettingsNotice}
                    updateField={this.props.updateField}
                    addHelp={this.props.addHelp}
                    pageProps={this.props.pageProps}
                    setPageProps={this.props.setPageProps}
                    fieldsUpdateComplete = {fieldsUpdateComplete}
                    highLightField={this.props.highLightField}
                    highLightedField={this.props.highLightedField}
                    isAPILoaded={isAPILoaded}
                    fields={this.props.fields}
                    progress={progress}
                    saveChangedFields={this.saveChangedFields}
                    menu={this.props.menu}
                    save={this.save}
                    saveAndContinue={this.saveAndContinue}
                    selectedMenuItem={this.props.selectedMenuItem}
                    selectedMainMenuItem={this.props.selectedMainMenuItem}
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