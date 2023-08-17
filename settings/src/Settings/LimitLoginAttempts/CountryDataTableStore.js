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

}));

export default CountryDataTableStore;