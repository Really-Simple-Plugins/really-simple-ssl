/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
import {produce} from "immer";
import React from "react";

const EventLogDataTableStore = create((set, get) => ({

    processing: false,
    dataLoaded: false,
    pagination: {},
    dataActions: {},
    DynamicDataTable: [],
    //for faking data we add a dymmmyData
    dummyData: {data: [
        {
            "id": 1,
            "datetime": "10:02, August 16",
            "severity": "warning",
            "description": "This is a warning message",
            "event_type": "Login protection",
            "source_ip": "1.1.1.1",
            "username": "admin",
            "iso2_code": "NL",
        },
        {
            "datetime": "10:02, August 16",
            "severity": "warning",
            "description": "This is a warning message",
            "event_type": "Login protection",
            "source_ip": "1.1.1.1",
            "username": "admin",
            "iso2_code": "NL",
        },
    ]},
    dummyPagination: {
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 2,
        totalRows: 2,
    },


    fetchDynamicData: async (action) => {
        try {
            const response = await rsssl_api.doAction(
                action,
                get().dataActions
            );
            // now we set the EventLog
            if (response) {
                // if the response is empty we set the dummyData
                const data = typeof response.pagination === 'undefined' ? get().dummyData : response;
                const pagination = typeof response.pagination === 'undefined' ? get().dummyPagination : response.pagination;
                set({DynamicDataTable: data, dataLoaded: true, processing: false, pagination});
            }
        } catch (e) {
            console.log(e);
        }
    },

    handleEventTableSearch: async (search, searchColumns) => {
        //Add the search to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            })
        );
        get().fetchDynamicData('event_log');
    },

    handleEventTablePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, page, pageSize};
            })
        );
        get().fetchDynamicData('event_log');
    },

    handleEventTableRowsChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
        get().fetchDynamicData('event_log');
    },

    //this handles all pagination and sorting
    handleEventTableSort: async (column, sortDirection) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
            })
        );
        get().fetchDynamicData('event_log');
    },

    handleEventTableFilter: async (column, filterValue) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
        //we prefetch the data
        get().fetchDynamicData('event_log');
    },


}));

export default EventLogDataTableStore;