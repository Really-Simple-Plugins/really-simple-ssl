import { useState, useEffect } from '@wordpress/element';
import Select from 'react-select';
import useFields from "./FieldsData";
import useRolesData from './TwoFA/RolesStore';
import {__} from "@wordpress/i18n";
import './TwoFA/select.scss';
/**
 * RolesDropDown component represents a dropdown select for excluding roles
 * from two-factor authentication email.
 * @param {object} field - The field object containing information about the field.
 */
const RolesDropDown = ({ field }) => {
    const {fetchRoles, roles, rolesLoaded} = useRolesData();
    const [selectedRoles, setSelectedRoles] = useState([]);
    const [rolesEnabled, setRolesEnabled] = useState(false);

    // Custom hook to manage form fields
    const { updateField, setChangedField, fieldsLoaded,getFieldValue  } = useFields();
    let enabled = true;


    useEffect(() => {
        if (!rolesLoaded) {
            fetchRoles(field.id);
        }
    }, [rolesLoaded]);

    useEffect(() => {
        if ( !field.value ) {
            setChangedField(field.id, field.default);
            updateField(field.id, field.default);
            setSelectedRoles( field.default.map((role, index) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));
        }
        setSelectedRoles( field.value.map((role, index) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) })));
    },[fieldsLoaded]);


    //if the field enforce_frequent_password_change is enabled, then the field is enabled
    useEffect(() => {
        setRolesEnabled(getFieldValue('enforce_frequent_password_change'));
    },[getFieldValue('enforce_frequent_password_change')]);

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
        menuList: (provided) => ({
            ...provided,
            height: '125px',
            zIndex: 999
        }),
    };

    return (
        <div style={{marginTop: '5px'}}>
            <Select
                isMulti
                options={roles}
                onChange={handleChange}
                value={selectedRoles}
                menuPosition={"fixed"}
                styles={customStyles}
                isDisabled={!rolesEnabled}
            />
            {! enabled &&
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate Two-Factor Authentication to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            }
        </div>
    );
};

export default RolesDropDown;