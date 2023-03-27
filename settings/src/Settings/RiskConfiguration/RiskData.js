/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {getMeasuresConfigData, measuresPostAction} from "../../utils/api";

const UseRiskData = create((set, get) => ({
    riskData: [],
    vulnerabilities: [],
    dataVulLoaded: false,
    dataLoaded: false,
    setData: (data) => set({riskData: data, dataLoaded: true}),
    //fetch Risk Data
    fetchRiskData: async () => {
        let data = {};
        data.risk_action = 'get';
        try {
            const riskData = await rsssl_api.getMeasuresConfigData('api_call_listener', data);
            //we convert the data to an array
            set({riskData: riskData.data, dataLoaded: true});
            console.log(riskData.data);
        } catch (e) {
            console.error(e);
        }
    },
    //update Risk Data
    updateRiskData: async (field, value) => {
        try {
            const riskData = await rsssl_api.measuresPostAction('api_call_listener', {
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
        data.action = 'get';
        try {
            const fetched = await rsssl_api.vulGetAction('api_call_listener', data);
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
