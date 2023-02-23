import React from 'react';
import {__} from '@wordpress/i18n';

const WPVulFooter = (props) => {

    return (
        <div className={'rsssl-wpvul'}>
            <div className={'alignleft'}>
                <a href="#settings/vulnerabilities"
                   className={'button button-black'}>{__('Settings', 'really-simple-ssl')}</a>
            </div>
            <div className={'alignright'}>
                <small>{__('Last checken on', 'really-simple-ssl')} 22-02-2023</small>
            </div>
        </div>
    )
}

export default WPVulFooter;