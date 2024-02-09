/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
import {produce} from "immer";
import React from "react";
import CountryDatatable from "./CountryDatatable";

const CountryDataTableStore = create((set, get) => ({

    processing: false,
    dataLoaded: false,
    pagination: {},
    dataActions: {},
    CountryDataTable: [],
    rowCleared: false,

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

    addRegion: async (region, status, dataActions) => {
        try {
            const response = await rsssl_api.doAction('add_region_to_list', {region, status});
            if (response && response.request_success) {
                // Do any immediate operations here if needed
                await get().fetchData('rsssl_limit_login_country', dataActions);
            } else {
                console.error("Failed to add region: ", response.message);
            }
        } catch (e) {
            console.error(e);
        } finally {
            set({processing: false});
        }
    },
    addRegions: async (regions, status, dataActions) => {
        try {
            const response = await rsssl_api.doAction('add_regions_to_list', {regions, status});
            if (response && response.request_success) {
                // Do any immediate operations here if needed
                await get().fetchData('rsssl_limit_login_country', dataActions);
            } else {
                console.error("Failed to add regions: ", response.message);
            }
        } catch (e) {
            console.error(e);
        } finally {
            set({processing: false});
        }

    },
    removeRegion: async (region, status, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('remove_region_from_list', {region, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchData('rsssl_limit_login_country', dataActions);
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
                console.error("Failed to remove region: ", response.message);
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
        } finally {
            set({processing: false});
        }
    },
    removeRegions: async (regions, status, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('remove_regions_from_list', {regions, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                // Potentially notify the user of success, if needed.
                await get().fetchData('rsssl_limit_login_country', dataActions);
            } else {
                // Handle any unsuccessful response if needed.
                console.error("Failed to remove regions: ", response.message);
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
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