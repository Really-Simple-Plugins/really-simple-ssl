import Icon from "../../utils/Icon";
import {__} from "@wordpress/i18n";
import useWPVul from "./WPVulData";
import {useEffect} from "react";
import useFields from "../../Settings/FieldsData";

const WPVul = (props) => {
    const {vulnerabilities, HighestRisk, lastChecked, vulEnabled, updates, dataLoaded, fetchVulnerabilities} = useWPVul();
    const {fields, fieldsLoaded} = useFields();
    let featuredFields = fields.filter( field => field.new_features_block );
    useEffect(() => {
        fetchVulnerabilities().then(r => {
            console.log(r);
        });
    }, []);

    if (!dataLoaded) {
        //we do not have the data yet, so we return null
        return null;
    }

    let vulClass = 'rsssl-inactive';
    const hardening = featuredFields.filter(field => field.value === 0);

    // console.log(updates, vulnerabilities, 'hardening',hardening);
    if (!(updates === 0 && vulnerabilities === 0 && hardening)) {
        vulClass = 'rsssl-error';
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
                <div className="rsssl-detail-icon"><Icon name="info" color='yellow'/></div>
                <div className="rsssl-detail">
                    {__("Enable vulnerability scanning for more information", "really-simple-ssl")}
                </div>
            </>
        )
    }

    const checkUpdates = () => {
        if (updates) {
            return (
                <>
                    <div className="rsssl-detail-icon"><Icon name="circle-times" color='red'/></div>
                    <div className="rsssl-detail">
                        {__("You have %s updates pending", "really-simple-ssl").replace("%s", updates)}
                    </div>
                </>
            )
        }
    }

    const checkVul = () => {
        if (vulnerabilities) {
            return (
                <>
                    <div className="rsssl-detail-icon"><Icon name="circle-times" color='red'/></div>
                    <div className="rsssl-detail">
                        {__("You have %s vulnerabilities", "really-simple-ssl").replace("%s", vulnerabilities)}
                    </div>
                </>
            )
        }
    }

    const checkHardening = () => {
        //
        if (hardening.length) {
            return (
                <>
                    <div className="rsssl-detail-icon"><Icon name="circle-times" color='red'/></div>
                    <div className="rsssl-detail">
                        {__("You have %s open hardening features", "really-simple-ssl").replace("%s", hardening.length)}
                    </div>
                </>
            )
        }
    }

    return (
        <div className={vulClass}>
            <div className={"rsssl-gridblock-progress-container " + vulClass}>
                <div className="rsssl-gridblock-progress" style={getStyles()}></div>
            </div>
            <div className={"rsssl-ssl-test-container " + vulClass}>
                <div className="rsssl-ssl-test ">
                    <div className="rsssl-ssl-test-information">
                        {<span><Icon color={'red'} name="file-search"></Icon></span>}
                        <span>
                            <h2 className={"rsssl-number"}>{vulnerabilities}</h2>
                        <p className={"rsssl-badge rsp-default"}>Vulnerabilities</p>
                        </span>
                    </div>
                    <div className={"rsssl-ssl-test-information"}>
                        {<span><Icon color={'red'} name="download"></Icon>
                        </span>}
                        <span>
                            <h2 className={"rsssl-number"}>{updates}</h2>
                        <p className={"rsssl-badge rsp-default"}>Updates</p>
                        </span>

                    </div>
                </div>

            </div>
            <div className="rsssl-details">
                {checkVulActive()}
            </div>
            <div className="rsssl-details">
                {checkUpdates()}
            </div>
            <div className="rsssl-details">
                {checkVul()}
            </div>
            <div className="rsssl-details">
                {checkHardening()}
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon"><Icon name="external-link" color='black'/></div>
                <div className="rsssl-detail">
                    <a href={'https://vulnerability.wpsysadmin.com/'}
                       target="_blank">{__("View all about WPVulnerability", "really-simple-ssl")}</a>
                </div>
            </div>


        </div>
    )
}

export default WPVul;