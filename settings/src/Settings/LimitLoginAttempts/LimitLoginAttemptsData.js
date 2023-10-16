/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
import {produce} from "immer";
import React from "react";

const LimitLoginAttemptsData = create((set, get) => ({

    processing:false,
    dataLoaded: false,
    EventLog: [],

    fetchEventLog: async (selectedFilter) => {
        set({processing:true});
        try {
            let response = await rsssl_api.doAction(selectedFilter);
            set({EventLog: response, dataLoaded: true, processing:false});
        } catch (e) {
            console.log(e);
        }
    }
}));

export default LimitLoginAttemptsData;