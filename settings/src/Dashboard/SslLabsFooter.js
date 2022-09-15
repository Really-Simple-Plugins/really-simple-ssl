import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import update from 'immutability-helper';
import {useUpdateEffect} from 'react-use';

const SslLabsFooter = (props) => {
    useUpdateEffect(()=> {})

    useEffect(() => {

    }, [])
    const startScan = () => {
        props.setBlockProps('sslScan', 'active');
    }
    let status = props.BlockProps && props.BlockProps.hasOwnProperty('sslScan') ? props.BlockProps['sslScan'] : false;
    let disabled = status === 'active';

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