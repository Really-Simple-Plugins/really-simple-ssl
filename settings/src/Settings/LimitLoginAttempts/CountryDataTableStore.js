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
    //for faking data we add a dymmmyData
    dummyData: { data: [
        {
            "id": 1,
            "iso2_code": "US",
            "iso3_code": "USA",
            "country_name": "United States",
            "region": "North America",
            "region_code": "NA",
        },
        {
            "id": 2,
            "iso2_code": "CA",
            "iso3_code": "CAN",
            "country_name": "Canada",
            "region": "North America",
            "region_code": "NA",
        },
        ]},
    dummyPagination: {
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 2,
        totalRows: 2,
    },


    fetchCountryData: async (action) => {
        try {
            const response = await rsssl_api.doAction(
                action,
                get().dataActions
            );
            //now we set the EventLog
            if (response) {
                if (typeof response.pagination === 'undefined') {
                    set({CountryDataTable: get().dummyData, dataLoaded: true, processing: false, pagination: get().dummyPagination});
                } else {
                    set({CountryDataTable: response, dataLoaded: true, processing: false, pagination: response.pagination});
                }
            }
        } catch (e) {
            console.log(e);
        }
    },

    handleCountryTableSearch: async (search, searchColumns) => {
        //Add the search to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            })
        );
        get().fetchCountryData('country_list');
    },

    handleCountryTablePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, page, pageSize};
            })
        );
        get().fetchCountryData('country_list');
    },

    handleCountryTableRowsChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
        get().fetchCountryData('country_list');
    },

    //this handles all pagination and sorting
    handleCountryTableSort: async (column, sortDirection) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
            })
        );
        get().fetchCountryData('country_list');
    },

    handleCountryTableFilter: async (column, filterValue) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
        get().fetchCountryData('country_list');
    },

    /*
* This function add a new row to the table
 */
    addRow: async (country, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('add_country_to_list', {country, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('country_list');
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
                console.log("Failed to add country: ", response.message);
            }
        } catch (e) {
            console.log(e);
            // Notify the user of an error.
        } finally {
            set({processing: false});
        }
    },

    addRowMultiple: async (countries, status) => {
        set( {processing: true});
        try {
            const response = await rsssl_api.doAction('add_countries_to_list', {countries, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('country_list');
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
                console.log("Failed to add countries: ", response.message);
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
        }
    },

    removeRowMultiple: async (countries, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('remove_countries_from_list', {countries, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('country_list');
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
                console.log("Failed to remove countries: ", response.message);
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
        }
    },

    removeRow: async (country, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('remove_country_from_list', {country, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('country_list');
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
                console.log("Failed to remove country: ", response.message);
            }
        } catch (e) {
            console.log(e);
            // Notify the user of an error.
        } finally {
            set({processing: false});
        }
    },

    addRegion: async (region, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('add_region_to_list', {region, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('country_list');
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
                console.error("Failed to add region: ", response.message);
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
        } finally {
            set({processing: false});
        }
    },
    removeRegion: async (region, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('remove_region_from_list', {region, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchCountryData('country_list');
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
                console.error("Failed to remove region: ", response.message);
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
        }
    },
    updateMultiRow: async (ids, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'update_multi_row',
                {ids, status}
            );
            //now we set the EventLog
            if (response) {
                await get().fetchCountryData('country_list');
            }
        } catch (e) {
            console.log(e);
        }
    },

    resetRow: async (id) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_entry',
                {id}
            );
            //now we set the EventLog
            if (response) {
                await get().fetchCountryData('country_list');
            }
        } catch (e) {
            console.log(e);
        }
    },

    resetMultiRow: async (ids) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_multi_entries',
                {ids}
            );
            //now we set the EventLog
            if (response) {
                await get().fetchCountryData('country_list');
            }
        } catch (e) {
            console.log(e);
        }
    }
}));

export default CountryDataTableStore;