/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";

const UseRiskData = create((set, get) => ({
    riskData: [],
    vulnerabilities: [],
    dataVulLoaded: false,
    dataLoaded: false,
    setData: (data) => set({riskData: data, dataLoaded: true}),
    //fetch Risk Data
    fetchRiskData: async () => {
        let data = {};
        try {
            const riskData = await rsssl_api.doAction('vulnerabilities_measures_get', data);
            //we convert the data to an array
            set({riskData: riskData.data, dataLoaded: true});
        } catch (e) {
            console.error(e);
        }
    },
    //update Risk Data
    updateRiskData: async (field, value) => {
        try {
            const riskData = await rsssl_api.doAction('vulnerabilities_measures_set', {
                field: field,
                value: value,
            });
            set({riskData: riskData.data, dataLoaded: true});
        } catch (e) {
            console.log(e);
        }
    },

    //we fetch the vulnerability data
    /*
    * Functions
     */
    fetchVulnerabilities: async () => {
        let data = {};
        try {
            const fetched = await rsssl_api.doAction('vulnerabilities_stats', data);
            set({
                vulnerabilities: fetched.data.vulList,
                dataVulLoaded: true,
                vulEnabled: fetched.data.vulEnabled,
            });
        } catch (e) {
            console.error(e);
        }
    },
}));

export default UseRiskData;
