/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {produce} from "immer";

const CountryDataTableStore = create((set, get) => ({

    processing: false,
    dataLoaded: false,
    pagination: {},
    dataActions: {
        page: 1,
        pageSize: 10,
        sortColumn: 'country_name',
        sortDirection: 'asc',
        filterColumn: '',
        filterValue: '',
        search: '',
        searchColumns: ['country_name']
    },
    CountryDataTable: [],
    rowCleared: false,
    setDataActions: async (data) => {
        set(produce((state) => {
                state.dataActions = data;
            })
        );
    },
    fetchData: async (action, dataActions) => {
        //we check if the processing is already true, if so we return
        set({processing: true});
        set({dataLoaded: false});
        set({rowCleared: true});

        if (Object.keys(dataActions).length === 0) {
            return;
        }
        try {
            const response = await rsssl_api.doAction(
                action,
                dataActions
            );
            //now we set the EventLog
            if (response && response.request_success) {
                set({CountryDataTable: response, dataLoaded: true, processing: false, pagination: response.pagination});
            }
            set({ rowCleared: true });
        } catch (e) {
            console.log(e);
        } finally {
            set({processing: false});
            set({rowCleared: false});

        }
    },

    handleCountryTableSearch: async (search, searchColumns) => {
        //Add the search to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            })
        );
    },

    handleCountryTablePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, page, pageSize};
            })
        );
    },

    handleCountryTableRowsChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
    },

    //this handles all pagination and sorting
    handleCountryTableSort: async (column, sortDirection) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
            })
        );
    },

    handleCountryTableFilter: async (column, filterValue) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
    },

    /*
* This function add a new row to the table
 */
    updateRow: async (value, status, dataActions) => {
        set({processing: true});
        let data = {
            value: value,
            status: status
        };
        try {
            const response = await rsssl_api.doAction(
                'country_update_row',
                data
            );
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchData('rsssl_limit_login_country', dataActions);
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to add country', response };
            }
        } catch (e) {
            console.log(e);
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    },
    updateRowRegion: async (value, status, dataActions) => {
        set({processing: true});
        let data = {
            value: value,
            status: status
        };
        try {
            const response = await rsssl_api.doAction(
                'region_update_row',
                data
            );
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchData('rsssl_limit_login_country', dataActions);
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to add region', response };
            }
        } catch (e) {
            console.log(e);
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    },

    resetRegions: async (region, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_entries_regions',
                {value: region}
            );
            //now we set the EventLog
            if (response && response.success) {
                await get().fetchData('rsssl_limit_login_country', dataActions);
                return { success: true, message: response.message, response };
            } else {
                return { success: false, message: response?.message || 'Failed to reset region', response };
            }
        } catch (e) {
            console.error(e);
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    },

    resetRow: async (id, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_entries',
                {id}
            );
            //now we set the EventLog
            if (response  && response.success) {
                await get().fetchData('rsssl_limit_login_country', dataActions);
                return { success: true, message: response.message, response };
            } else {
                return { success: false, message: response?.message || 'Failed to reset country', response };
            }
        } catch (e) {
            console.error(e);
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    },

    resetMultiRow: async (ids, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_entries',
                {ids}
            );
            //now we set the EventLog
            if (response && response.success) {
                await get().fetchData('rsssl_limit_login_country', dataActions);
                return { success: true, message: response.message, response };
            } else {
                return { success: false, message: response?.message || 'Failed to reset country', response };
            }
        } catch (e) {
            console.error(e);
            return { success: false, message: 'Error occurred', error: e };ß
        } finally {
            set({processing: false});
        }
    }
}));

export default CountryDataTableStore;