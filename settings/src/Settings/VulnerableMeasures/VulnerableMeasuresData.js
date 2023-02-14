import {create} from "zustand";
import * as rsssl_api from "../../utils/api";

const UseMeasuresData = create((set, get) => ({
    measuresData: [],
    dataLoaded: false,
    fixedItemId: false,
    action: "",
    nonce: "",
    completedStatus: "never",
    progress: 0,
    scanStatus: false,
    fetchMeasuresData: async () => {
        set({ scanStatus: "running" });
        const { data, progress, state, action, nonce, completed_status } = await getScanIteration(false);
        set({
            scanStatus: state,
            measuresData: data,
            progress: progress,
            action: action,
            nonce: nonce,
            completedStatus: completed_status,
            dataLoaded: true
        });
    },
    start: async () => {
        const { data, progress, state, action, nonce, completed_status } = await getScanIteration("start");
        set({
            scanStatus: state,
            measuresData: data,
            progress: progress,
            action: action,
            nonce: nonce,
            completedStatus: completed_status,
            dataLoaded: true
        });
    }
}));
