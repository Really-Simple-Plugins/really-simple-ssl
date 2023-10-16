import Hyperlink from "../utils/Hyperlink";
import getAnchor from "../utils/getAnchor";
import {__} from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import useFields from "../Settings/FieldsData";
import useMenu from "../Menu/MenuData";
import useLicense from "./License/LicenseData";
import filterData from "./FilterData";
import {useEffect, useState} from '@wordpress/element';

/**
 * Render a grouped block of settings
 */
const SettingsGroup = (props) => {
    const {fields} = useFields();
    const {selectedFilter, setSelectedFilter} = filterData();
    const {licenseStatus} = useLicense();
    const {selectedSubMenuItem, subMenu} = useMenu();
    const [Field, setField] = useState(null);

    useEffect(() => {
        import("./Field").then(({default: Field}) => {
            setField(() => Field);
        });

    }, []);


    let upgrade = 'https://really-simple-ssl.com/pro/?mtm_campaign=fallback&mtm_source=free&mtm_content=upgrade';

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
    //first, set the selected menu item as activate group, so we have a default in case there are no groups
    for (const item of subMenu.menu_items) {
        if (item.id === selectedSubMenuItem) {
            activeGroup = item;
        } else if (item.menu_items) {
            activeGroup = item.menu_items.filter(menuItem => menuItem.id === selectedSubMenuItem)[0];
        }
        if (activeGroup) {
            break;
        }
    }

    //now check if we have actual groups
    for (const item of subMenu.menu_items) {
        if (item.id === selectedSubMenuItem && item.hasOwnProperty('groups')) {
            let currentGroup = item.groups.filter(group => group.id === props.group);
            if (currentGroup.length > 0) {
                activeGroup = currentGroup[0];
            }
        }
    }

    if (!activeGroup) {
        return (<></>);
    }
    let msg = activeGroup.premium_text ? activeGroup.premium_text : __("Learn more about %sPremium%s", "really-simple-ssl");
    if (rsssl_settings.pro_plugin_active) {
        if (licenseStatus === 'empty' || licenseStatus === 'deactivated') {
            msg = rsssl_settings.messageInactive;
        } else {
            msg = rsssl_settings.messageInvalid;
        }
        if (rsssl_settings.pro_incompatible) {
            msg = __("You are using an incompatible version of Really Simple SSL pro. Please update to the latest version.", "really-simple-ssl");
        }
    }

    let disabled = (licenseStatus !== 'valid' || rsssl_settings.pro_incompatible) && activeGroup.premium;
    //if a feature can only be used on networkwide or single site setups, pass that info here.
    let networkwide_error = !rsssl_settings.networkwide_active && activeGroup.networkwide_required;
    upgrade = activeGroup.upgrade ? activeGroup.upgrade : upgrade;
    let helplinkText = activeGroup.helpLink_text ? activeGroup.helpLink_text : __("Instructions", "really-simple-ssl");
    let anchor = getAnchor('main');
    let disabledClass = disabled || networkwide_error ? 'rsssl-disabled' : '';
    const filterId = "rsssl-group-filter-" + activeGroup.id;
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
                                        key={option.id}
                                        value={option.id}
                                    >
                                        {option.title}
                                    </option>
                                ))}
                            </select>
                    </div>
                )}
                {activeGroup.helpLink && anchor !== 'letsencrypt' && (
                    <div className="rsssl-grid-item-controls">
                        <Hyperlink
                            target="_blank"
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
                {activeGroup.intro &&
                    <div className="rsssl-settings-block-intro">{activeGroup.intro}</div>
                }
                {Field && selectedFields.map((field, i) =>
                        <Field key={"selectedFields-" + i} index={i} field={field} fields={selectedFields}/>
                )}
            </div>
            {disabled && !networkwide_error && <div className="rsssl-locked">
                <div className="rsssl-locked-overlay">
                    <span className="rsssl-task-status rsssl-premium">{__("Upgrade", "really-simple-ssl")}</span>
                    <span>
                        {rsssl_settings.pro_plugin_active && <span>{msg}&nbsp;<a className="rsssl-locked-link"
                                                                                 href="#settings/license">{__("Check license", "really-simple-ssl")}</a></span>}
                        {!rsssl_settings.pro_plugin_active && <Hyperlink target="_blank" text={msg} url={upgrade}/>}
                    </span>
                </div>
            </div>}
            {networkwide_error && <div className="rsssl-locked">
                <div className="rsssl-locked-overlay">
                    <span
                        className="rsssl-task-status rsssl-warning">{__("Network feature", "really-simple-ssl")}</span>
                    <span>{__("This feature is only available networkwide.", "really-simple-ssl")}<Hyperlink
                        target="_blank" text={__("Network settings", "really-simple-ssl")}
                        url={rsssl_settings.network_link}/></span>
                </div>
            </div>}

        </div>
    )
}

export default SettingsGroup
