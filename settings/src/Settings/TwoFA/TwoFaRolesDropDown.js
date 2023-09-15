import React, { useState, useEffect } from 'react';
import Select from 'react-select';
import useFields from "../FieldsData";
import useTwoFaData from './TwoFaStore';
import {__} from "@wordpress/i18n";
/**
 * TwoFaRolesDropDown component represents a dropdown select for excluding roles
 * from two-factor authentication email.
 * @param {object} field - The field object containing information about the field.
 */
const TwoFaRolesDropDown = ({ field }) => {
    const {fetchRoles, roles, rolesLoaded} = useTwoFaData();
    const [selectedRoles, setSelectedRoles] = useState(field.value.map(value => ({ value, label: value.charAt(0).toUpperCase() + value.slice(1) })));

    // Custom hook to manage form fields
    const { fields, updateField, setChangedField } = useFields();
    let enabled = false;

    useEffect(() => {
        if (!rolesLoaded) {
            fetchRoles(field.id);
        }
    }, [rolesLoaded]);

    /**
     * Handles the change event of the react-select component.
     * @param {array} selectedOptions - The selected options from the dropdown.
     */
    const handleChange = (selectedOptions) => {
        // Extract the values of the selected options
        const rolesExcluded = selectedOptions.map(option => option.value);

        // Update the field and changedField using the custom hook functions
        updateField(field.id, rolesExcluded);
        setChangedField(field.id, rolesExcluded);

        // Update the selectedRoles state
        setSelectedRoles(selectedOptions);
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

    fields.forEach(function (item, i) {
        if (item.id === 'two_fa_enabled') {
            enabled = item.value;
        }
    });

    if ( ! enabled ) {
        // Render the component
        return (
            <div style={{marginTop: '5px'}}>
                <Select
                    isMulti
                    options={roles}
                    onChange={handleChange}
                    value={selectedRoles}
                    menuPosition={"fixed"}
                    styles={customStyles}
                />
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate login protection to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div style={{marginTop: '5px'}}>
            <Select
                isMulti
                options={roles}
                onChange={handleChange}
                value={selectedRoles}
                menuPosition={"fixed"}
                styles={customStyles}
            />
        </div>
    );
};

export default TwoFaRolesDropDown;
