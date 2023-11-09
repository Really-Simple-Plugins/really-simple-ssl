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
    sorting: [],
    rowCleared: false,

    fetchDynamicData: async (action, dataActions = {}) => {
        //cool we can fetch the data so first we set the processing to true
        set({processing: true});
        set({dataLoaded: false});
        set({rowCleared: true});
        if (Object.keys(dataActions).length === 0) {
            dataActions = get().dataActions;
        }
        //now we fetch the data
        try {
            const response = await rsssl_api.doAction(
                action,
                dataActions
            );
            // now we set the EventLog
            if (response) {
                set({DynamicDataTable: response, dataLoaded: true, processing: false, pagination: response.pagination, sorting: response.sorting});
            }
        } catch (e) {
            console.log(e);
        } finally {
            set({processing: false});
            set({rowCleared: false});
        }
    },

    handleEventTableSearch: async (search, searchColumns) => {
        //Add the search to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            })
        );
    },

    handleEventTablePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, page, pageSize};
            })
        );
    },
    handleEventTableRowsChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
    },

    //this handles all pagination and sorting
    handleEventTableSort: async (column, sortDirection) => {
        set(produce((state) => {
                state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
            })
        );
    },
    handleEventTableFilter: async (column, filterValue) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
    },


}));

export default EventLogDataTableStore;