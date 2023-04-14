import Icon from "../../utils/Icon";
import {__} from "@wordpress/i18n";
import useVulnerabilityData from "./VulnerabilityData";
import {useEffect, useState} from "react";
import useFields from "../../Settings/FieldsData";

const WPVul = () => {
    const {
        vulnerabilities,
        vulnerabilityScore,
        vulEnabled,
        updates,
        dataLoaded,
        riskNaming,
        vulnerabilityCount,
        capitalizeFirstLetter,
        fetchVulnerabilities
    } = useVulnerabilityData();
    const {fields, fieldAlreadyEnabled} = useFields();
    const [vulnerabilityWord, setVulnerabilityWord] = useState('');
    const [updateWord, setUpdateWord] = useState('');
    const [hardeningWord, setHardeningWord] = useState('');
    const [notEnabledHardeningFields, setNotEnabledHardeningFields] = useState(0);
    useEffect(() => {
        if ( fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            if (!dataLoaded) {
                fetchVulnerabilities();
            }
        }
    }, [fields]);

    useEffect(() => {
        //singular or plural of the word vulnerability
        const v = (vulnerabilities === 1) ? __("vulnerability", "really-simple-ssl") : __("vulnerabilities", "really-simple-ssl");
        setVulnerabilityWord(v);
        const u = (updates === 1) ? __("update", "really-simple-ssl") : __("updates", "really-simple-ssl");
        setUpdateWord(u);
        const h = (notEnabledHardeningFields === 1) ? __("hardening feature", "really-simple-ssl") : __("hardening features", "really-simple-ssl");
        setHardeningWord(h);
    },[vulnerabilities, updates, notEnabledHardeningFields])

    useEffect(() => {
        if (fields.length>0) {
            let notEnabledFields = fields.filter(field => field.recommended);
            notEnabledFields = notEnabledFields.filter(field => field.value !== 1);
            setNotEnabledHardeningFields(notEnabledFields.length);
        }
    },[fields])

    let risks = vulnerabilityCount();
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

        if (score < notEnabledHardeningFields) {
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

    }

    const getStyles = () => {
        let progress = 0;
        let vulScanStatus = 'disabled';
        if (vulScanStatus === 'active') progress = 50;
        if (vulScanStatus === 'paused') progress = 100;

        return Object.assign(
            {},
            {width: progress + "%"},
        );
    }

    function neverScannedYet() {
        return true;
    }

    let gradeClass = neverScannedYet() ? 'inactive' : '?';

    const checkVulActive = () => {
        if (vulEnabled) {
            // iconVulEnabledColor = 'green';
            return (<></>)
        }

        return (
            <>
                <div className="rsssl-details">
                    <div className="rsssl-detail-icon"><Icon name="info" color='yellow'/></div>
                    <div className="rsssl-detail">
                        {__("Enable vulnerability scanning for more information", "really-simple-ssl")}
                    </div>
                </div>
            </>
        )
    }

    const getHighestRiskVulnerability = () => {
        //we have an array of risks the order in which we check is important so c, h, m, l
        let highestRiskVulnerability = {
            name: riskNaming[risks[1].level],
            count: 0
        };

        //we get the highest risk based where c is highest, followed by h and then m and l is lowest
        for (let i = 0; i < risks.length; i++) {
            if (risks[i].level === 'c') {
                highestRiskVulnerability = {
                    name: riskNaming[risks[i].level],
                    count: risks[i].count
                };
                break;
            }
            if (risks[i].level === 'h') {
                highestRiskVulnerability = {
                    name: riskNaming[risks[i].level],
                    count: risks[i].count
                };
            }
            if (risks[i].level === 'm') {
                highestRiskVulnerability = {
                    name: riskNaming[risks[i].level],
                    count: risks[i].count
                };
            }
            if (risks[i].level === 'l') {
                highestRiskVulnerability = {
                    name: riskNaming[risks[i].level],
                    count: risks[i].count
                };
            }
        }
        return highestRiskVulnerability;
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
                    <div className="rsssl-details">
                        <div className="rsssl-detail-icon"><Icon name={icon} color={iconColor}/></div>
                        <div className="rsssl-detail">
                            {__("You have %s %d pending", "really-simple-ssl").replace("%s", updates).replace("%d", updateWord)}
                            <a href={"/wp-admin/update-core.php"}
                               style={linkStyle}>{capitalizeFirstLetter(__('%d', 'really-simple-ssl').replace('%d', updateWord))}</a>
                        </div>
                    </div>
                </>
            )
        } else {
            return (
                <>
                    <div className="rsssl-details">
                        <div className="rsssl-detail-icon"><Icon name={icon} color={iconColor}/></div>
                        <div className="rsssl-detail">
                            {__("You have %s %d pending", "really-simple-ssl").replace("%s", updates).replace("%d", updateWord)}
                        </div>
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
            let highestRiskVulnerability = getHighestRiskVulnerability();
            return (
                <>
                    <div className="rsssl-details">
                        <div className="rsssl-detail-icon"><Icon name={icon} color={iconColor}/></div>
                        <div className="rsssl-detail">
                            <p>{__("You have %s %d", "really-simple-ssl")
                                .replace("%s", vulnerabilities)
                                .replace("%d", vulnerabilityWord)
                            }
                                <a style={linkStyle} href={'#'}
                                   target="_blank">{__('Read more', 'really-simple-ssl')}</a>
                            </p>
                        </div>
                    </div>
                </>
            )
        } else {
            return (
                <>
                    <div className="rsssl-details">
                        <div className="rsssl-detail-icon"><Icon name="circle-check" color='green'/></div>
                        <div className="rsssl-detail">
                            {__("You have %s %d", "really-simple-ssl")
                                .replace("%d", vulnerabilityWord)
                                .replace("%s", vulnerabilities)
                            }
                        </div>
                    </div>
                </>
            )
        }

    }

    const linkStyle = {
        marginLeft: '1em'
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
                    <div className="rsssl-details">
                        <div className="rsssl-detail-icon"><Icon name={icon} color={iconColor}/></div>
                        <div className="rsssl-detail">
                            {__("You have %s open %d", "really-simple-ssl").replace("%s", notEnabledHardeningFields).replace('%d',hardeningWord)}
                        </div>
                    </div>
                </>
            )
        } else {
            return (<>
                <div className="rsssl-details">
                    <div className="rsssl-detail-icon"><Icon name="circle-check" color='green'/></div>
                    <div className="rsssl-detail"><p>{__("Hardening features are configured", "really-simple-ssl")}
                        <a style={linkStyle} href={'#'}
                           target="_blank">{__('What now', 'really-simple-ssl')}?</a></p>
                    </div>
                </div>
            </>)
        }
    }

    return (
        <div className={vulClass}>
            <div className={"rsssl-ssl-test-container " + vulClass}>
                <div className="rsssl-ssl-test ">
                    <div className="rsssl-ssl-test-information">
                        <span>
                            {vulEnabled ? <Icon color={iconVulColor} size={20} name="radar-duotone"></Icon> : <Icon size={20}  color={iconVulEnabledColor} name="satellite-dish-duotone"></Icon>}

                        </span>
                        <span>
                            <h2 className={"rsssl-number"}>{vulEnabled ? vulnerabilities : '-'}</h2>
                        <span
                            className={"rsssl-badge " + badgeVulStyle}>{capitalizeFirstLetter(vulnerabilityWord)}</span>
                        </span>
                    </div>
                    <div className={"rsssl-ssl-test-information"}>
                        <span>{ updates ? <Icon size={20} color={iconUpdateColor} name="rotate-exclamation-light"></Icon> : <Icon size={20} color={'#000'} name="rotate-light"></Icon>}
                        </span>
                        <span>
                            <h2 className={"rsssl-number"}>{updates}</h2>
                        <span className={"rsssl-badge " + badgeUpdateStyle}>{capitalizeFirstLetter(updateWord)}</span>
                        </span>
                    </div>
                </div>

            </div>
            {dataLoaded? <>
                {checknotEnabledHardeningFields()}
                {checkVulActive()}
                {checkVul()}
                {checkUpdates()}</>:<>
                <div className="rsssl-learningmode-placeholder">
                <div></div><div></div><div></div><div></div>
                </div>
                </>}
            <div className="rsssl-details">
            </div>


        </div>
    )
}

export default WPVul;