import { create } from 'zustand';
import * as rsssl_api from "../../utils/api";

const ManualCspAddition = create((set, get) => ({
    manualAdditionProcessing: false,
    manualAdditionData: [],
    manualAdditionDataLoaded: false,
    cspUri: '',
    directive: '',

    setDirective: (directive) => set({ directive }),
    setCspUri: (cspUri) => set({ cspUri }),
    setDataLoaded: (manualAdditionDataLoaded) => set({ manualAdditionDataLoaded }),
    addManualCspEntry: async (cspUri, directive) => {
        let response;

        set({ manualAdditionProcessing: true });

        try {
            response = await rsssl_api.doAction('rsssl_csp_uri_add', { cspUri, directive });
            if (response.request_success) {
                set({ manualAdditionDataLoaded: false });
            }
        } catch (e) {
            console.error(e);
        } finally {
            set({ manualAdditionProcessing: false, manualAdditionDataLoaded: true });
        }

        // Should contain keys "success" and "message";
        return response;
    }
}));

export default ManualCspAddition;