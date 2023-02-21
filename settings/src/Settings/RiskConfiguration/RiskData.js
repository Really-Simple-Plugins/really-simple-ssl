/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import {__} from "@wordpress/i18n";
import * as rsssl_api from "../../utils/api";

const UseRiskData = create((set, get) => ({
    riskData: [],
    dataLoaded: false,
    setData: (data) => set({riskData: data, dataLoaded: true}),
    //fetch Risk Data
    fetchRiskData: async () => {
        let data = {};
        data.risk_action = 'get';
        try {
            const riskData = await rsssl_api.doAction('risk_vulnerabilities_data', data);
            //we convert the data to an array
            set({riskData: riskData, dataLoaded: true});
        } catch (e) {
            console.error(e);
        }
    },
    //update Risk Data
    updateRiskData: async (field, value) => {
        try {
            const riskData = await rsssl_api.doAction('risk_vulnerabilities_data_save', {
                field: field,
                value: value,
            });
            set({riskData: riskData, dataLoaded: true});
        } catch (e) {
            console.error(e);
        }
    }
}));

export default UseRiskData;
