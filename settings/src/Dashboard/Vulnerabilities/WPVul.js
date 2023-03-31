import Icon from "../../utils/Icon";
import {__} from "@wordpress/i18n";
import useVulnerabilityData from "./VulnerabilityData";
import {useEffect} from "react";
import useFields from "../../Settings/FieldsData";
import Help from "../../Settings/Help";

const WPVul = (props) => {
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
    const {fields} = useFields();
    let featuredFields = fields.filter(field => field.new_features_block);
    useEffect(() => {
        const run = async () => {
            await fetchVulnerabilities();
        }
        run();
    }, []);

    //singular or plural of the word vulnerability
    const vulnerabilityWord = (vulnerabilities.length === 1) ? __("vulnerability", "really-simple-ssl") : __("vulnerabilities", "really-simple-ssl");
    const updateWord = (updates === 1) ? __("update", "really-simple-ssl") : __("updates", "really-simple-ssl");

    let risks = vulnerabilityCount();
    const hardening = featuredFields.filter(field => field.value === 0);
    let vulClass = 'rsssl-inactive';
    let badgeVulStyle = 'rsp-default';
    let badgeUpdateStyle = 'rsp-default';
    let iconVulColor = 'black';
    let iconUpdateColor = 'black';
    if (vulEnabled) {
        //now we calculate the score
        let score = vulnerabilityScore();
        //we create correct badge style
        if (score >= 5) {
            badgeVulStyle = 'rsp-critical';
            iconVulColor =  'red';
        } else if (score < 4 && score > 0) {
            badgeVulStyle = 'rsp-low';
            iconVulColor = 'yellow';
        }

        if (updates >= 5) {
            badgeUpdateStyle = 'rsp-critical';
            iconUpdateColor =  'red';
        } else if (score < 5 && score > 0) {
            badgeUpdateStyle = 'rsp-low';
            iconUpdateColor = 'yellow';
        }

        if (score < hardening.length) {
            score = hardening.length;
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
            return (<></>)
        }

        return (
            <>
                <div className="rsssl-details">
                    <div className="rsssl-detail-icon"><Icon name="info" color='yellow'/></div>
                    <div className="rsssl-detail">
                        {capitalizeFirstLetter(__("Enable vulnerability scanning for more information", "really-simple-ssl"))}
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
                            {capitalizeFirstLetter(__("You have %s %word pending", "really-simple-ssl").replace("%s", updates).replace("%word", updateWord))}
                            <a href={"/wp-admin/update-core.php"}
                               style={linkStyle}>{capitalizeFirstLetter(__('%word', 'really-simple-ssl').replace('%word', updateWord))}</a>
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
                            {capitalizeFirstLetter(__("You have %s updates pending", "really-simple-ssl").replace("%s", updates).replace("%word", updateWord))}
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
                            <p>{capitalizeFirstLetter(__("You have %s %word", "really-simple-ssl")
                                .replace("%s", vulnerabilities)
                                .replace("%word", vulnerabilityWord))
                            }
                                <a style={linkStyle} href={'#'}
                                   target="_blank">{capitalizeFirstLetter(__('Read more', 'really-simple-ssl'))}</a>
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
                            {capitalizeFirstLetter(__("You have %s %word", "really-simple-ssl").replace("%s", vulnerabilities, "%word", vulnerabilityWord))}
                        </div>
                    </div>
                </>
            )
        }

    }

    const linkStyle = {
        marginLeft: '1em'
    }
    const checkHardening = () => {
        //
        if (hardening.length) {
            let icon = 'circle-check';
            let iconColor = 'green';
            if (hardening.length > 0) {
                icon = 'info';
                iconColor = 'yellow';
            }
            if (hardening.length >= 5) {
                icon = 'circle-times';
                iconColor = 'red';
            }
            return (
                <>
                    <div className="rsssl-details">
                        <div className="rsssl-detail-icon"><Icon name={icon} color={iconColor}/></div>
                        <div className="rsssl-detail">
                            {capitalizeFirstLetter(__("You have %s open hardening features", "really-simple-ssl").replace("%s", hardening.length))}
                        </div>
                    </div>
                </>
            )
        } else {
            return (<>
                <div className="rsssl-details">
                    <div className="rsssl-detail-icon"><Icon name="circle-check" color='green'/></div>
                    <div className="rsssl-detail"><p>{capitalizeFirstLetter(__("Hardening features are configured", "really-simple-ssl"))}
                        <a style={linkStyle} href={'#'}
                           target="_blank">{capitalizeFirstLetter(__('What now', 'really-simple-ssl'))}?</a></p>
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
                            {vulEnabled ? <Icon color={iconVulColor} name="radar-duotone"></Icon> : <Icon color={'#000'} name="satellite-dish"></Icon>}

                        </span>
                        <span>
                            <h2 className={"rsssl-number"}>{vulEnabled ? vulnerabilities : '-'}</h2>
                        <span
                            className={"rsssl-badge " + badgeVulStyle}>{capitalizeFirstLetter(vulnerabilityWord)}</span>
                        </span>
                    </div>
                    <div className={"rsssl-ssl-test-information"}>
                        <span>{ updates ? <Icon color={iconUpdateColor} name="rotate-exclamation-light"></Icon> : <Icon color={'#000'} name="rotate-light"></Icon>}
                        </span>
                        <span>
                            <h2 className={"rsssl-number"}>{updates}</h2>
                        <span className={"rsssl-badge " + badgeUpdateStyle}>{capitalizeFirstLetter(updateWord)}</span>
                        </span>
                    </div>
                </div>

            </div>
            {dataLoaded? <>
                {checkHardening()}
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