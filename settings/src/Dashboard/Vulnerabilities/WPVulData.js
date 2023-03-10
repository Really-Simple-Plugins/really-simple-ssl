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
    vulList: [], //for storing the list of vulnerabilities

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
            set({
                vulnerabilities: fetched.data.vulnerabilities,
                updates: fetched.data.updates,
                vulList: fetched.data.vulList,
                dataLoaded: true,
                lastChecked: fetched.data.lastChecked,
                vulEnabled: fetched.data.vulEnabled,
            });
        } catch (e) {
            console.error(e);
        }
    },

    vulnerabilityScore: () => {
        let score = 0;
        let vuls = get().vulList;

        Object.keys(vuls).forEach(function (key) {
            //if there are vulnerabilities with critical severity, score is 5
            if (vuls[key].risk_level === 'c') {
                score = 5;
            } else if (score < 1){
                score = 1;
            }
        });
        return score;
    },

    hardeningScore: () => {
        let score = 0;
        let vuls = get().vulnerabilities;
        for (let i = 0; i < vuls.length; i++) {
            score += vuls[i].hardening_score;
        }
        return score;
    }

}));

export default useWPVul;