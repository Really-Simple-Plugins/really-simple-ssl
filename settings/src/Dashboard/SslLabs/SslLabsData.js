import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
const useSslLabs = create(( set, get ) => ({
    debug:false, //set to true for localhost testing, with wordpress.org as domain
    sslScanStatus: false,
    sslData: false,
    endpointData: [],
    dataLoaded:false,
    clearCache:false,
    requestActive:false,
    intervalId:false,
    setIntervalId: (intervalId) => set({ intervalId }),
    setRequestActive: (requestActive) => set({ requestActive }),
    setSslScanStatus: (sslScanStatus) => set({ sslScanStatus }),
    setClearCache: (clearCache) => set({ clearCache }),
    setSslData: (sslData) => set({ sslData }),
    setEndpointData: (endpointData) => set({ endpointData }),

    isLocalHost: () => {
        let debug = get().debug;
        return debug ? false: window.location.host.indexOf('localhost')!==-1;
    } ,
    host: () => {
        let debug = get().debug;
        return debug ? "wordpress.org" : window.location.host;
    },
    fetchSslData: async () => {
        rsssl_api.doAction('ssltest_get').then( ( response ) => {
            if (response.data.hasOwnProperty('host') )  {
                let data = get().processSslData(response.data);
                set({
                    sslData: data,
                    endpointData: data.endpointData,
                    dataLoaded: true,
                })
            }
        })
    },
    getSslLabsData: (e) => {
        let clearCacheUrl = '';
        if (get().clearCache){
            set({clearCache:false,sslData:false });
            clearCacheUrl = '&startNew=on';
        }
        const url = "https://api.ssllabs.com/api/v3/analyze?host="+get().host()+clearCacheUrl;
        let data = {};
        data.url = url;
        return rsssl_api.doAction('ssltest_run', data).then( ( response ) => {
            if ( response && !response.errors) {
                return JSON.parse(response);
            } else {
                return false;
            }
        })
    },
    runSslTest: () => {
        get().getSslLabsData().then((sslData)=>{
            if ( sslData.status && sslData.status === 'ERROR' ){
                sslData = get().processSslData(sslData);
                set({
                    sslData: sslData,
                    sslScanStatus: 'completed',
                });
                clearInterval(get().intervalId);
            } else
            if ( sslData.endpoints && sslData.endpoints.filter((endpoint) => endpoint.statusMessage === 'Ready').length>0 ) {
                let completedEndpoints = sslData.endpoints.filter((endpoint) => endpoint.statusMessage === 'Ready');
                let lastCompletedEndpointIndex = completedEndpoints.length-1;
                let lastCompletedEndpoint = completedEndpoints[ lastCompletedEndpointIndex];
                let ipAddress = lastCompletedEndpoint.ipAddress;
                get().getEndpointData(ipAddress).then( (response ) => {
                    let endpointData = get().endpointData;
                    if (!Array.isArray(endpointData)) endpointData = [];
                    if ( !response.errors ){
                        //if the endpoint already is stored, replace it.
                        let foundEndpoint = false;
                        if (endpointData.length>0) {
                            endpointData.forEach(function(endpoint, i) {
                                if ( endpoint.ipAddress === response.ipAddress ) {
                                    endpointData[i] = response;
                                    foundEndpoint = true;
                                }
                            });
                        }
                        if ( !foundEndpoint ) {
                            endpointData[endpointData.length] = response;
                        }
                        set({endpointData: endpointData});
                        sslData.endpointData = endpointData;
                    }

                    if ( !sslData.errors ) {
                        rsssl_api.doAction('store_ssl_labs', sslData );
                    }
                    sslData = get().processSslData(sslData);
                    set({sslData: sslData, requestActive: false});
                });
            } else {
                //if there are no errors, this runs when the first endpoint is not completed yet
                sslData = get().processSslData(sslData);
                if ( !sslData.errors ) {
                    rsssl_api.doAction('store_ssl_labs', sslData ).then( ( response ) => {});
                }
                set({sslData:sslData,requestActive: false});
            }

        });

    },
    processSslData: (sslData) => {
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
            set({sslScanStatus: 'completed'});
        }
        summary.grade = grade;
        summary.startTime = startTime;
        summary.statusMessage = statusMessage;
        summary.ipAddress = ipAddress;
        summary.progress = progress;
        sslData.summary = summary;
        return sslData;
    },
    getEndpointData:(ipAddress) => {
        const url = 'https://api.ssllabs.com/api/v3/getEndpointData?host='+get().host()+'&s='+ipAddress;
        let data = {};
        data.url = url;
        return rsssl_api.doAction('ssltest_run', data).then( ( response ) => {
            if ( response && !response.errors) {
                return JSON.parse(response);
            }
        })
    }
}));
export default useSslLabs;







