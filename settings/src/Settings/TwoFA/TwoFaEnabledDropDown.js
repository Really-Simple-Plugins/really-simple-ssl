import {useRef, useEffect, useState} from '@wordpress/element';
import Select from 'react-select';
import useFields from "../FieldsData";
import useRolesData from './RolesStore';
import {__} from "@wordpress/i18n";
// import './select.scss';
/**
 * TwoFaRolesDropDown component represents a dropdown select for excluding roles
 * from two-factor authentication email.
 * @param {object} field - The field object containing information about the field.
 * @param enabledId
 */
const TwoFaEnabledDropDown = ({ field }) => {
    const {fetchRoles, roles, rolesLoaded} = useRolesData();
    const [selectedRoles, setSelectedRoles] = useState([]);
    const [otherRoles, setOtherRoles] = useState([]);
    // Custom hook to manage form fields
    const { updateField, getFieldValue, setChangedField, getField, fieldsLoaded,saveFields } = useFields();
    const [enabled, setEnabled] = useState(false);
    let enabledId = field.id;

    let featureEnabled = getFieldValue('login_protection_enabled');

    //if the field enforce_frequent_password_change is enabled, then the field is enabled
    useEffect(() => {
        setEnabled(getFieldValue('login_protection_enabled'));
        if(getFieldValue('login_protection_enabled') === 1 && field.id === 'two_fa_enabled_roles_totp') {
            setChangedField(field.id, field.value);
            saveFields(true, false);
        }
    },[getFieldValue('login_protection_enabled')]);


    useEffect(() => {
        if (!rolesLoaded) {
            fetchRoles(field.id);
        }

    }, [rolesLoaded]);


    useEffect(() => {
        if ( field.id === enabledId ) {
            let otherField = getField(enabledId);
            let roles = Array.isArray(otherField.value) ? otherField.value : [];
            setOtherRoles(roles);
        }
    }, [selectedRoles, getField(enabledId)]);

    useEffect(() => {
        if ( field.id === enabledId ) {
            let otherField = getField(enabledId);
            let roles = Array.isArray(otherField.value) ? otherField.value : [];
            setSelectedRoles(roles.map((role, index) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));
        }
       if ( !field.value ) {
            setChangedField(field.id, field.default);
            updateField(field.id, field.default);
            setSelectedRoles(field.default.map((role, index) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));
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
            backgroundColor: field.id === enabledId ? '#F5CD54' :
                field.id === enabledId ? '#FDF5DC' : 'default',
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
        }),
        menuPortal: (base) => ({
            ...base,
            zIndex: 30, // Adding z-index directly here
        }),
    };

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
                isDisabled={!enabled}
            />
            {! featureEnabled &&
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate Two-Factor Authentication to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            }
        </div>
    );
};

export default TwoFaEnabledDropDown;
