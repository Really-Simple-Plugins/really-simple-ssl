import Icon from "../../utils/Icon";
import {__, _n} from "@wordpress/i18n";
import {useEffect, useState} from "react";
import useFields from "../../Settings/FieldsData";
import useRiskData from "../../Settings/RiskConfiguration/RiskData";

const Vulnerabilities = () => {
    const {
        vulnerabilities,
        vulnerabilityScore,
        updates,
        dataLoaded,
        fetchVulnerabilities
    } = useRiskData();
    const {fields, getFieldValue} = useFields();
    const [vulnerabilityWord, setVulnerabilityWord] = useState('');
    const [updateWord, setUpdateWord] = useState('');
    const [updateWordCapitalized, setUpdateWordCapitalized] = useState('');
    const [vulnerabilityWordCapitalized, setVulnerabilityWordCapitalized] = useState('');
    const [updateString, setUpdateString] = useState('');
    const [hardeningWord, setHardeningWord] = useState('');
    const [notEnabledHardeningFields, setNotEnabledHardeningFields] = useState(0);
    const [vulEnabled, setVulEnabled] = useState(false);
    useEffect(() => {
        if (getFieldValue('enable_vulnerability_scanner')==1) {
            setVulEnabled(true);
        }
    }, [fields]);

    useEffect(() => {
        if (!dataLoaded) {
            fetchVulnerabilities();
        }
    }, [vulEnabled]);

    useEffect(() => {
        //singular or plural of the word vulnerability
        const v = (vulnerabilities === 1) ? __("vulnerability", "really-simple-ssl") : __("vulnerabilities", "really-simple-ssl");
        setVulnerabilityWordCapitalized(v.charAt(0).toUpperCase() + v.slice(1));
        setVulnerabilityWord(v);
        const u = (updates === 1) ? __("update", "really-simple-ssl") : __("updates", "really-simple-ssl");
        const s = _n('You have %s update pending', 'You have %s updates pending', updates, 'really-simple-ssl').replace('%s', updates);
        setUpdateWord(u);
        setUpdateWordCapitalized(u.charAt(0).toUpperCase() + u.slice(1));
        setUpdateString(s);
        const h = (notEnabledHardeningFields === 1) ? __("hardening feature", "really-simple-ssl") : __("hardening features", "really-simple-ssl");
        setHardeningWord(h);
    },[vulnerabilities, updates, notEnabledHardeningFields])

    useEffect(() => {
        if (fields.length>0) {
            let notEnabledFields = fields.filter(field => field.recommended);
            //we need to filter enabled fields, but also disabled fields, because these are not enabled, but set by another method, so disabled
            notEnabledFields = notEnabledFields.filter(field => field.value !== 1 && field.disabled !== true);
            setNotEnabledHardeningFields(notEnabledFields.length);
        }
    },[fields])

    let vulClass = 'rsssl-inactive';
    let badgeVulStyle = vulEnabled?'rsp-success':'rsp-default';
    let badgeUpdateStyle = 'rsp-success';
    let iconVulColor = 'green';
    let iconVulEnabledColor = 'red';
    let iconUpdateColor = 'black';
    if (vulEnabled || notEnabledHardeningFields > 0 || updates > 0) {
        //now we calculate the score
        let score = vulnerabilityScore();
        //we create correct badge style
        if (score >= 5) {
            badgeVulStyle = 'rsp-critical';
            iconVulColor =  'red';
        } else if (score < 4 && score > 0) {
            badgeVulStyle = 'rsp-medium';
            iconVulColor = 'yellow';
        }

        if (updates >= 5) {
            badgeUpdateStyle = 'rsp-critical';
            iconUpdateColor =  'red';
        } else if (score < 5 && score > 0) {
            badgeUpdateStyle = 'rsp-medium';
            iconUpdateColor = 'yellow';
        }

        if ( score < notEnabledHardeningFields ) {
            score = notEnabledHardeningFields;
        }

        if (score < updates) {
            score = updates;
        }

        if (score === 0) {
            vulClass = 'rsssl-success';
        } else if (score < 5) {
            vulClass = 'rsssl-warning';
        } else {
            vulClass = 'rsssl-error';
        }

        // if (!vulEnabled) vulClass = "rsssl-inactive";

    }

    const checkVulActive = () => {
        if (vulEnabled) {
            // iconVulEnabledColor = 'green';
            return (<></>)
        }

        return (
            <>
                <div className="rsssl-hardening-list-item">
                    <Icon name="info" color='yellow'/>
                    <p className={'rsssl-hardening-list-item-text'}> {__("Enable vulnerability detection", "really-simple-ssl")}</p>
                    <a href="#settings/vulnerabilities">{__("Enable", "really-simple-ssl")}</a>
                </div>
            </>
        )
    }

    const checkUpdates = () => {
        let icon = 'circle-check';
        let iconColor = 'green';
        if (updates > 0) {
            icon = 'info';
            iconColor = 'yellow';
        }
        if (updates >= 5) {
            icon = 'circle-times';
            iconColor = 'red';
        }

        if (updates) {
            return (
                <>
                    <div className="rsssl-hardening-list-item">
                        <Icon name={icon} color={iconColor}/>
                        <p className="rsssl-hardening-list-item-text">
                            {updateString}
                        </p>
                        <a href={rsssl_settings.plugins_url + "?plugin_status=upgrade"}
                           style={linkStyle}>{updateWordCapitalized}</a>
                    </div>

                </>
            )
        } else {
            return (
                <>
                    <div className="rsssl-hardening-list-item">
                        <Icon name={icon} color={iconColor}/>
                        <p className="rsssl-hardening-list-item-text">
                            {updateString}
                        </p>
                    </div>
                </>
            )

        }
    }

    const checkVul = () => {
        let icon = 'circle-check';
        let iconColor = 'green';
        if (vulnerabilityScore() > 0) {
            icon = 'info';
            iconColor = 'yellow';
        }
        if (vulnerabilityScore() >= 5) {
            icon = 'circle-times';
            iconColor = 'red';
        }
        if (!vulEnabled) {
            return (
                <>
                </>
            )
        }

        if (vulnerabilities) {
            return (
                <>
                    <div className="rsssl-hardening-list-item">
                        <Icon name={icon} color={iconColor}/>
                        <p className="rsssl-hardening-list-item-text">
                            {__("You have %s %d", "really-simple-ssl")
                                .replace("%s", vulnerabilities)
                                .replace("%d", vulnerabilityWord)
                            }
                        </p>
                        <a style={linkStyle} href={'#settings/vulnerabilities'}>{__('Learn more', 'really-simple-ssl')}</a>
                    </div>
                </>
            )
        } else {
            return (
                <>
                    <div className="rsssl-hardening-list-item">
                       <Icon name="circle-check" color='green'/>
                        <p className="rsssl-hardening-list-item-text">
                            {__("You have %s %d", "really-simple-ssl")
                                .replace("%d", vulnerabilityWord)
                                .replace("%s", vulnerabilities)
                            }
                        </p>
                    </div>
                </>
            )
        }

    }

    const linkStyle = {
        marginLeft: '0.3em'
    }
    const checknotEnabledHardeningFields = () => {
        if (notEnabledHardeningFields) {
            let icon = 'circle-check';
            let iconColor = 'green';
            if (notEnabledHardeningFields > 0) {
                icon = 'info';
                iconColor = 'yellow';
            }
            if (notEnabledHardeningFields >= 5) {
                icon = 'circle-times';
                iconColor = 'red';
            }
            return (
                <>
                    <div className="rsssl-hardening-list-item">
                        <Icon name={icon} color={iconColor}/>

                            <p className={"rsssl-hardening-list-item-text"}>
                                {__("You have %s open %d", "really-simple-ssl").replace("%s", notEnabledHardeningFields).replace('%d',hardeningWord)}
                            </p>
                            <a href="#settings/hardening">{__("Settings", "really-simple-ssl")}</a>
                    </div>
                </>
            )
        } else {
            return (<>
                <div className="rsssl-hardening-list-item">
                    <Icon name="circle-check" color='green'/>
                    <p className={"rsssl-hardening-list-item-text"}>{__("Hardening features are configured", "really-simple-ssl")}</p>
                </div>
            </>)
        }
    }

    return (
        <>
            {dataLoaded ?
            <div className={'rsssl-hardening'}>
                <div className="rsssl-gridblock-progress" ></div>
                <div className={"rsssl-hardening-select "  + vulClass}>
                    <div className="rsssl-hardening-select-item">
                        {vulEnabled ? <Icon color={iconVulColor} size={23} name="radar-duotone"></Icon> : <Icon size={23}  color={iconVulEnabledColor} name="satellite-dish-duotone"></Icon>}
                        <h2>{vulEnabled ? vulnerabilities : '?'}</h2>
                        <span className={"rsssl-badge " + badgeVulStyle}>{vulnerabilityWordCapitalized}</span>
                    </div>
                    <div className="rsssl-hardening-select-item">
                        { updates ? <Icon size={23} color={iconUpdateColor} name="rotate-exclamation-light"></Icon> : <Icon size={23} color={'black'} name="rotate-light"></Icon>}
                        <h2>{updates}</h2>
                        <span className={"rsssl-badge " + badgeUpdateStyle}>{updateWordCapitalized}</span>
                    </div>
                </div>
                <div className="rsssl-hardening-list">
                    {checknotEnabledHardeningFields()}
                    {checkVulActive()}
                    {checkVul()}
                    {checkUpdates()}
                </div>
            </div>
                : <div className="rsssl-hardening">
                    <div className="rsssl-gridblock-progress" ></div>
                    <div className="rsssl-hardening-select">
                        <div className="rsssl-hardening-select-item">
                            <Icon size={23} color={'grey'} name="radar-duotone"></Icon>
                            <h2>0</h2>
                            <span className={"rsssl-badge rsp-default"}>{vulnerabilityWordCapitalized}</span>
                        </div>
                        <div className="rsssl-hardening-select-item">
                            <Icon size={23} color={'grey'} name="rotate-exclamation-light"></Icon>
                            <h2>0</h2>
                            <span className={"rsssl-badge rsp-default"}>{updateWordCapitalized}</span>
                        </div>
                    </div>
                    <div className="rsssl-hardening-list">
                        <div className="rsssl-hardening-list-item">
                            <Icon color={'grey'} name="circle-check"></Icon>
                            <p className={"rsssl-hardening-list-item-text"}>{__("Loading...", "really-simple-ssl")}</p>
                        </div>
                        <div className="rsssl-hardening-list-item">
                            <Icon color={'grey'} name="circle-check"></Icon>
                            <p className={"rsssl-hardening-list-item-text"}>{__("Loading...", "really-simple-ssl")}</p>
                        </div>
                        <div className="rsssl-hardening-list-item">
                            <Icon color={'grey'} name="circle-check"></Icon>
                            <p className={"rsssl-hardening-list-item-text"}>{__("Loading...", "really-simple-ssl")}</p>
                        </div>
                    </div>
                </div>
            }
        </>
    )
}

export default Vulnerabilities;
