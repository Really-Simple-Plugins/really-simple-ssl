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
        const {error, percentageCompleted, progressText, filter, notices} = await rsssl_api.runTest('progressData', 'refresh').then( ( response ) => {
            return response;
        });
        if ( error ) {
            set(state => ({ error:error }))
        } else {
            set(state => ({
                percentageCompleted:percentage,
                progressText:progressText,
                filter:filter,
                progressLoaded:true,
            }))

        }
        if ( response.error ) {
            this.setState({
                error: response.error,
            });
        } else {
            this.percentageCompleted = response.percentage;
            this.notices = response.notices;

            this.setState({
                progressLoaded: this.progressLoaded,
                progressText: this.progressText,
                filter: this.filter,
                notices: this.notices,
                percentageCompleted: this.percentageCompleted,
            });
            this.props.updateBlockProps('notices', this.notices);
        }
    },
    dismissNotice: async (noticeId) => {
        let notices = get().notices;
        notices = notices.filter(function (notice) {
            return notice.id !== noticeId;
        });
        set(state => ({ notices:notices }))

        const {error, percentageCompleted} = await rsssl_api.runTest('dismiss_task', noticeId).then(( response ) => {
            if ( error ) {
                set(state => ({ error:error }))
            } else {
                set(state => ({ percentage:percentageCompleted }))

            }
        });
    }


}));

export default useProgress;

