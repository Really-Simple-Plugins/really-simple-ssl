/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import {__} from "@wordpress/i18n";
import * as rsssl_api from "../../utils/api";
import {immer} from "zustand/middleware/immer";

/*
    * Creates A Store For Risk Data using Zustand
    * we also implement immer here, so we can mutate the state
 */
const UseRiskData = create((set, get) => ({
    riskData: [],
    dataLoaded: false,

    //fetch Risk Data
    fetchRiskData: async () => {
        let data = {};
        data.risk_action = 'get';
        let riskData = await rsssl_api.doAction('risk_vulnerabilities_data', data).then((response) => {
            return response;
        })
        if (typeof riskData === 'object') {
            riskData = Object.values(riskData);
        }
        if (!Array.isArray(riskData)) {
            riskData = [];
        }
        set({
            riskData: riskData,
            dataLoaded: true,
        });
    }

}));

export default UseRiskData;
