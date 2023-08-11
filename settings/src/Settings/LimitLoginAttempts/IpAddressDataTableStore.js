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
    highestIP: '',
    lowestIP: '',
    statusSelected: 'blocked',
    inputRangeValidated: false,
    cidr: '',
    ip_count: '',
    canSetCidr: false,
    ipRange: {},
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
    },

    validateIpv4: (ip) => {
        const parts = ip.split(".");
        if (parts.length !== 4) return false;
        for (let part of parts) {
            const num = parseInt(part, 10);
            if (isNaN(num) || num < 0 || num > 255) return false;
        }
        return true;
    },

    validateIpv6: (ip) => {
        const parts = ip.split(":");
        if (parts.length !== 8) return false;
        for (let part of parts) {
            if (part.length > 4 || !/^[0-9a-fA-F]+$/.test(part)) return false;
        }
        return true;
    },

    ipToNumber: (ip) => {
        if (get().validateIpv4(ip)) {
            return get().ipV4ToNumber(ip);
        } else if (get().validateIpv6(ip)) {
            return get().ipV6ToNumber(ip);
        }
    },

    ipV4ToNumber: (ip) => {
        return ip.split(".").reduce((acc, cur) => (acc << 8) + parseInt(cur, 10), 0);
    },

    ipV6ToNumber: (ip) => {
        return ip.split(":").reduce((acc, cur) => (acc << BigInt(16)) + BigInt(parseInt(cur, 16)), BigInt(0));
    },

    validateIpRange: (lowest, highest) => {
        //first we determine if the IP is ipv4 or ipv6
        if (lowest && highest) {
            if (get().validateIpv4(lowest) && get().validateIpv4(highest)) {
                //now we check if the lowest is lower than the highest
                if (get().ipToNumber(lowest) > get().ipToNumber(highest)) {
                    set({inputRangeValidated: false});
                    return;
                }
                set({inputRangeValidated: true});
            } else if (get().validateIpv6(lowest) && get().validateIpv6(highest)) {
                //now we check if the lowest is lower than the highest
                if (get().ipToNumber(lowest) > get().ipToNumber(highest)) {
                    set({inputRangeValidated: false});
                    return;
                }
                set({inputRangeValidated: true});
            }
        }
        if (get().inputRangeValidated) {
            set({ipRange: {lowest, highest}});
        }
    },


    fetchCidrData: async (action) => {
        try {
            const response = await rsssl_api.doAction(
                action,
                get().ipRange
            );
            //now we set the EventLog
            if (response) {
               //we set the cidrFound and cidrCount
                set({cidr: response.cidr, ip_count: response.ip_count, canSetCidr: true});
            }
        } catch (e) {
            console.log(e);
        }
    }


}));

export default IpAddressDataTableStore;