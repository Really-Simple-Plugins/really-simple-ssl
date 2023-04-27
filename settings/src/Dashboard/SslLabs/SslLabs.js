import {useState, useEffect, useRef} from "@wordpress/element";
import * as rsssl_api from "../../utils/api";
import { __ } from '@wordpress/i18n';
import Icon from "../../utils/Icon";
import useSslLabs from "./SslLabsData";

const SslLabs = (props) => {
    const {sslScanStatus, setSslScanStatus, isLocalHost, host } = useSslLabs();
    const [sslData, setSslData] = useState(false);
    const [endpointData, setEndpointData] = useState([]);
    const [dataLoaded, setDataLoaded] = useState(false);
    const hasRunOnce = useRef(false);
    const clearCache = useRef(false);
    const requestActive = useRef(false);
    const intervalId = useRef(false);

    useEffect(()=>{
        if ( !dataLoaded ) {
            rsssl_api.doAction('ssltest_get').then( ( response ) => {
                if (response.data.hasOwnProperty('host') )  {
                    let data = processSslData(response.data);
                    setSslData(data);
                    setEndpointData(data.endpointData);
                    setDataLoaded(true);
                }
            })
        }
    } , [])

    const neverScannedYet = () => {
        return !sslData;
    }

    useEffect(()=> {
        if ( isLocalHost() ) {
            return;
        }

        if (sslScanStatus==='active' && sslData.summary && sslData.summary.progress>=100 ) {
            clearCache.current = true;
            hasRunOnce.current = false;
            setSslData(false);
            setEndpointData(false);
        }

        if (sslScanStatus==='active' && sslData.status === 'ERROR' ) {
            clearCache.current = true;
            setSslData(false);
            setEndpointData(false);
        }

        let scanInComplete = (sslData && sslData.status !== 'READY');
        let userClickedStartScan = sslScanStatus==='active';
        if (clearCache.current) scanInComplete = true;
        let hasErrors = sslData.errors || sslData.status === 'ERROR';
        let startScan = !hasErrors && (scanInComplete || userClickedStartScan);
        if ( !requestActive.current && startScan ) {
            setSslScanStatus('active');
            requestActive.current = true;
            if ( !hasRunOnce.current ) {
                runSslTest();
                intervalId.current = setInterval(function(){
                    runSslTest();
                }, 3000)
                hasRunOnce.current  = true;
            }
        } else if ( sslData && sslData.status === 'READY' ) {
            setSslScanStatus('completed');
            clearInterval(intervalId.current);
        }
    }, [sslScanStatus]);

    const runSslTest = () => {
        getSslLabsData().then((sslData)=>{
            if ( sslData.status && sslData.status === 'ERROR' ){
                sslData = processSslData(sslData);
                setSslData(sslData);
                setSslScanStatus('completed');
                clearInterval(intervalId.current);
            } else
            if ( sslData.endpoints && sslData.endpoints.filter((endpoint) => endpoint.statusMessage === 'Ready').length>0 ) {
                let completedEndpoints = sslData.endpoints.filter((endpoint) => endpoint.statusMessage === 'Ready');
                let lastCompletedEndpointIndex = completedEndpoints.length-1;
                let lastCompletedEndpoint = completedEndpoints[ lastCompletedEndpointIndex];
                let ipAddress = lastCompletedEndpoint.ipAddress;
                getEndpointData(ipAddress).then( (response ) => {

                    if ( !response.errors && endpointData ){
                        //if the endpoint already is stored, replace it.
                        let foundEndpoint = false;
                        endpointData.forEach(function(endpoint, i) {
                            if ( endpoint.ipAddress === response.ipAddress ) {
                                endpointData[i] = response;
                                foundEndpoint = true;
                            }
                        });

                        if ( !foundEndpoint ) {
                            endpointData[endpointData.length] = response;
                        }

                        setEndpointData(endpointData);
                        sslData.endpointData = endpointData;
                    }

                    if ( !sslData.errors ) {
                        rsssl_api.doAction('store_ssl_labs', sslData ).then( ( response ) => {});
                    }
                    sslData = processSslData(sslData);
                    setSslData(sslData);
                    requestActive.current = false;
                });
            } else {
                //if there are no errors, this is the first request. We reset the endpoint data we have.
                setEndpointData([]);
                sslData.endpointData = endpointData;
                sslData = processSslData(sslData);
                setSslData(sslData);
                if ( !sslData.errors ) {
                    rsssl_api.doAction('store_ssl_labs', sslData ).then( ( response ) => {});
                }

                requestActive.current = false;
            }

        });

}

    const processSslData = (sslData) => {
        let progress = sslData.progress ? sslData.progress : 0;
        let startTime = sslData.startTime ? sslData.startTime : '';
        let statusMessage = sslData.statusMessage ? sslData.statusMessage : '';
        let grade = sslData.grade ? sslData.grade : '?';
        let ipAddress='';
        if ( sslData.endpoints ) {
            let completedEndpoints = sslData.endpoints.filter((endpoint) => endpoint.statusMessage === 'Ready');
            let completedEndpointsLength = completedEndpoints.length;
            let lastCompletedEndpoint = completedEndpoints[ completedEndpointsLength-1];
            let activeEndpoint = sslData.endpoints.filter((endpoint) => endpoint.statusMessage === 'In progress')[0];
            let activeEndpointProgress = 0;
            if (activeEndpoint) {
                activeEndpointProgress = activeEndpoint.progress ? activeEndpoint.progress : 0;
                statusMessage = activeEndpoint.statusDetailsMessage;
                ipAddress = activeEndpoint.ipAddress;
            }
            if (lastCompletedEndpoint) grade = lastCompletedEndpoint.grade;

            progress = ( completedEndpointsLength * 100 + activeEndpointProgress ) / sslData.endpoints.length;
        }
        if ( sslData.errors ) {
            grade = '?';
            statusMessage =  sslData.errors[0].message;
            progress = 100;
        }
        let summary = {};
        if ( progress >= 100) {
            setSslScanStatus('completed');
        }
        summary.grade = grade;
        summary.startTime = startTime;
        summary.statusMessage = statusMessage;
        summary.ipAddress = ipAddress;
        summary.progress = progress;
        sslData.summary = summary;
        return sslData;
    }

    const getEndpointData = (ipAddress) => {
        const url = 'https://api.ssllabs.com/api/v3/getEndpointData?host='+host()+'&s='+ipAddress;
        let data = {};
        data.url = url;
        return rsssl_api.doAction('ssltest_run', data).then( ( response ) => {
            if ( response && !response.errors) {
                return JSON.parse(response);
            }
        })
    }

    const getSslLabsData = (e) => {
        let clearCacheUrl = '';
        if (clearCache.current){
            clearCache.current = false;
            clearCacheUrl = '&startNew=on';
            setSslData(false);
        }
        const url = "https://api.ssllabs.com/api/v3/analyze?host="+host()+clearCacheUrl;
        let data = {};
        data.url = url;
        return rsssl_api.doAction('ssltest_run', data).then( ( response ) => {
            if ( response && !response.errors) {
                return JSON.parse(response);
            } else {
                return false;
            }
        })
    }

    /**
     * Get some styles for the progress bar
     * @returns {{width: string}}
     */
    const getStyles = () => {
        let progress = 0;
        if (sslData && sslData.summary.progress) {
            progress = sslData.summary.progress;
        } else if (progress==0 && sslScanStatus ==='active') {
           progress=5;
        }

        return Object.assign(
            {},
            {width: progress+"%"},
        );
    }

    /**
     * Retrieve information from SSL labs if HSTS is detected
     *
     * @returns {JSX.Element}
     */
    const hasHSTS = () => {
        let status = 'processing';
        if ( neverScannedYet() ){
            status = 'inactive';
        }

        if ( endpointData && endpointData.length>0 ) {
            let failedData = endpointData.filter(function (endpoint) {
                return endpoint.details.hstsPolicy.status!=='present';
            });
            status = failedData.length>0 ? 'error' : 'success';
        }
        return (
            <>
                {(status==='inactive') && scoreSnippet("rsssl-test-inactive", "HSTS")}
                {status==='processing' && scoreSnippet("rsssl-test-processing", "HSTS...")}
                {status==='error' && scoreSnippet("rsssl-test-error", "No HSTS header")}
                {status==='success' && scoreSnippet("rsssl-test-success", "HSTS header detected")}
            </>
        )
    }

    /**
     * Calculate cipher strength
     * @returns {JSX.Element}
     */
    const cipherStrength = () => {
        // Start with the score of the strongest cipher.
        // Add the score of the weakest cipher.
        // Divide the total by 2.
        let rating = 0;
        let ratingClass = 'rsssl-test-processing';
        if ( neverScannedYet() ){
            ratingClass = 'rsssl-test-inactive';
        }
        if ( endpointData && endpointData.length>0 ) {
            let weakest = 256;
            let strongest = 128;
            endpointData.forEach(function(endpoint, i){
                endpoint.details.suites.forEach(function(suite, j){
                   suite.list.forEach(function(cipher, j){
                       weakest = cipher.cipherStrength<weakest ? cipher.cipherStrength : weakest;
                       strongest = cipher.cipherStrength>strongest ? cipher.cipherStrength : strongest;
                   });
               });
           });
           rating = (getCypherRating(weakest) + getCypherRating(strongest) )/2;
           rating = Math.round(rating);
           ratingClass = rating>70 ? "rsssl-test-success" : "rsssl-test-error";
        }

        return (
            <>
            {scoreSnippet(ratingClass, __("Cipher strength","really-simple-ssl")+' '+rating+'%')}
            </>
        )
    }

    /**
    * https://github.com/ssllabs/research/wiki/SSL-Server-Rating-Guide#Certificate-strength
    */
    const getCypherRating = (strength) => {
        let score = 0;
        if (strength==0) {
            score = 0;
        } else if (strength<128){
            score = 20;
        } else if (strength<256){
            score=80;
        } else {
          score=100;
        }
        return score;
    }

    const certificateStatus = () => {
        let status = 'processing';
        if ( neverScannedYet() ){
            status = 'inactive';
        }
        if ( endpointData && endpointData.length>0 ) {
            let failedData = endpointData.filter(function (endpoint) {
                return endpoint.grade.indexOf('A')===-1;
            });
            status = failedData.length>0 ? 'error' : 'success';
        }
        return (
            <>
            {(status==='inactive') && scoreSnippet("rsssl-test-inactive", "Certificate")}
            {status==='processing' && scoreSnippet("rsssl-test-processing", "Certificate...")}
            {status==='error' && !hasErrors && scoreSnippet("rsssl-test-error", "Certificate issue")}
            {status==='success' && scoreSnippet("rsssl-test-success", "Valid certificate")}
            </>
        )
    }

    const scoreSnippet = (className, content) => {
        return (
            <div className="rsssl-score-container"><div className={"rsssl-score-snippet "+className}>{content}</div></div>
        )
    }

    const supportsTlS11 = () => {
        let status = 'processing';
        if ( neverScannedYet() ){
            status = 'inactive';
        }
        if ( endpointData && endpointData.length>0 ) {
            status = 'success';
            endpointData.forEach(function(endpoint, i){
                endpoint.details.protocols.forEach(function(protocol, j){
                   if (protocol.version==='1.1') status = 'error';
               });
           });
        }
        return (
            <>
            {(status==='inactive') && scoreSnippet("rsssl-test-inactive", "Protocol support")}
            {(status==='processing') && scoreSnippet("rsssl-test-processing", "Protocol support...")}
            {status==='error' && scoreSnippet("rsssl-test-error", "Supports TLS 1.1")}
            {status==='success' && scoreSnippet("rsssl-test-success", "No TLS 1.1")}
            </>
        )
    }

    let sslClass = 'rsssl-inactive';
    let progress = sslData ? sslData.summary.progress : 0;
    let startTime = sslData ? sslData.summary.startTime : false;
    let startTimeNice='';
    if ( startTime ) {
        let newDate = new Date();
        newDate.setTime(startTime);
        startTimeNice = newDate.toLocaleString();
    } else {
        startTimeNice = __("No test started yet","really-simple-ssl")
    }

    let statusMessage = sslData ? sslData.summary.statusMessage : false;
    let grade = sslData ? sslData.summary.grade : '?';
    let ipAddress = sslData ? sslData.summary.ipAddress : '';
    if ( sslData && sslData.status === 'READY' ) {
        if ( grade.indexOf('A')!==-1 ){
            sslClass = "rsssl-success";
        } else {
            sslClass = "rsssl-error";
        }
    }

    if (neverScannedYet()){
        sslClass = "rsssl-inactive";
    }

    let gradeClass = neverScannedYet() ? 'inactive' : grade;
    let url = 'https://www.ssllabs.com/analyze.html?d='+encodeURIComponent(window.location.protocol + "//" + window.location.host);
    let hasErrors = false;
    let errorMessage='';
    let sslStatusColor = 'black';

    if ( isLocalHost() ) {
        hasErrors = true;
        sslStatusColor = 'red';
        errorMessage = __("Not available on localhost","really-simple-ssl");
    } else if (sslData && (sslData.errors || sslData.status === 'ERROR') ) {
        hasErrors = true;
        sslStatusColor = 'red';
        errorMessage = statusMessage;
    } else if (sslData && progress<100 ) {
        hasErrors = true;
        sslStatusColor = 'orange';
        errorMessage = statusMessage;
    }

    return (
        <div className={sslClass}>
            <div className={"rsssl-gridblock-progress-container "+sslClass}>
                <div className="rsssl-gridblock-progress" style={getStyles()}></div>
            </div>
            <div className={"rsssl-ssl-test-container "+sslClass}>
                <div className="rsssl-ssl-test ">
                    <div className="rsssl-ssl-test-information">
                       {supportsTlS11()}
                       {hasHSTS()}
                       {certificateStatus()}
                       {cipherStrength()}
                    </div>
                    <div className={"rsssl-ssl-test-grade rsssl-grade-"+gradeClass}>
                        {!neverScannedYet() && <span>{grade}</span>}
                        {neverScannedYet() && <div></div>}
                    </div>
                </div>
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon"><Icon name = "info" color = {sslStatusColor} /></div>
                <div className={"rsssl-detail rsssl-status-"+sslStatusColor}>
                { hasErrors && <>{errorMessage}</>}
                { !hasErrors && <> {__("What does my score mean?", "really-simple-ssl") }&nbsp;<a href="https://really-simple-ssl.com/instructions/about-ssl-labs/" target="_blank">{__("Read more", "really-simple-ssl")}</a></>}
                </div>
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon"><Icon name = "list" color = 'black' /></div>
                <div className="rsssl-detail">
                    {__("Last check:", "really-simple-ssl")}&nbsp;{startTimeNice}
                </div>
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon"><Icon name = "external-link" color = 'black' /></div>
                <div className="rsssl-detail">
                    <a href={url} target="_blank">{__("View detailed report on Qualys SSL Labs", "really-simple-ssl")}</a>
                </div>
            </div>


        </div>
    )
}

export default SslLabs;