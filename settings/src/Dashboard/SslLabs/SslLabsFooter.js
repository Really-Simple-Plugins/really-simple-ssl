import { __ } from '@wordpress/i18n';
import useSslLabs from "./SslLabsData";
const SslLabsFooter = () => {
    const {sslScanStatus, setSslScanStatus, isLocalHost} = useSslLabs();
    const startScan = () => {
        setSslScanStatus('active');
    }

    let disabled = sslScanStatus === 'active' || isLocalHost();
    return (
        <>
           <button disabled={disabled} onClick={ (e) => startScan(e)} className="button button-default">
            { sslScanStatus==='paused' && __("Continue SSL Health check", "really-simple-ssl")}
            { sslScanStatus!=='paused' && __("Check SSL Health", "really-simple-ssl")}
           </button>
        </>
    )
}

export default SslLabsFooter;