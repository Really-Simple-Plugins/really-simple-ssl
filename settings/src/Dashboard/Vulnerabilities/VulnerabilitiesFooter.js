import React, {useEffect, useState} from 'react';
import {__} from '@wordpress/i18n';
import useRiskData from "../../Settings/RiskConfiguration/RiskData";
import useFields from "../../Settings/FieldsData";
import {getRelativeTime} from '../../utils/formatting';

const VulnerabilitiesFooter = (props) => {
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
        <>
            <a href="#settings/vulnerabilities" className={'button button-default'}>{__('Settings', 'really-simple-ssl')}</a>
            {vulEnabled? <p className={'rsssl-small-text'}>{getRelativeTime(lastChecked)}</p>: null}
        </>
    )
}

export default VulnerabilitiesFooter;
