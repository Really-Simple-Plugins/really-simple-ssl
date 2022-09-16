import {useState, useEffect} from "@wordpress/element";
import * as rsssl_api from "../utils/api";
import { __ } from '@wordpress/i18n';
import update from 'immutability-helper';
import {useUpdateEffect} from 'react-use';
import Icon from "../utils/Icon";
import Placeholder from '../Placeholder/Placeholder';

const SslLabs = (props) => {
    const [sslData, setSslData] = useState(false);
    const [endpointData, setEndpointData] = useState([]);
    const [dataLoaded, setDataLoaded] = useState(false);
    const [requestActive, setRequestActive] = useState(false);
    let clearCache = false
    useEffect(()=>{
        if (!dataLoaded) {
                rsssl_api.runTest('ssltest_get' ).then( ( response ) => {
                    if (response.data.hasOwnProperty('host') )  {
                        let data = processSslData(response.data);
                        console.log(data);
                        setSslData(data);
                        setEndpointData(data.endpointData);
                        setDataLoaded(true);
                    }
                })
        }

    })

    useUpdateEffect(()=> {
        let status = props.BlockProps.hasOwnProperty('sslScan') ? props.BlockProps['sslScan'] : false;
        if (status==='active' && sslData.summary && sslData.summary.progress>=100) {
            clearCache = true;
        }

        let scanCompleted = (sslData && sslData.status === 'READY') && status!=='active'
        console.log("scanCompleted");
        console.log(scanCompleted);
        if (clearCache) scanCompleted = false;

        let startScan = !sslData.errors && !scanCompleted;
        if ( !requestActive && startScan ) {
            console.log("sslData.status");
            console.log(sslData.status);
            setRequestActive(true);
            setTimeout(function(){
                getSslLabsData().then((sslData)=>{
                    console.log("new data");
                    console.log(sslData);
                    if ( sslData.endpoints && sslData.endpoints.filter((endpoint) => endpoint.statusMessage === 'Ready').length>0 ) {
                        let completedEndpoints = sslData.endpoints.filter((endpoint) => endpoint.statusMessage === 'Ready');
                        console.log("complete endpoints");
                        console.log(completedEndpoints);
                        let lastCompletedEndpointIndex = completedEndpoints.length-1;
                        let lastCompletedEndpoint = completedEndpoints[ lastCompletedEndpointIndex];
                        let ipAddress = lastCompletedEndpoint.ipAddress;
                        console.log("ipAddress");
                        console.log(ipAddress);
                        getEndpointData(ipAddress).then( (response ) => {
                            console.log("endpoint data update");
                            console.log(response);
                            if ( !response.errors){
                                endpointData[endpointData.length] = response;
                                setEndpointData(endpointData);
                                sslData.endpointData = endpointData;
                            }

                            console.log(sslData);

                            if ( !sslData.errors ) {
                                rsssl_api.updateSslLabs(sslData ).then( ( response ) => {});
                            }
                            sslData = processSslData(sslData);
                            setSslData(sslData);
                            setRequestActive(false);
                        });
                    } else {
                        if ( !sslData.errors ) {
                            rsssl_api.updateSslLabs(sslData ).then( ( response ) => {});
                        }
                        sslData = processSslData(sslData);
                        setSslData(sslData);
                        setRequestActive(false);
                    }
                });

            }, 2000)
        }


        if ( sslData.status === 'READY' ) {
            props.setBlockProps('sslScan', 'completed');
        }
    });


    const processSslData = (sslData) => {
        let totalProgress = 100;
        let progress = sslData.progress ? sslData.progress : 0;
        let startTime = sslData.startTime ? sslData.startTime : '';
        let statusMessage = sslData.statusMessage ? sslData.statusMessage : '';
        let grade = sslData.grade ? sslData.grade : '?';
        let ipAddress='';
        if ( sslData.endpoints ) {
            totalProgress = sslData.endpoints.length * 100;
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
            console.log(sslData.errors[0]);
            grade = '?';
            statusMessage =  sslData.errors[0].message;
            progress = 100;
        }
        let summary = {};
        if ( progress >= 100) {
            props.setBlockProps('sslScan','completed');
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
        return new Promise((resolve, reject) => {
            //const host = window.location.host;
            const host = "really-simple-ssl.com";
            const url = 'https://api.ssllabs.com/api/v3/getEndpointData?host='+host+'&s='+ipAddress;
            console.log(url);
            var xmlHttp = new XMLHttpRequest();
            xmlHttp.open( "GET", url, false ); // false for synchronous request
            xmlHttp.send( null );

            resolve(JSON.parse(xmlHttp.responseText));
        });
    }

    const getSslLabsData = (e) => {
        return new Promise((resolve, reject) => {
            let clearCacheUrl = '';
            if (clearCache){
                clearCache = false;
                clearCacheUrl = '&startNew=on';
            }
    //         const host = window.location.host;
            const host = "really-simple-ssl.com";
            const url = "https://api.ssllabs.com/api/v3/analyze?host="+host+clearCacheUrl;
            var xmlHttp = new XMLHttpRequest();
            xmlHttp.open( "GET", url, false ); // false for synchronous request
            xmlHttp.send( null );
            resolve(JSON.parse(xmlHttp.responseText));
        });
    }

    const getStyles = () => {
        let progress = 0;
        if (sslData && sslData.summary.progress) {
            progress = sslData.summary.progress;
        }
        return Object.assign(
            {},
            {width: progress+"%"},
        );
    }

    const hasHSTS = () => {
        let status = 'processing';
        if ( endpointData.length>0 ) {
            let failedData = endpointData.filter(function (endpoint) {
                return endpoint.details.hstsPolicy.status!=='present';
            });
            status = failedData.length>0 ? 'error' : 'success';
        }
        return (
            <>
            {status==='processing' && <div className="rsssl-test-processing">{__("HSTS...","really-simple-ssl")}</div>}
            {status==='error' && <div className="rsssl-test-error">{__("No HSTS header","really-simple-ssl")}</div>}
            {status==='success' && <div className="rsssl-test-success">{__("HSTS header detected","really-simple-ssl")}</div>}
            </>
        )
    }

    const supportsTlS11 = () => {
        let status = 'processing';
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
            {status==='processing' && <div className="rsssl-test-processing">{__("Protocolsupport...","really-simple-ssl")}</div>}
            {status==='error' && <div className="rsssl-test-error">{__("Supports TLS 1.1","really-simple-ssl")}</div>}
            {status==='success' && <div className="rsssl-test-success">{__("No TLS 1.1","really-simple-ssl")}</div>}
            </>
        )
    }

    let sslClass = 'rsssl-incomplete';
    let progress = sslData ? sslData.summary.progress : 0;
    let startTime = sslData ? sslData.summary.startTime : false;
    let startTimeNice='';
    if (startTime) {
        let newDate = new Date();
        newDate.setTime(startTime*1000);
        startTimeNice = newDate.toUTCString();
    } else {
        startTimeNice = __("No test started yet","really-simple-ssl")
    }


    let statusMessage = sslData ? sslData.summary.statusMessage : false;
    let grade = sslData ? sslData.summary.grade : '?';
    let ipAddress = sslData ? sslData.summary.ipAddress : '';
    if ( progress>=100 ){
        sslClass = "rsssl-complete";
    }
    if (sslData.errors) {
        sslClass = "rsssl-error";
    }
    let host = window.location.protocol + "//" + window.location.host;
    let url = 'https://www.ssllabs.com/analyze.html?d='+encodeURIComponent(host);
    return (
        <div className={sslClass}>
            <div className={"rsssl-gridblock-progress-container "+sslClass}>
                <div className="rsssl-gridblock-progress" style={getStyles()}></div>
            </div>
            <div className="rsssl-ssl-test-container">
                <div className="rsssl-ssl-test ">
                    <div className="rsssl-ssl-test-information">
                        { statusMessage && <>
                            <p>{ipAddress}</p>
                            <p>{statusMessage}</p>
                        </>
                        }
                       {supportsTlS11()}
                       {hasHSTS()}
                    </div>
                    <div className={"rsssl-ssl-test-grade rsssl-h0 rsssl-grade-"+grade}>
                        <span>{grade}</span>
                    </div>
                </div>
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon">1</div>
                <div className="rsssl-detail">
                    {__("What does my score mean?", "really-simple-ssl")}&nbsp;<a href={url} target="_blank">{__("Read more", "really-simple-ssl")}</a>
                </div>
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon">2</div>
                <div className="rsssl-detail">
                    {__("Last check:", "really-simple-ssl")}&nbsp;{startTimeNice}
                </div>
            </div>
            <div className="rsssl-details">
                <div className="rsssl-detail-icon">3</div>
                <div className="rsssl-detail">
                    <a href={url} target="_blank">{__("View detailed report on Qualys SSL Labs", "really-simple-ssl")}</a>
                </div>
            </div>


        </div>
    )
}

export default SslLabs;