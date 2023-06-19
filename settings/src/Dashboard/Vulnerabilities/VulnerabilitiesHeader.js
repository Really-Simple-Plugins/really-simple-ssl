import { __ } from '@wordpress/i18n';
import {useEffect, useState} from "react";
import useFields from "../../Settings/FieldsData";
const VulnerabilitiesHeader = () => {
    const {fields, getFieldValue} = useFields();
    const [vulEnabled, setVulEnabled] = useState(false);
    useEffect(() => {
        if (getFieldValue('enable_vulnerability_scanner')==1) {
            setVulEnabled(true);
        }
    }, [fields]);

    return (
        <>
            <h3 className="rsssl-grid-title rsssl-h4">{  vulEnabled ? __( "Vulnerabilities", 'really-simple-ssl' ) : __( "Hardening", 'really-simple-ssl' ) }</h3>
            <div className="rsssl-grid-item-controls">
                <span className="rsssl-header-html"></span>
            </div>
        </>
    )
}

export default VulnerabilitiesHeader;