import React, {useEffect, useState} from 'react';
import {__} from '@wordpress/i18n';
import useRiskData from "../../Settings/RiskConfiguration/RiskData";
import useFields from "../../Settings/FieldsData";

const WPVulFooter = (props) => {
    const {lastChecked} = useRiskData();
    const {fields, getFieldValue} = useFields();
    const [vulEnabled, setVulEnabled] = useState(false);
    useEffect(() => {
        if (getFieldValue('enable_vulnerability_scanner')==1) {
            setVulEnabled(true);
        }
    }, [fields]);

   const styleFooter = {
        textAlign: 'right',
        position: 'relative',
        right: '0',
    }
    return (
        <div className={'rsssl-wpvul'}>
            <a href="#settings/vulnerabilities" className={'button button-default alignleft'}>{__('Settings', 'really-simple-ssl')}</a>
            {vulEnabled? <small className={'alignright'}>{__('', 'really-simple-ssl')} {lastChecked}</small>:<small className={'alignright'} style={styleFooter}></small>}
        </div>
    )
}

export default WPVulFooter;
