import {create} from 'zustand';
import {produce} from 'immer';
import * as rsssl_api from "../utils/api";
import {__} from '@wordpress/i18n';
import {toast} from 'react-toastify';

const fetchFields = () => {
    return rsssl_api.getFields().then((response) => {
        let fields = response.fields;
        let progress = response.progress;
        let error = response.error;
        return {fields, progress, error};
    }).catch((error) => {
        console.error(error);
    });
}

const useFields = create(( set, get ) => ({
    fieldsLoaded: false,
    error:false,
    fields: [],
    changedFields:[],
    progress:[],
    nextButtonDisabled:false,
    overrideNextButtonDisabled:false,
    refreshTests:false,
    highLightField: '',
    setHighLightField: (highLightField) => {
        set({ highLightField });
    },

    setRefreshTests: (refreshTests) => set(state => ({ refreshTests })),
    handleNextButtonDisabled: (nextButtonDisabled) => {
        set({overrideNextButtonDisabled: nextButtonDisabled});
    },
    setChangedField: (id, value) => {
        set(
            produce((state) => {
                //remove current reference
                const existingFieldIndex = state.changedFields.findIndex(field => {
                    return field.id===id;
                });

                if (existingFieldIndex!==-1){
                    state.changedFields.splice(existingFieldIndex, 1);
                }

                //add again, with new value
                let field = {};
                field.id = id;
                field.value = value;
                state.changedFields.push(field);
            })
        )
    },
    showSavedSettingsNotice : (text , type = 'success') => {
        handleShowSavedSettingsNotice(text, type);
    },

    updateField: (id, value) => {
        set(
            produce((state) => {
                let index = state.fields.findIndex(fieldItem => fieldItem.id === id);
                if (index !== -1) {
                    state.fields[index].value = value;
                }
            })
        )
    },
    updateFieldAttribute: (id, attribute, value) => {
        set(
            produce((state) => {
                let index = state.fields.findIndex(fieldItem => fieldItem.id === id);
                if (index !== -1) {
                    state.fields[index][attribute] = value;
                }
            })
        )
    },
    updateSubField: (id, subItemId, value) => {
        set(
            produce((state) => {
                let index = state.fields.findIndex(fieldItem => fieldItem.id === id);
                let itemValue = state.fields[index].value;
                if (!Array.isArray(itemValue)) {
                    itemValue = [];
                }

                let subIndex = itemValue.findIndex(subItem => subItem.id === subItemId);
                if (subIndex !== -1) {
                    state.fields[index].updateItemId = subItemId;
                    state.fields[index].value[subIndex]['value'] = value;
                    state.fields[index].value = itemValue.map(item => {
                        const { deleteControl, valueControl, statusControl, ...rest } = item;
                        return rest;
                    });
                }
            })
        )
    },
    removeHelpNotice: (id) => {
        set(
            produce((state) => {
                const fieldIndex = state.fields.findIndex(field => {
                    return field.id===id;
                });
                state.fields[fieldIndex].help = false;
            })
        )
    },
    addHelpNotice : (id, label, text, title, url) => {
        get().removeHelpNotice(id);
        //create help object

        let help = {};
        help.label=label;
        help.text=text;
        if (url) help.url=url;
        if (title) help.title=title;
        set(
            produce((state) => {
                const fieldIndex = state.fields.findIndex(field => {
                    return field.id===id;
                });
                if (fieldIndex!==-1) {
                    state.fields[fieldIndex].help = help;
                }
            })
        )
    },
    fieldAlreadyEnabled: (id) => {
        let fieldIsChanged = get().changedFields.filter(field => field.id === id ).length>0;
        let fieldIsEnabled = get().getFieldValue(id);
        return !fieldIsChanged && fieldIsEnabled;
    },
    getFieldValue : (id) => {
        let fields = get().fields;
        let fieldItem = fields.filter(field => field.id === id )[0];
        if (fieldItem){
            return fieldItem.value;
        }
        return false;
    },
    getField : (id) => {
        let fields = get().fields;
        let fieldItem = fields.filter(field => field.id === id )[0];
        if (fieldItem){
            return fieldItem;
        }
        return false;
    },
    saveFields: async (skipRefreshTests, showSavedNotice, force = false) => {
        let refreshTests = typeof skipRefreshTests !== 'undefined' ? skipRefreshTests : true;
        showSavedNotice = typeof showSavedNotice !== 'undefined' ? showSavedNotice : true;
        let fields = get().fields;
        fields = fields.filter(field => field.data_target !== 'banner');
        let changedFields = get().changedFields;
        let saveFields = [];
        //data_target
        for (const field of fields) {
            let fieldIsIncluded = changedFields.filter(changedField => changedField.id === field.id).length > 0;
            //also check if there's no saved value yet for radio fields, by checking the never_saved attribute.
            //a radio or select field looks like it's completed, but won't save if it isn't changed.
            //this should not be the case for disabled fields, as these fields often are enabled server side because they're enabled outside Really Simple Security.
            let select_or_radio = field.type === 'select' || field.type === 'radio';
            if (fieldIsIncluded || (field.never_saved && !field.disabled && select_or_radio)) {
                saveFields.push(field);
            }
        }

        //if no fields were changed, do nothing.
        if (saveFields.length > 0 || force === true) {
            let response = rsssl_api.setFields(saveFields).then((response) => {
                return response;
            })

            if (showSavedNotice) {
                toast.promise(
                    response,
                    {
                        pending: __('Saving settings...', 'really-simple-ssl'),
                        success: __('Settings saved', 'really-simple-ssl'),
                        error: __('Something went wrong', 'really-simple-ssl'),
                    }
                );
            }
            await response.then((response) => {
                set(
                    produce((state) => {
                        state.changedFields = [];
                        state.fields = response.fields;
                        state.progress = response.progress;
                        state.refreshTests = refreshTests;
                    })
                )
            });
        }

        if (showSavedNotice && saveFields.length === 0) {
            //nothing to save. show instant success.
            toast.promise(
                Promise.resolve(),
                {
                    success: __('Settings saved', 'really-simple-ssl'),
                }
            );
        }

    },

    updateFieldsData: (selectedSubMenuItem) => {
        let fields = get().fields;
        fields = updateFieldsListWithConditions(fields);

        //only if selectedSubMenuItem is actually passed
        if (selectedSubMenuItem) {
            let nextButtonDisabled = isNextButtonDisabled(fields, selectedSubMenuItem);
            //if the button was set to disabled with the handleNextButtonDisabled function, we give that priority until it's released.
            if (get().overrideNextButtonDisabled) {
                nextButtonDisabled = get().overrideNextButtonDisabled;
            }
            set(
                produce((state) => {
                    state.nextButtonDisabled = nextButtonDisabled;
                })
            )
        }

        set(
            produce((state) => {
                state.fields = fields;
            })
        )
    },
    fetchFieldsData: async ( selectedSubMenuItem ) => {
        const { fields, progress, error }   = await fetchFields();
        let conditionallyEnabledFields = updateFieldsListWithConditions(fields);
        let selectedFields = conditionallyEnabledFields.filter(field => field.menu_id === selectedSubMenuItem);
        set({fieldsLoaded: true, fields:conditionallyEnabledFields, selectedFields:selectedFields, progress:progress, error: error });
    }
}));

export default useFields;

//check if all required fields have been enabled. If so, enable save/continue button
const isNextButtonDisabled = (fields, selectedMenuItem) => {
    let fieldsOnPage = [];
    //get all fields with group_id this.props.group_id
    for (const field of fields){
        if (field.menu_id === selectedMenuItem ){
            fieldsOnPage.push(field);
        }
    }

    let requiredFields = fieldsOnPage.filter(field => field.required && !field.conditionallyDisabled && (field.value.length==0 || !field.value) );
    return requiredFields.length > 0;
}

const updateFieldsListWithConditions = (fields) => {
    let newFields = [];
    if (!fields || !Array.isArray(fields)) {
        return [];
    }
    fields.forEach(function(field, i) {
        let enabled = !( field.hasOwnProperty('react_conditions') && !validateConditions(field.react_conditions, fields, field.id) );
        let previouslyEnabled = !field.conditionallyDisabled;
        //we want to update the changed fields if this field has just become visible. Otherwise the new field won't get saved.
        const newField = {...field};
        newField.conditionallyDisabled = !enabled;
        newField.visible = !(!enabled && (newField.type === 'letsencrypt' || newField.condition_action === 'hide'));

        newFields.push(newField);
        //if this is a learning mode field, do not add it to the changed fields list
        if ( !previouslyEnabled && newField.enabled && field.type!=='learningmode') {
            set().setChangedField(field.id, field.value);
        }
    });
    return newFields;
}

const handleShowSavedSettingsNotice = ( text, type ) => {
    if (typeof text === 'undefined') {
        text = __( 'Settings saved', 'really-simple-ssl' );
    }

    if (typeof type === 'undefined') {
        type = 'success';
    }

    if (type === 'error') {
        toast.error(text);
    }

    if (type === 'warning') {
        toast.warning(text);
    }

    if (type === 'info') {
        toast.info(text);
    }

    if (type === 'success') {
        toast.success(text);
    }
}

const validateConditions = (conditions, fields, fieldId) => {
    let relation = conditions[0].relation === 'OR' ? 'OR' : 'AND';
    let conditionApplies = relation==='AND';
    for (const key in conditions) {
        if ( conditions.hasOwnProperty(key) ) {
            let thisConditionApplies = relation==='AND';
            let subConditionsArray = conditions[key];
            if ( subConditionsArray.hasOwnProperty('relation') ) {
                thisConditionApplies = validateConditions(subConditionsArray, fields, fieldId)
            } else {
                for ( let conditionField in subConditionsArray ) {
                    let invert = conditionField.indexOf('!')===0;
                    if ( subConditionsArray.hasOwnProperty(conditionField) ) {
                        let conditionValue = subConditionsArray[conditionField];
                        conditionField = conditionField.replace('!','');
                        let conditionFields = fields.filter(field => field.id === conditionField);
                        if ( conditionFields.hasOwnProperty(0) ){
                            let field = conditionFields[0];
                            let actualValue = field.value;
                            if ( field.type==='text_checkbox' ) {
                                thisConditionApplies = actualValue.hasOwnProperty('show') && actualValue['show'] == conditionValue;//can be 1/true or 0/false
                            } else if ( field.type==='checkbox' ) {
                                thisConditionApplies = actualValue == conditionValue;//can be 1/true or 0/false
                            } else if ( field.type==='multicheckbox' ) {
                                //multicheckbox conditions
                                //loop through objects
                                thisConditionApplies = false;
                                let arrayValue = actualValue;
                                if ( arrayValue.length===0 ) {
                                    thisConditionApplies = false;
                                } else {
                                    for (const key of Object.keys(arrayValue)) {
                                        if ( !Array.isArray(conditionValue) ) conditionValue = [conditionValue];
                                        if ( conditionValue.includes(arrayValue[key])){
                                            thisConditionApplies = true;
                                            break;
                                        }
                                    }
                                }
                            } else if ( field.type==='radio' ) {
                                //as the regions field can be both radio and multicheckbox, an array is possible for a radio field
                                if ( Array.isArray(conditionValue) ) {
                                    thisConditionApplies = conditionValue.includes(actualValue);
                                } else {
                                    thisConditionApplies = conditionValue === actualValue;
                                }

                            } else {
                                if (conditionValue === true ) {
                                    thisConditionApplies = actualValue===1 || actualValue === "1" || actualValue === true;
                                } else if (conditionValue === false ) {
                                    thisConditionApplies = actualValue === 0 || actualValue === "0" || actualValue === false;
                                } else if (conditionValue.indexOf('EMPTY')!==-1) {
                                    thisConditionApplies = actualValue.length === 0;
                                } else {
                                    thisConditionApplies = String(actualValue).toLowerCase() === conditionValue.toLowerCase();
                                }
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

                if ( relation === 'AND' ) {
                    conditionApplies = conditionApplies && thisConditionApplies;
                } else {
                    conditionApplies = conditionApplies || thisConditionApplies;
                }
            }
        }
    }

    return conditionApplies ? 1 : 0;
}
