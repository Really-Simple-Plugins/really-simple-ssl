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
    const { updateField, getFieldValue, setChangedField, getField, fieldsLoaded, showSavedSettingsNotice, saveField } = useFields();
    // Reference for tooltip usage
    const selectRef = useRef(null);
    // Check if the select component should be disabled based on `rsssl_settings.email_verified`
    const isSelectDisabled = ! getFieldValue('login_protection_enabled');

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
        if (!field.value) {
            setChangedField(field.id, field.default);
            updateField(field.id, field.default);
            setSelectedRoles(field.default.map((role, index) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));
        } else {
            setSelectedRoles(field.value.map((role, index) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));
        }
    },[fieldsLoaded]);

    const handleChange = (selectedOptions) => {
        const rolesExcluded = selectedOptions.map(option => option.value);
        let rolesEnabledEmail = getFieldValue('two_fa_enabled_roles_email');
        let rolesEnabledTotp = getFieldValue('two_fa_enabled_roles_totp');
        let rolesEnabled = rolesEnabledEmail.concat(rolesEnabledTotp);

        let rolesEnabledContainsSelected = rolesEnabled.filter(role => selectedOptions.map(option => option.value).includes(role));
        if (rolesEnabledContainsSelected.length === 0 && selectedOptions.length > 0) {
            showSavedSettingsNotice(__('You have enforced 2FA, but not configured any methods.', 'really-simple-ssl'), 'error');
        } else {
            selectedOptions.forEach(role => {
                if (!rolesEnabled.includes(role.value)) {
                    showSavedSettingsNotice(__('You have enforced 2FA, but not configured any methods for the role: ', 'really-simple-ssl') + role.label, 'error');
                }
            });
        }

        setSelectedRoles(selectedOptions);
        updateField(field.id, rolesExcluded);
        setChangedField(field.id, rolesExcluded);
    };

    const customStyles = {
        multiValue: (provided) => ({
            ...provided,
            borderRadius: '10px',
            backgroundColor: field.id === forcedRoledId ? '#F5CD54' : field.id === optionalRolesId ? '#FDF5DC' : 'default',
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

    const alreadySelected = selectedRoles.map(option => option.value);
    let filteredRoles = [];
    let inRolesInUse = [...alreadySelected, ...otherRoles];

    roles.forEach(function (item, i) {
        if (Array.isArray(inRolesInUse) && inRolesInUse.includes(item.value)) {
            filteredRoles.splice(i, 1);
        } else {
            filteredRoles.push(item);
        }
    });

    return (
        <div style={{marginTop: '5px'}} ref={selectRef}>
            <Select
                isMulti
                options={filteredRoles}
                onChange={handleChange}
                value={selectedRoles}
                menuPosition={"fixed"}
                styles={customStyles}
                isDisabled={isSelectDisabled}
            />
        </div>
    );
};

export default TwoFaRolesDropDown;