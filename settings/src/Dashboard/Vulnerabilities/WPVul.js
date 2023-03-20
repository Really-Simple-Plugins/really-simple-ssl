import Icon from "../../utils/Icon";
import {__} from "@wordpress/i18n";
import useWPVul from "./WPVulData";
import {useEffect} from "react";
import useFields from "../../Settings/FieldsData";

const WPVul = (props) => {
    const {
        vulnerabilities,
        vulnerabilityScore,
        vulEnabled,
        updates,
        dataLoaded,
        vulnerabilityCount,
        fetchVulnerabilities
    } = useWPVul();
    const {fields, fieldsLoaded} = useFields();
    let featuredFields = fields.filter(field => field.new_features_block);
    useEffect(() => {
        fetchVulnerabilities().then(r => {
        });
    }, []);


    if (!dataLoaded) {
        //we do not have the data yet, so we return null
        return null;
    }
    let risks = vulnerabilityCount();
    const hardening = featuredFields.filter(field => field.value === 0);
    let vulClass = 'rsssl-inactive';
    let badgeVulStyle = 'rsp-default';
    let badgeUpdateStyle = 'rsp-default';
    if (vulEnabled) {
        //now we calculate the score
        let score = vulnerabilityScore();
        //we create correct badge style
        if (score >= 5) {
            badgeVulStyle = 'rsp-critical';
        } else if (score < 4 && score > 0) {
            badgeVulStyle = 'rsp-low';
        }

        if (updates > 0) {
            badgeUpdateStyle = 'rsp-low';
        } else if (score > 5) {
            badgeUpdateStyle = 'rsp-critical';
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
                        {__("Enable vulnerability scanning for more information", "really-simple-ssl")}
                    </div>
                </div>
            </>
        )
    }

    const getHighestRiskVulnerability = () => {
        //we have an array of risks the order in which we check is important so c, h, m, l
        let highestRiskVulnerability = {
            name: 'critical',
            count: 0
        };
        //we loop through the risks
        for (let i = 0; i < risks.length; i++) {
            //if we have a higher risk, we set the highest risk to this risk
            if (risks[i].level === 'c') {
                risks[i].name = __('critical', 'really-simple-ssl');
                highestRiskVulnerability = risks[i];
                break;
            } else if (risks[i].level === 'h') {
                risks[i].name = __('high', 'really-simple-ssl');
                highestRiskVulnerability = risks[i];
                break;
            } else if (risks[i].level === 'm') {
                risks[i].name = __('medium', 'really-simple-ssl');
                highestRiskVulnerability = risks[i];
                break;
            } else if (risks[i].level === 'l') {
                risks[i].name = __('low', 'really-simple-ssl');
                highestRiskVulnerability = risks[i];
                break;
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
        if (updates > 5) {
            icon = 'circle-times';
            iconColor = 'red';
        }
        if (updates) {

            return (
                <>
                    <div className="rsssl-details">
                        <div className="rsssl-detail-icon"><Icon name={icon} color={iconColor}/></div>
                        <div className="rsssl-detail">
                            {__("You have %s updates pending", "really-simple-ssl").replace("%s", updates)}
                            <a href={"/wp-admin/update-core.php"} style={linkStyle}>{__('Update now', 'really-simple-ssl')}</a>
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
                            {__("You have %s updates pending", "really-simple-ssl").replace("%s", updates)}
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
        if(!vulEnabled) {
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
                            <p>{__("You have %s %name vulnerabilities", "really-simple-ssl").replace("%s", highestRiskVulnerability.count)
                                .replace("%name", highestRiskVulnerability.name)}
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
                            {__("You have %s vulnerabilities", "really-simple-ssl").replace("%s", vulnerabilities)}
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
                            {__("You have %s open hardening features", "really-simple-ssl").replace("%s", hardening.length)}
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
                        {<span><Icon color={'red'} name="file-search"></Icon></span>}
                        <span>
                            <h2 className={"rsssl-number"}>{vulEnabled ? vulnerabilities : '-'}</h2>
                        <span
                            className={"rsssl-badge " + badgeVulStyle}>{__('Vulnerabilities', 'really-simple-ssl')}</span>
                        </span>
                    </div>
                    <div className={"rsssl-ssl-test-information"}>
                        {<span><Icon color={'red'} name="download"></Icon>
                        </span>}
                        <span>
                            <h2 className={"rsssl-number"}>{updates}</h2>
                        <span className={"rsssl-badge " + badgeUpdateStyle}>Updates</span>
                        </span>
                    </div>
                </div>

            </div>
            {checkHardening()}
            {checkVulActive()}
            {checkVul()}
            {checkUpdates()}
            <div className="rsssl-details">
            </div>


        </div>
    )
}

export default WPVul;