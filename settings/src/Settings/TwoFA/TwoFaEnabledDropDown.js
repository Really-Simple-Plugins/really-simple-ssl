import {useRef, useEffect, useState} from '@wordpress/element';
import Select from 'react-select';
import useFields from "../FieldsData";
import useRolesData from './RolesStore';
import hoverTooltip from "../../utils/hoverTooltip";
import {__} from "@wordpress/i18n";

/**
 * TwoFaEnabledDropDown component represents a dropdown select for excluding roles
 * from two-factor authentication email.
 */
const TwoFaEnabledDropDown = (props) => {
    const {fetchRoles, roles, rolesLoaded} = useRolesData();
    const [selectedRoles, setSelectedRoles] = useState([]);
    const [otherRoles, setOtherRoles] = useState([]);
    const { updateField, getFieldValue, setChangedField, getField, fieldsLoaded, saveFields } = useFields();
    const selectRef = useRef(null);

    const loginProtectionEnabled = getFieldValue('login_protection_enabled');

    let disabledSelectPropBoolean = (props.disabled === true);
    let disabledSelectViaFieldConfig = (props.field.disabled === true);

    let selectDisabled = (
        disabledSelectViaFieldConfig
        || disabledSelectPropBoolean
    );

    let tooltipText = '';
    let emptyValues = [undefined, null, ''];

    if (selectDisabled
        && props.field.hasOwnProperty('disabledTooltipText')
        && !emptyValues.includes(props.field.disabledTooltipHoverText)
    ) {
        tooltipText = props.field.disabledTooltipHoverText;
    }

    hoverTooltip(
        selectRef,
        (selectDisabled && (tooltipText !== '')),
        tooltipText
    );

    useEffect(() => {
        if (getFieldValue('login_protection_enabled') === 1 && props.field.id === 'two_fa_enabled_roles_totp') {
            setChangedField(props.field.id, props.field.value);
            saveFields(true, false);
        }
    }, [loginProtectionEnabled]);

    useEffect(() => {
        if (!rolesLoaded) {
            fetchRoles(props.field.id);
        }
    }, [rolesLoaded]);

    useEffect(() => {
        if (props.field.id) {
            let otherField = getField(props.field.id);
            let roles = Array.isArray(otherField.value) ? otherField.value : [];
            setOtherRoles(roles);
        }
    }, [selectedRoles, getField(props.field.id)]);

    useEffect(() => {
        if (props.field.id) {
            let otherField = getField(props.field.id);
            let roles = Array.isArray(otherField.value) ? otherField.value : [];
            setSelectedRoles(roles.map((role) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));
        }

        if (!props.field.value) {
            setChangedField(props.field.id, props.field.default);
            updateField(props.field.id, props.field.default);
            setSelectedRoles(props.field.default.map((role) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));
        }
    }, [fieldsLoaded]);

    const handleChange = (selectedOptions) => {
        const rolesExcluded = selectedOptions.map(option => option.value);
        setSelectedRoles(selectedOptions);
        updateField(props.field.id, rolesExcluded);
        setChangedField(props.field.id, rolesExcluded);
    };

    const customStyles = {
        multiValue: (provided) => ({
            ...provided,
            borderRadius: '10px',
            backgroundColor: '#F5CD54',
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
            zIndex: 30,
        }),
    };

    const alreadySelected = selectedRoles.map(option => option.value);
    let filteredRoles = [];
    let inRolesInUse = [...alreadySelected, ...otherRoles];

    roles.forEach((item) => {
        if (!inRolesInUse.includes(item.value)) {
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
                isDisabled={selectDisabled}
            />
            {! loginProtectionEnabled &&
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