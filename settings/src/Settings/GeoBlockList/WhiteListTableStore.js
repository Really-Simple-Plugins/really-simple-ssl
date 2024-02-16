/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
import {produce} from "immer";
import React from "react";
import GeoDatatable from "./GeoDatatable";

const WhiteListTableStore = create((set, get) => ({

    processing: false,
    dataLoaded: false,
    pagination: {},
    dataActions: {},
    WhiteListTable: [],
    rowCleared: false,

    fetchWhiteListData: async (action, dataActions) => {
        //we check if the processing is already true, if so we return
        set({processing: true});
        set({dataLoaded: false});
        set({rowCleared: true});
        // if (Object.keys(dataActions).length === 0) {
        //     return;
        // }
        try {
            const response = await rsssl_api.doAction(
                action,
                dataActions
            );
            //now we set the EventLog
            if (response && response.request_success) {
                set({WhiteListTable: response, dataLoaded: true, processing: false, pagination: response.pagination});
            }
            set({ rowCleared: true });
        } catch (e) {
            console.error(e);
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
    addRow: async (country, name, dataActions) => {
        set({processing: true});
        let data = {
            country_code: country,
            country_name: name
        };
        try {
            const response = await rsssl_api.doAction('geo_block_add_blocked_country', data);
            if (response && response.request_success) {
                await get().fetchCountryData('rsssl_geo_list', dataActions);
                // Return the success message from the API response.
                return { success: true, message: response.message, response };
            } else {
                // Return a custom error message or the API response message.
                return { success: false, message: response?.message || 'Failed to add country', response };
            }
        } catch (e) {
            console.error(e);
            // Return the caught error with a custom message.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    },

    removeRow: async (country, dataActions) => {
        set({processing: true});
        let data = {
            country_code: country
        };
        try {
            const response = await rsssl_api.doAction('geo_block_remove_blocked_country', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('rsssl_geo_list', dataActions);
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to remove country', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    },

    addRegion: async (region, dataActions) => {
        set({processing: true});
        let data = {
            region_code: region
        };
        try {
            const response = await rsssl_api.doAction('geo_block_add_blocked_region', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('rsssl_geo_list', dataActions);
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to add region', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    },
    removeRegion: async (region, dataActions) => {
        set({processing: true});
        let data = {
            region_code: region
        };
        try {
            const response = await rsssl_api.doAction('geo_block_remove_blocked_region', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('rsssl_geo_list', dataActions);
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to remove region', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});

        }
    },
}));

export default WhiteListTableStore;