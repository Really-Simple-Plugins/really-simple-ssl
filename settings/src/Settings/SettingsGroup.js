import Hyperlink from "../utils/Hyperlink";
import getAnchor from "../utils/getAnchor";
import {__} from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import useFields from "../Settings/FieldsData";
import useMenu from "../Menu/MenuData";
import useLicense from "./License/LicenseData";
import filterData from "./FilterData";
import {useEffect, useState} from '@wordpress/element';
import ErrorBoundary from "../utils/ErrorBoundary";
import PremiumOverlay from "./PremiumOverlay";

/**
 * Render a grouped block of settings
 */
const SettingsGroup = (props) => {

    const {fields} = useFields();
    const {selectedFilter, setSelectedFilter} = filterData();
    const {licenseStatus} = useLicense();
    const {selectedSubMenuItem, subMenu} = useMenu();
    const [Field, setField] = useState(null);
    const [updatedIntro, setUpdatedIntro] = useState(null);

    useEffect(() => {
        import("./Field").then(({default: Field}) => {
            setField(() => Field);
        });
        if (activeGroup && activeGroup.intro && typeof activeGroup.intro === 'object') {
            setUpdatedIntro(activeGroup.intro[selectedFilter[filterId]]);
        }

    }, [selectedFilter]);

    /*
    * On reset of LE, send this info to the back-end, and redirect to the first step.
    * reload to ensure that.
    */
    const handleLetsEncryptReset = (e) => {
        e.preventDefault();
        rsssl_api.runLetsEncryptTest('reset').then((response) => {
            window.location.href = window.location.href.replace(/#letsencrypt.*/, '&r=' + (+new Date()) + '#letsencrypt/le-system-status');
        });
    }

    let selectedFields = [];
    //get all fields with group_id props.group_id
    for (const selectedField of fields) {
        if (selectedField.group_id === props.group) {
            selectedFields.push(selectedField);
        }
    }

    let activeGroup;
    for (const item of subMenu.menu_items) {
        if (item.id === selectedSubMenuItem && item.hasOwnProperty('groups')) {
            for (const group of item.groups) {
                if (group.group_id === props.group) {
                    activeGroup = group;
                    break;
                }
            }
        }
        if (activeGroup) break; // Exit the loop once a match is found.
    }

// If activeGroup is not set, then default to the parent menu item.
    if (!activeGroup) {
        for (const item of subMenu.menu_items) {
            if (item.id === selectedSubMenuItem) {
                activeGroup = item;
                break;
            }
            // Handle the case where there are nested menu items.
            if (item.menu_items) {
                const nestedItem = item.menu_items.find(menuItem => menuItem.id === selectedSubMenuItem);
                if (nestedItem) {
                    activeGroup = nestedItem;
                    break;
                }
            }
        }
    }

    // Check for nested groups in the activeGroup.
    if (activeGroup && activeGroup.groups) {
        const nestedGroup = activeGroup.groups.find(group => group.group_id === props.group);
        if (nestedGroup) {
            activeGroup = nestedGroup;
        } else {
            const nestedGroup = activeGroup.groups.find(group => group.group_id === props.group);

        }
    }

    let disabled = licenseStatus !== 'valid' && activeGroup.premium;
    //if a feature can only be used on networkwide or single site setups, pass that info here.
    let networkwide_error = !rsssl_settings.networkwide_active && activeGroup.networkwide_required;
    let helplinkText = activeGroup.helpLink_text ? activeGroup.helpLink_text : __("Instructions", "really-simple-ssl");
    let anchor = getAnchor('main');
    let disabledClass = disabled || networkwide_error ? 'rsssl-disabled' : '';
    const filterId = "rsssl-group-filter-" + activeGroup.id;
    //filter out all fields that are not visible
    selectedFields = selectedFields.filter((field) => {
        if (field.hasOwnProperty('visible')) {
            return field.visible;
        }
        return true;
    });
    //if there are no visible fields, return null
    if (selectedFields.length === 0) {
        return null;
    }
    return (
        <div className={"rsssl-grid-item rsssl-" + activeGroup.id + ' ' + disabledClass}>
            {activeGroup.title && <div className="rsssl-grid-item-header">
                <h3 className="rsssl-h4">{activeGroup.title}</h3>
                {activeGroup.groupFilter && (
                        <div className="rsssl-grid-item-controls">
                            <select
                                className="rsssl-group-filter"
                                id={filterId}
                                name={filterId}
                                value={selectedFilter[filterId]}
                                onChange={(e) => {
                                    const selectedValue = e.target.value;
                                    setSelectedFilter(selectedValue, filterId);
                                }}
                            >
                                {activeGroup.groupFilter.options.map((option) => (
                                    //if the value is equal to the selected value, set it as selected
                                    <option
                                        key={'option-'+option.id}
                                        value={option.id}
                                    >
                                        {option.title}
                                    </option>
                                ))}
                            </select>
                    </div>
                )}
                {!activeGroup.groupFilter && activeGroup.helpLink && anchor !== 'letsencrypt' && (
                    <div className="rsssl-grid-item-controls">
                        <Hyperlink
                            target="_blank"
                            rel="noopener noreferrer"
                            className="rsssl-helplink"
                            text={helplinkText}
                            url={activeGroup.helpLink}
                        />
                    </div>
                )}
                {anchor === 'letsencrypt' && <div className="rsssl-grid-item-controls">
                    <a href="#" className="rsssl-helplink"
                       onClick={(e) => handleLetsEncryptReset(e)}>{__("Reset Let's Encrypt", "really-simple-ssl")}</a>
                </div>}
            </div>}
            <div className="rsssl-grid-item-content">
                {(activeGroup.intro && typeof activeGroup.intro === 'string') && <ErrorBoundary fallback={"Could not load group intro"}>
                    {(activeGroup.intro && typeof activeGroup.intro === 'string') && <div className="rsssl-settings-block-intro">{activeGroup.intro}</div>}
                    {(activeGroup.intro &&  typeof activeGroup.intro === 'object') && <div className="rsssl-settings-block-intro">{updatedIntro}</div>}
                </ErrorBoundary>}

                {Field && selectedFields.map((field, i) =>
                        <Field key={"selectedFields-" + i} index={i} field={field} fields={selectedFields}/>
                )}
            </div>
            {disabled && !networkwide_error && <PremiumOverlay
                msg={activeGroup.premium_text}
                title={activeGroup.premium_title ? activeGroup.premium_title : activeGroup.title}
                upgrade={activeGroup.upgrade}
                url={activeGroup.upgrade}
            />}

            {networkwide_error && <div className="rsssl-locked">
                <div className="rsssl-locked-overlay">
                    <span
                        className="rsssl-task-status rsssl-warning">{__("Network feature", "really-simple-ssl")}</span>
                    <span>{__("This feature is only available networkwide.", "really-simple-ssl")}<Hyperlink
                        target="_blank" rel="noopener noreferrer" text={__("Network settings", "really-simple-ssl")}
                        url={rsssl_settings.network_link}/></span>
                </div>
            </div>}

        </div>
    )
}

export default SettingsGroup
