import React, { useState, useEffect } from 'react';
import Select from 'react-select';
import useFields from "../FieldsData";
import useRolesData from './RolesStore';
import {__} from "@wordpress/i18n";
import './select.scss';
/**
 * TwoFaRolesDropDown component represents a dropdown select for excluding roles
 * from two-factor authentication email.
 * @param {object} field - The field object containing information about the field.
 */
const TwoFaRolesDropDown = ({ field }) => {
    const {fetchRoles, roles, rolesLoaded} = useRolesData();
    const [selectedRoles, setSelectedRoles] = useState([]);
    const [otherRoles, setOtherRoles] = useState([]);
    // Custom hook to manage form fields
    const { updateField, getFieldValue, setChangedField, getField, fieldsLoaded } = useFields();
    let enabled = true;

    useEffect(() => {
        if (!rolesLoaded) {
            fetchRoles(field.id);
        }
    }, [rolesLoaded]);

    useEffect(() => {
        if ( field.id==='two_fa_forced_roles' ) {
            let otherField = getField('two_fa_optional_roles');
            let roles = Array.isArray(otherField.value) ? otherField.value : [];
            setOtherRoles(roles);
        } else {
            let otherField = getField('two_fa_forced_roles');
            let roles = Array.isArray(otherField.value) ? otherField.value : [];
            setOtherRoles(roles);
        }
    }, [selectedRoles, getField('two_fa_optional_roles'), getField('two_fa_forced_roles')]);

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
            backgroundColor: field.id === 'two_fa_forced_roles' ? '#F5CD54' :
                field.id === 'two_fa_optional_roles' ? '#FDF5DC' : 'default',
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

    if (field.id === 'two_fa_optional_roles') {
        enabled = getFieldValue('login_protection_enabled');
    }

    const alreadySelected = selectedRoles.map(option => option.value);
    let filteredRoles = [];
    //from roles, remove roles in the usedRoles array
    //merge alreadyselected and otherroles in one array
    let inRolesInUse = [...alreadySelected, ...otherRoles];
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
            />
            {! enabled &&
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate login protection to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            }
        </div>
    );
};

export default TwoFaRolesDropDown;
