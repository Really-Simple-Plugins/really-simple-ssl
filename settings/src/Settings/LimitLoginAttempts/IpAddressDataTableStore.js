/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
import {produce} from "immer";
import React from "react";

const IpAddressDataTableStore = create((set, get) => ({

    processing: false,
    dataLoaded: false,
    pagination: {},
    dataActions: {},
    IpDataTable: [],

    fetchIpData: async (action) => {
        try {
            const response = await rsssl_api.doAction(
                action,
                get().dataActions
            );
            //now we set the EventLog
            if (response) {
                set({DynamicDataTable: response, dataLoaded: true, processing: false, pagination: response.pagination});
            }
        } catch (e) {
            console.log(e);
        }
    },

    handleTableSearch: async (search, searchColumns) => {
        //Add the search to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            })
        );
        get().fetchIpData('ip_list');
    },

    handleTablePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, page, pageSize};
            })
        );
        get().fetchIpData('ip_list');
    },

    handleTableRowsChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
        get().fetchIpData('ip_list');
    },

    //this handles all pagination and sorting
    handleTableSort: async (column, sortDirection) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
            })
        );
        get().fetchIpData('ip_list');
    },

    handleTableFilter: async (column, filterValue) => {
        console.log(filterValue);
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
        get().fetchIpData('ip_list');
    },

}));

export default IpAddressDataTableStore;