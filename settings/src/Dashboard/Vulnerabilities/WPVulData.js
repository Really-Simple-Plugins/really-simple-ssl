import {create} from "zustand";
import * as rsssl_api from "../../utils/api";

const useWPVul = create((set, get) => ({
    // Stuff we need for the WPVulData component
    updates: 0, //for letting the component know if there are updates available
    vulnerabilities: 0, //for storing the data
    HighestRisk: false, //for storing the highest risk
    dataLoaded: false, //for letting the component know if the data is loaded
    lastChecked: 'never', //for storing the last time the data was checked
    vulEnabled: false, //for storing the status of the vulnerability scan

    /*
    * Setters
     */
    // setData: (data) =>
    //     set({
    //         updates: data.updates,
    //         vulnerabilities: data.vulnerabilities,
    //         dataLoaded: true,
    //     }),

    /*
    * Getters
     */
    getVulnerabilities: () => {
        return get().vulnerabilities;
    },

    /*
    * Functions
     */
    fetchVulnerabilities: async () => {
        let data = {};
        data.action = 'get';
        try {
            const fetched = await rsssl_api.vulGetAction('api_call_listener', data);
            console.log(fetched);
            set({
                vulnerabilities: fetched.data.vulnerabilities,
                HighestRisk: determineHighestRisk(fetched.data.vulnerabilities),
                updates: fetched.data.updates,
                dataLoaded: true,
                lastChecked: fetched.data.lastChecked,
                vulEnabled: fetched.data.vulEnabled,
            });


        } catch (e) {
            console.error(e);
        }

        function determineHighestRisk(vulnerabilities) {
            console.log(vulnerabilities);
            return 'c';
        }


    }

}));

export default useWPVul;