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
            refreshTests:false,
            fields:'',
            isAPILoaded: false,
            changedFields:'',
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
        this.resetRefreshTests = this.resetRefreshTests.bind(this);
        this.handleNextButtonDisabled = this.handleNextButtonDisabled.bind(this);
        this.checkRequiredFields = this.checkRequiredFields.bind(this);
        let fields = this.props.fields;
        //if count >1, it's a wizard
        let changedFields = [];
        let selectedMenuItem = this.props.selectedMenuItem;
        this.selectedMenuItem = selectedMenuItem;
        this.changedFields = changedFields;
        this.setState({
            isAPILoaded: true,
            fields: this.props.fields,
            changedFields: changedFields,
            selectedMainMenuItem: this.props.selectedMainMenuItem,
        });
    }

    componentDidChange(){
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

        const {
            nextButtonDisabled,
        } = this.state;
        if (nextButtonDisabled !== disable ) {
            this.setState({
                nextButtonDisabled:disable,
            });
        }

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
        //if the only field on this page has actions, this is a tests page, the nextButtonDisabled should be handled by the LE componenent
        let isTestPage = fieldsOnPage.length==1 && fieldsOnPage[0].actions && fieldsOnPage[0].actions.length>0;
        if ( !isTestPage ) {
            let requiredFields = fieldsOnPage.filter(field => field.required && (field.value.length==0 || !field.value) );
            if ( requiredFields.length>0) {
                this.handleNextButtonDisabled(true);
            } else {
                this.handleNextButtonDisabled(false);
            }
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

          //we want to update the changed fields if this field has just become visible. Otherwise the new field won't get saved.
          let previouslyDisabled = this.props.fields[this.props.fields.indexOf(field)].conditionallyDisabled;
          this.props.fields[this.props.fields.indexOf(field)].conditionallyDisabled = !enabled;
          if ( previouslyDisabled && enabled ) {
                //if this is a learning mode field, do not add it to the changed fields list
              let changedFields = this.changedFields;
              if (field.type!=='learningmode' && !in_array(field.id, changedFields)) {
                  changedFields.push(field.id);
              }
              this.changedFields = changedFields;
              this.setState({
                  changedFields:changedFields,
              });
          }

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

    save(skipRefreshTests){
        //skipRefreshTests is default false, but when called from next/previous, it is true
        //this prevents the LE test from restarting on next/previous.
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
            this.props.updateProgress(response.data.progress);
            this.setState({
                changedFields :[],
            });
            if ( !skipRefreshTests ) {
                this.setState({
                    refreshTests:true,
                });
            }
            this.showSavedSettingsNotice();
        });
    }

    resetRefreshTests(){
        this.setState({
            refreshTests:false,
        });
    }

    wizardNextPrevious(isPrevious) {
        const { nextMenuItem, previousMenuItem } = this.props.getPreviousAndNextMenuItems()
        this.props.selectMenu(isPrevious ? previousMenuItem : nextMenuItem);
    }

    saveAndContinue() {
        this.wizardNextPrevious(false);
        this.save(true);
    }

    validateConditions(conditions, fields){

        let relation = conditions.relation === 'OR' ? 'OR' : 'AND';
        let conditionApplies = relation==='AND' ? true : false;

        for (const key in conditions) {
            if ( conditions.hasOwnProperty(key) ) {
                let thisConditionApplies = relation==='AND' ? true : false;
                let subConditionsArray = conditions[key];
                if ( subConditionsArray.hasOwnProperty('relation') ) {
                    thisConditionApplies = this.validateConditions(subConditionsArray, fields)
                } else {
                    for ( let conditionField in subConditionsArray ) {

                        let invert = conditionField.indexOf('!')===0;
                        if ( subConditionsArray.hasOwnProperty(conditionField) ) {
                            let conditionValue = subConditionsArray[conditionField];
                            conditionField = conditionField.replace('!','');
                            let conditionFields = fields.filter(field => field.id === conditionField);
                            if ( conditionFields.hasOwnProperty(0) ){
                                if ( conditionFields[0].type==='checkbox' ) {
                                    let actualValue = +conditionFields[0].value;
                                    conditionValue = +conditionValue;
                                    thisConditionApplies = actualValue === conditionValue;
                                } else {
                                    if (conditionValue.indexOf('EMPTY')!==-1){
                                        thisConditionApplies = conditionFields[0].value.length===0;
                                    } else {
                                        thisConditionApplies = conditionFields[0].value.toLowerCase() === conditionValue.toLowerCase();
                                    }
                                }
                            }
                            if ( invert ){
                                thisConditionApplies = !thisConditionApplies;
                            }
                            if ( relation === 'AND' ) {
                                conditionApplies = conditionApplies && thisConditionApplies;
                            } else {
                                conditionApplies = conditionApplies || thisConditionApplies;
                            }
                        }
                    }
                }
            }
        }
        return conditionApplies ? 1 : 0;
    }

    render() {
        const {
            selectedStep,
            isAPILoaded,
            refreshTests,
            changedFields,
            nextButtonDisabled,
        } = this.state;

        if ( ! isAPILoaded ) {
            return (
                <Placeholder></Placeholder>
            );
        }
        this.props.menu.menu_items = this.addVisibleToMenuItems(this.props.menu.menu_items);
        this.checkRequiredFields();
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
                    updateFields={this.props.updateFields}
                    dropItemFromModal={this.props.dropItemFromModal}
                    selectMenu={this.props.selectMenu}
                    selectMainMenu={this.props.selectMainMenu}
                    nextButtonDisabled={nextButtonDisabled}
                    handleNextButtonDisabled={this.handleNextButtonDisabled}
                    getDefaultMenuItem={this.props.getDefaultMenuItem}
                    handleModal={this.props.handleModal}
                    showSavedSettingsNotice={this.showSavedSettingsNotice}
                    updateField={this.props.updateField}
                    getFieldValue={this.props.getFieldValue}
                    resetRefreshTests={this.resetRefreshTests}
                    refreshTests={refreshTests}
                    addHelp={this.props.addHelp}
                    pageProps={this.props.pageProps}
                    setPageProps={this.props.setPageProps}
                    fieldsUpdateComplete = {fieldsUpdateComplete}
                    highLightField={this.props.highLightField}
                    highLightedField={this.props.highLightedField}
                    isAPILoaded={isAPILoaded}
                    fields={this.props.fields}
                    progress={this.props.progress}
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