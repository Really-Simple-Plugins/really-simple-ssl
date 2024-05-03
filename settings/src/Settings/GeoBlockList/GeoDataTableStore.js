/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
import {produce} from "immer";
import React from "react";
import GeoDatatable from "./GeoDatatable";

const GeoDataTableStore = create((set, get) => ({

    processing: false,
    dataLoaded: false,
    pagination: {},
    dataActions: {},
    CountryDataTable: [],
    rowCleared: false,

    fetchCountryData: async (action, filterValue) => {
        //we check if the processing is already true, if so we return
        set({
            processing: true,
            rowCleared: true,
            dataLoaded: false
        });
        // if the filterValue is not set, we do nothing.
        if (!filterValue) {
            set({processing: false});
            return;
        }
        try {
            const response = await rsssl_api.doAction(
                action, {filterValue}
            );
            //now we set the EventLog
            if (response && response.request_success) {
                set({
                    CountryDataTable: response,
                    dataLoaded: true,
                    processing: false
                });
            }
            set({ rowCleared: true });
        } catch (e) {
            console.error(e);
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
    addRow: async (country, name) => {
        set({rowCleared: false});
        let data = {
            country_code: country,
            country_name: name
        };
        try {
            const response = await rsssl_api.doAction('geo_block_add_blocked_country', data);
            if (response && response.request_success) {
                set({rowCleared: true});
                return { success: true, message: response.message, response };
            } else {
                // Return a custom error message or the API response message.
                return { success: false, message: response?.message || 'Failed to add country', response };
            }
        } catch (e) {
            console.error(e);
            // Return the caught error with a custom message.
            return { success: false, message: 'Error occurred', error: e };
        }
    },

    addMultiRow: async (countries) => {
        set({processing: true});
        set({rowCleared: false});
        let data = {
            country_codes: countries
        };
        try {
            const response = await rsssl_api.doAction('geo_block_add_blocked_country', data);
            if (response && response.request_success) {
                set({rowCleared: true});
                // Return the success message from the API response.
                return { success: true, message: response.message, response };
            } else {
                set({rowCleared: true});
                // Return a custom error message or the API response message.
                return { success: false, message: response?.message || 'Failed to add countries', response };
            }
        } catch (e) {
            console.error(e);
            set({rowCleared: true});
            // Return the caught error with a custom message.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
            set({rowCleared: true});
        }
    },

    removeRowMulti: async (countries, dataActions) => {
        set({processing: true});
        set({rowCleared: false});
        let data = {
            country_codes: countries
        };
        try {
            const response = await rsssl_api.doAction('geo_block_remove_blocked_country', data);
            if (response && response.request_success) {
                set({rowCleared: true});
                // Return the success message from the API response.
                return { success: true, message: response.message, response };
            } else {
                // Return a custom error message or the API response message.
                return { success: false, message: response?.message || 'Failed to remove countries', response };
            }
        }
        catch (e) {
            console.error(e);
            set({rowCleared: true});
            // Return the caught error with a custom message.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({rowCleared: true});
            set({processing: false});
        }
    },

    removeRow: async (country) => {
        set({processing: true});
        set({rowCleared: false});
        let data = {
            country_code: country
        };
        try {
            const response = await rsssl_api.doAction('geo_block_remove_blocked_country', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                set({rowCleared: true});
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                set({rowCleared: true});
                return { success: false, message: response?.message || 'Failed to remove country', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({rowCleared: true});
            set({processing: false});
        }
    },

    addRegion: async (region) => {
        set({processing: true});
        set({rowCleared: false});
        let data = {
            region_code: region
        };
        try {
            const response = await rsssl_api.doAction('geo_block_add_blocked_region', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                set({rowCleared: true});
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                set({rowCleared: true});
                return { success: false, message: response?.message || 'Failed to add region', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
            set({rowCleared: true});
        }
    },

    addRegionsMulti: async (regions, dataActions) => {
        set({processing: true});
        set({rowCleared: false});
        let data = {
            region_codes: regions
        };
        try {
            const response = await rsssl_api.doAction('geo_block_add_blocked_region', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                set({rowCleared: true});
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                set({rowCleared: true});
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to add regions', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({rowCleared: true});
            set({processing: false});
        }
    },

    removeRegion: async (region) => {
        set({processing: true});
        set({rowCleared: false});
        let data = {
            region_code: region
        };
        try {
            const response = await rsssl_api.doAction('geo_block_remove_blocked_region', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                set({rowCleared: true});
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                set({rowCleared: true});
                return { success: false, message: response?.message || 'Failed to remove region', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
            set({rowCleared: true});
        }
    },

    removeRegionMulti: async (regions) => {
        set({processing: true});
        set({rowCleared: false});
        let data = {
            region_codes: regions
        };
        try {
            const response = await rsssl_api.doAction('geo_block_remove_blocked_region', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                // Potentially notify the user of success, if needed.
                set({rowCleared: true});
                return { success: true, message: response.message, response };
            } else {
                set({rowCleared: true});
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to remove regions', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            set({rowCleared: true});
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
            set({rowCleared: true});
        }
    },

    resetRowSelection: async (on_off) => {
        set({rowCleared: on_off});
    }
}));

export default GeoDataTableStore;