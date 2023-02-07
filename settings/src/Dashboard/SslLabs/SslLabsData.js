import {create} from 'zustand';
const useSslLabs = create(( set, get ) => ({
    debug:false, //set to true for localhost testing, with wordpress.org as domain
    sslScanStatus: false,
    setSslScanStatus: (sslScanStatus) => set({ sslScanStatus }),
    isLocalHost: () => {
        let debug = get().debug;
        return debug ? false: window.location.host.indexOf('localhost')!==-1;
    } ,
    host: () => {
        let debug = get().debug;
        return debug ? "wordpress.org" : window.location.host;
    }
}));
export default useSslLabs;
