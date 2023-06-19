import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
const useProgress = create(( set, get ) => ({
    filter:'all',
    progressText:'',
    notices: [],
    error:false,
    percentageCompleted:0,
    progressLoaded:false,
    setFilter: (filter) => {
        sessionStorage.rsssl_task_filter = filter;

        set(state => ({ filter }))
    },
    fetchFilter: () => {
        if ( typeof (Storage) !== "undefined" && sessionStorage.rsssl_task_filter  ) {
            let filter = sessionStorage.rsssl_task_filter;
            set(state => ({ filter:filter }))

        }
    },
    getProgressData: async () => {
        const {percentage, text, notices, error } = await rsssl_api.runTest('progressData', 'refresh').then( ( response ) => {
            return response;
        });

        set(state => ({
            notices:notices,
            percentageCompleted:percentage,
            progressText:text,
            progressLoaded:true,
            error:error,
        }))
    },
    dismissNotice: async (noticeId) => {
        let notices = get().notices;
        notices = notices.filter(function (notice) {
            return notice.id !== noticeId;
        });
        set(state => ({ notices:notices }))
        const {percentage} = await rsssl_api.runTest('dismiss_task', noticeId);
        set({ percentageCompleted:percentage })
    }
}));

export default useProgress;

