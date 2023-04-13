import React from 'react';
import {__} from '@wordpress/i18n';
import useVulnerabilityData from "./VulnerabilityData";

const WPVulFooter = (props) => {
    const {lastChecked, vulEnabled} = useVulnerabilityData();
    const styleFooter = {
        textAlign: 'right',
        position: 'relative',
        right: '0',
    }
    return (
        <div className={'rsssl-wpvul'}>
            <a href="#settings/vulnerabilities"
               className={'button button-default alignleft'}>{__('Settings', 'really-simple-ssl')}</a>

            {vulEnabled? <small className={'alignright'}>{__('Last check:', 'really-simple-ssl')} {lastChecked}</small>:<small className={'alignright'} style={styleFooter}></small>}
        </div>
    )
}

export default WPVulFooter;