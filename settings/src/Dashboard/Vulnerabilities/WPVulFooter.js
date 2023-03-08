import React from 'react';
import {__} from '@wordpress/i18n';
import useWPVul from "./WPVulData";

const WPVulFooter = (props) => {
    const {lastChecked} = useWPVul();
    return (
        <div className={'rsssl-wpvul'}>
            <a href="#settings/vulnerabilities"
               className={'button button-default alignleft'}>{__('Settings', 'really-simple-ssl')}</a>
            <small className={'alignright'}>{__('Last checked on', 'really-simple-ssl')}: {lastChecked}</small>
        </div>
    )
}

export default WPVulFooter;