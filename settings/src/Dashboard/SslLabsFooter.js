import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';

const SslLabsFooter = (props) => {
    const startScan = () => {
        props.setBlockProps('sslScan', 'active');
    }
    let status = props.BlockProps && props.BlockProps.hasOwnProperty('sslScan') ? props.BlockProps['sslScan'] : false;
    let disabled = status === 'active' || window.location.host.indexOf('localhost')!==-1;

    return (
        <>
           <button disabled={disabled} onClick={ (e) => startScan(e)} className="button button-default">
            { status==='paused' && __("Continue SSL Health check", "really-simple-ssl")}
            { status!=='paused' && __("Check SSL Health", "really-simple-ssl")}
           </button>
        </>
    )
}

export default SslLabsFooter;