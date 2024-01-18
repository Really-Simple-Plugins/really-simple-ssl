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

    fetchCountryData: async (action, dataActions) => {
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
    addRow: async (country, name, dataActions) => {
        set({processing: true});
        // we make an array of country and name
        let data = {
                country_code: country,
                country_name: name
            };
        try {
            const response = await rsssl_api.doAction('geo_block_add_blocked_country', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('rsssl_geo_list', dataActions);
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
            }
        } catch (e) {
            console.log(e);
            // Notify the user of an error.
        } finally {
            set({processing: false});
        }
    },

    addRowMultiple: async (countries, dataActions) => {
        set({processing: true});
        let data = {
            country_code: countries
        }
        try {
            const response = await rsssl_api.doAction('geo_block_add_blocked_country', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.success) {
                await get().fetchCountryData('rsssl_geo_list', dataActions);
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
        } finally {
            set({processing: false});
        }
    },

    removeRowMultiple: async (countries, dataActions) => {
        set({processing: true});
        let data = {
            country_code: countries
        }
        try {
            const response = await rsssl_api.doAction('geo_block_remove_blocked_country', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.success) {
                await get().fetchCountryData('rsssl_geo_list', dataActions);
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
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
            } else {
                // Handle any unsuccessful response if needed.
            }
        } catch (e) {
            console.log(e);
            // Notify the user of an error.
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
            } else {
                // Handle any unsuccessful response if needed.
            }
        } catch (e) {
            console.log(e);
            // Notify the user of an error.
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
            } else {
                // Handle any unsuccessful response if needed.
            }
        } catch (e) {
            console.log(e);
            // Notify the user of an error.
        } finally {
            set({processing: false});

        }
    },
    updateMultiRow: async (ids, status, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'update_multi_row',
                {ids, status}
            );
            //now we set the EventLog
            if (response) {
                await get().fetchCountryData('rsssl_geo_list', dataActions);
            }
        } catch (e) {
            console.log(e);
        } finally {
            set({processing: false});
        }
    }
}));

export default GeoDataTableStore;