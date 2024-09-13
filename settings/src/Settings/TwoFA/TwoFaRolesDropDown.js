import {useRef, useEffect, useState} from '@wordpress/element';
import Select from 'react-select';
import useFields from "../FieldsData";
import useRolesData from './RolesStore';
import {__} from "@wordpress/i18n";
import './select.scss';

/**
 * TwoFaRolesDropDown component represents a dropdown select for excluding roles
 * from two-factor authentication email.
 * @param {object} field - The field object containing information about the field.
 * @param forcedRoledId
 * @param optionalRolesId
 */
const TwoFaRolesDropDown = ({ field, forcedRoledId, optionalRolesId }) => {
    const {fetchRoles, roles, rolesLoaded} = useRolesData();
    const [selectedRoles, setSelectedRoles] = useState([]);
    const [otherRoles, setOtherRoles] = useState([]);
    // Custom hook to manage form fields
    const { updateField, getFieldValue, setChangedField, getField, fieldsLoaded, showSavedSettingsNotice, saveField } = useFields();

    let enabled = true;

    useEffect(() => {
        if (!rolesLoaded) {
            fetchRoles(field.id);
        }

    }, [rolesLoaded]);


    useEffect(() => {
        if ( field.id === forcedRoledId ) {
            let otherField = getField(optionalRolesId);
            let roles = Array.isArray(otherField.value) ? otherField.value : [];
            setOtherRoles(roles);
        } else {
            let otherField = getField(forcedRoledId);
            let roles = Array.isArray(otherField.value) ? otherField.value : [];
            setOtherRoles(roles);
        }
    }, [selectedRoles, getField(optionalRolesId), getField(forcedRoledId)]);

    useEffect(() => {
       if ( !field.value ) {
            setChangedField(field.id, field.default);
            updateField(field.id, field.default);
            setSelectedRoles(field.default.map((role, index) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));
       } else {
           setSelectedRoles(field.value.map((role, index) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));

       }
    },[fieldsLoaded]);

    /**
     * Handles the change event of the react-select component.
     * @param {array} selectedOptions - The selected options from the dropdown.
     */
    const handleChange = (selectedOptions) => {
        // Extract the values of the selected options
        const rolesExcluded = selectedOptions.map(option => option.value);

        // Check if the roles enabled are also set, if not show a warning
        let rolesEnabledEmail = getFieldValue('two_fa_enabled_roles_email');
        let rolesEnabledTotp = getFieldValue('two_fa_enabled_roles_totp');
        let rolesEnabled = rolesEnabledEmail.concat(rolesEnabledTotp);

        // Check if the roles enabled also contains the selectedOptions
        let rolesEnabledContainsSelected = rolesEnabled.filter(role => selectedOptions.map(option => option.value).includes(role));

        if ( rolesEnabledContainsSelected.length === 0 && selectedOptions.length > 0 ) {
            showSavedSettingsNotice(__('You have enforced 2FA, but not configured any methods.', 'really-simple-ssl'), 'error');
        } else {
            // Checking each role if it is in the rolesEnabled array
            selectedOptions.forEach(role => {
                //the role.value needs to be in the rolesEnabled array
                if ( !rolesEnabled.includes(role.value) ) {
                    // showing an error when the role is not in the rolesEnabled array, with the role name
                    showSavedSettingsNotice(__('You have enforced 2FA, but not configured any methods for the role: ', 'really-simple-ssl') + role.label, 'error');
                }
            });
        }

        // Update the selectedRoles state
        setSelectedRoles(selectedOptions);

        // Update the field and changedField using the custom hook functions
        updateField(field.id, rolesExcluded);
        setChangedField(field.id, rolesExcluded);
    };

    const customStyles = {
        multiValue: (provided) => ({
            ...provided,
            borderRadius: '10px',
            backgroundColor: field.id === forcedRoledId ? '#F5CD54' :
                field.id === optionalRolesId ? '#FDF5DC' : 'default',
        }),
        multiValueRemove: (base, state) => ({
            ...base,
            color: state.isHovered ? 'initial' : base.color,
            opacity: '0.7',
            ':hover': {
                backgroundColor: 'initial',
                color: 'initial',
                opacity: '1',
            },
        })
    };

    if (field.id === optionalRolesId) {
        enabled = getFieldValue('login_protection_enabled');
    }

    const alreadySelected = selectedRoles.map(option => option.value);
    let filteredRoles = [];
    let inRolesInUse = [...alreadySelected, ...otherRoles];
    //from roles, remove roles in the usedRoles array
    roles.forEach(function (item, i) {
        if ( Array.isArray(inRolesInUse) && inRolesInUse.includes(item.value) ) {
            filteredRoles.splice(i, 1);
        } else {
            filteredRoles.push(item);
        }
    });

    return (
        <div style={{marginTop: '5px'}}>
            <Select
                isMulti
                options={filteredRoles}
                onChange={handleChange}
                value={selectedRoles}
                menuPosition={"fixed"}
                styles={customStyles}
                isDisabled={!getFieldValue('login_protection_enabled')}
            />
        </div>
    );
};

export default TwoFaRolesDropDown;
