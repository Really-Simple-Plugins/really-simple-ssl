import React, { useState, useEffect } from 'react';
import Select from 'react-select';
import useFields from "./FieldsData";
import * as rsssl_api from "../utils/api";

/**
 * TwoFaRolesDropDown component represents a dropdown select for excluding roles
 * from two-factor authentication email.
 * @param {object} field - The field object containing information about the field.
 */
const TwoFaRolesDropDown = ({ field }) => {

    // State initialization
    const [roles, setRoles] = useState([]);
    const [selectedRoles, setSelectedRoles] = useState([]);

    // Custom hook to manage form fields
    const { updateField, setChangedField } = useFields();

    /**
     * Fetches the roles from the server on component mount.
     */
    useEffect(() => {
        const fetchRoles = async () => {
            try {
                // Fetch the roles from the server using rsssl_api.getUserRoles()
                const response = await rsssl_api.getUserRoles();

                // Handle the response
                if (!response) {
                    console.error('No response received from the server.');
                    return;
                }

                const data = response.roles;
                if (!data) {
                    console.error('No data received in the server response.');
                    return;
                }

                // Format the data into options array for react-select
                const formattedData = data.map((role, index) => ({ value: role, label: role }));

                // Set the roles state with formatted data
                setRoles(formattedData);

                // Set the selectedRoles state based on the field value
                const selectedRolesFromField = field.value.map(value => ({ value, label: value }));
                setSelectedRoles(selectedRolesFromField);
            } catch (error) {
                console.error('Error:', error);
            }
        };

        fetchRoles();
    }, []);

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