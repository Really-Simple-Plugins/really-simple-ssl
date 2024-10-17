import { create } from 'zustand';
import * as rsssl_api from "../../utils/api";

const UserAgentStore = create((set, get) => ({
    processing: false,
    data: [],
    dataLoaded: false,
    user_agent: '',
    note: '',

    fetchData: async (action, filter) => {
        set({ processing: true });
        set({ dataLoaded: false });
        try {
            const response = await rsssl_api.doAction(action , { filter });
            if (response.request_success) {
                set({ data: response.data });
                if (response.data) {
                  set({ dataLoaded: true });
                }
                set({ processing: false });
            }
        } catch (e) {
            console.error(e);
            set({ dataLoaded: false });
        } finally {
            set({ processing: false });
        }
    },
    setNote: (note) => set({ note }),
    setUserAgent: (user_agent) => set({ user_agent }),
    setDataLoaded: (dataLoaded) => set({ dataLoaded }),
    addRow: async (user_agent, note) => {
        set({ processing: true });
        try {
            const response = await rsssl_api.doAction('rsssl_user_agent_add', { user_agent, note });
            if (response.request_success) {
                set({ dataLoaded: false });
            }
        } catch (e) {
            console.error(e);
        } finally {
            set({ processing: false, dataLoaded: true });
        }
        return { success: true, message: 'User-Agent added successfully' };
    },
    deleteValue: async (id) => {
        set({ processing: true });
        try {
            const response = await rsssl_api.doAction('rsssl_user_agent_delete', { id });
            if (response.request_success) {
                set({ dataLoaded: false });
                return { success: true, message: response.message };
            }
        } catch (e) {
            console.error(e);
        } finally {
            set({ processing: false, dataLoaded: false });
        }
    }
}));

export default UserAgentStore;