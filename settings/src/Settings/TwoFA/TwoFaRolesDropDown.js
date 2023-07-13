import React, { useState, useEffect } from 'react';
import Select from 'react-select';
import useFields from "../FieldsData";
import useTwoFaData from './TwoFaStore';
import * as rsssl_api from "../../utils/api";
/**
 * TwoFaRolesDropDown component represents a dropdown select for excluding roles
 * from two-factor authentication email.
 * @param {object} field - The field object containing information about the field.
 */
const TwoFaRolesDropDown = ({ field }) => {
    const {fetchRoles, roles, rolesLoaded} = useTwoFaData();
    const [selectedRoles, setSelectedRoles] = useState([]);

    // Custom hook to manage form fields
    const { updateField, setChangedField } = useFields();

    useEffect(() => {
        const run = async () => {
            await fetchRoles(field.id);
        }
        run();
    }, []);

    /**
     * Fetches the roles from the server on component mount.
     */
    useEffect(() => {
        const run = async () => {
            try {
                // replace `get_roles` with your actual action
                const response = await rsssl_api.doAction('get_roles', { id: field.id });
                console.log(response);

                // Set the selectedRoles state based on the field value
                const selectedRolesFromField = field.value.map(value => ({ value, label: value }));
                setSelectedRoles(selectedRolesFromField);
            } catch (err) {
                console.error(err);
            } finally {
                // setLoading(false);
            }
        }
        run();
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

    console.log(roles)
    console.log(selectedRoles)
    // Render the component
    return (
        <div>
            <label htmlFor="rsssl-exclude-roles">
                {field.label}
            </label>
                <Select
                    isMulti
                    options={roles}
                    onChange={handleChange}
                    value={selectedRoles}
                    menuPosition={"fixed"}
                />
        </div>
    );
};

export default TwoFaRolesDropDown;