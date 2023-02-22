import Icon from "../../utils/Icon";
import {__} from "@wordpress/i18n";

const WPVul = ( props ) => {
    const vulClass = 'rsssl-inactive';
    const gradeClass = 'inactive';
    const hasErrors = false;

    const scoreSnippet = (className, content) => {
        return (
            <div className="rsssl-score-container"><div className={"rsssl-score-snippet "+className}>{content}</div></div>
        )
    }

    let vulStatusColor = 'black';

    const getStyles = () => {
        let progress = 0;
        let vulScanStatus = 'active';
          if (vulScanStatus==='active') progress=50;
          if (vulScanStatus==='paused') progress=100;

        return Object.assign(
            {},
            {width: progress+"%"},
        );
    }

    function enabledVul() {
        const status = 'enabled';
        return (
            <>
                {(status==='enabled') && scoreSnippet("rsssl-test-inactive", "Vulnerability support")}
            </>
        )
    }

    function neverScannedYet() {
        return true;
    }

    return (
        <div className={vulClass}>
            <div className={"rsssl-gridblock-progress-container " + vulClass}>
                <div className="rsssl-gridblock-progress" style={getStyles()}></div>
            </div>
            <div className={"rsssl-ssl-test-container " + vulClass}>
                <div className="rsssl-ssl-test ">
                    <div className="rsssl-ssl-test-information">
                        {enabledVul()}
                    </div>
                    <div className={"rsssl-ssl-test-grade rsssl-grade-" + gradeClass}>
                        {neverScannedYet() && <span>cool</span>}
                    </div>
                </div>
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon"><Icon name="info" color={vulStatusColor}/></div>
                <div className={"rsssl-detail rsssl-status-" + vulStatusColor}>
                    {hasErrors && <>{vulStatusColor}</>}
                    {!hasErrors && <> {__("What does my score mean?", "really-simple-ssl")}&nbsp;<a
                        href="https://really-simple-ssl.com/instructions/about-ssl-labs/"
                        target="_blank">{__("Read more", "really-simple-ssl")}</a></>}
                </div>
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon"><Icon name="list" color='black'/></div>
                <div className="rsssl-detail">
                    {__("Last check:", "really-simple-ssl")}&nbsp;{'today'}
                </div>
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon"><Icon name="external-link" color='black'/></div>
                <div className="rsssl-detail">
                    <a href={'#'}
                       target="_blank">{__("View detailed report on Qualys SSL Labs", "really-simple-ssl")}</a>
                </div>
            </div>


        </div>
    )
}

export default WPVul;