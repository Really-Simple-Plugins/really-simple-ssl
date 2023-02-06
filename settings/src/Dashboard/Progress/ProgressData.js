import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
const useProgress = create(( set, get ) => ({
    filter:'all',
    notices: [],
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
    getProgressData: () => {
        rsssl_api.runTest('progressData', 'refresh').then( ( response ) => {
            if ( response.error ) {
                this.setState({
                    error: response.error,
                });
            } else {
                this.progressText = response.text;
                this.filter = response.filter;
                this.percentageCompleted = response.percentage;
                this.notices = response.notices;
                this.progressLoaded = true;

                this.setState({
                    progressLoaded: this.progressLoaded,
                    progressText: this.progressText,
                    filter: this.filter,
                    notices: this.notices,
                    percentageCompleted: this.percentageCompleted,
                });
                this.props.updateBlockProps('notices', this.notices);
            }
        });
    }

}));

export default useProgress;

