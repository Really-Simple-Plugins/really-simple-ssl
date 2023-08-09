/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
import {produce} from "immer";
import React from "react";

const IpAddressDataTableStore = create((set, get) => ({

    processing: false,
    dataLoaded: false,
    ipAddress: '',
    statusSelected: '',
    idSelected: '',
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
                set({IpDataTable: response, dataLoaded: true, processing: false, pagination: response.pagination});
            }
        } catch (e) {
            console.log(e);
        }
    },

    handleIpTableSearch: async (search, searchColumns) => {
        //Add the search to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            })
        );
        get().fetchIpData('ip_list');
    },

    handleIpTablePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, page, pageSize};
            })
        );
        get().fetchIpData('ip_list');
    },

    handleIpTableRowsChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
        get().fetchIpData('ip_list');
    },

    //this handles all pagination and sorting
    handleIpTableSort: async (column, sortDirection) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
            })
        );
        get().fetchIpData('ip_list');
    },

    handleIpTableFilter: async (column, filterValue) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
        get().fetchIpData('ip_list');
    },

    setIpAddress: (ipAddress) => {
        set({ipAddress});
    },

    setStatusSelected: (statusSelected) => {
        set({statusSelected});
    },

    setId: (idSelected) => {
        set({idSelected});
    },

    updateRow: async (id, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'ip_update_row',
                {id, status}
            );
            //now we set the EventLog
            if (response) {
                get().fetchIpData('ip_list');
            }
        } catch (e) {
            console.log(e);
        }
    },

    addRow: async (ipAddress, status) => {
        console.log(ipAddress, status);
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'ip_add_ip_address',
                {ipAddress, status}
            );
            //now we set the EventLog
            if (response) {
                get().fetchIpData('ip_list');
            }
        } catch (e) {
            console.log(e);
        }
    }



}));

export default IpAddressDataTableStore;