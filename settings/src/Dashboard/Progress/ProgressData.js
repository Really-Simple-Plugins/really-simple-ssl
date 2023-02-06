import {create} from 'zustand';
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
    }

}));

export default useProgress;

