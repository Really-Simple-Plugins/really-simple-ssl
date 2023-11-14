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
    maskError: false,
    rowCleared: false,


    setMaskError: (maskError) => {
        set({maskError});
    },

    /*
    * This function fetches the data from the server and fills the property IpDataTable
    * Note this function works with the DataTable class on serverside
     */
    fetchIpData: async (action, dataActions) => {
        set({processing: true});
        set({dataLoaded: false});
        set({rowCleared: true});
        //if the dataActions is empty we do nothing
        if (Object.keys(dataActions).length === 0) {
            return;
        }
        try {
            const response = await rsssl_api.doAction(
                action,
                dataActions
            );
            //now we set the EventLog
            if (response) {
                //if the response is empty we set the dummyData
                set({IpDataTable: response, dataLoaded: true, processing: false, pagination: response.pagination});
            }
        } catch (e) {
            console.log(e);
        } finally {
            set({processing: false});
            set({rowCleared: false});

        }
    },

    /*
    * This function handles the search, it is called from the search from it's parent class
     */
    handleIpTableSearch: async (search, searchColumns) => {
        //Add the search to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            })
        );
    },

    /*
    * This function handles the page change, it is called from the DataTable class
     */
    handleIpTablePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, page, pageSize};
            })
        );
    },

    /*
    * This function handles the rows change, it is called from the DataTable class
     */
    handleIpTableRowsChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
    },

    /*
    * This function handles the sort, it is called from the DataTable class
     */
    handleIpTableSort: async (column, sortDirection) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
            })
        );
    },

    /*
    * This function handles the filter, it is called from the GroupSetting class
     */
    handleIpTableFilter: async (column, filterValue) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
    },

    /*
    * This function sets the ip address and is used by Cidr and IpAddressInput
     */
    setIpAddress: (ipAddress) => {
        if(ipAddress.length === 0) {
            return;
        }
        let ipRegex = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$|^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,4}|((25[0-5]|(2[0-4]|1{0,1}[0-9])?[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9])?[0-9]))|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9])?[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9])?[0-9]))$/;
        if (ipAddress.includes('/')) {
            let finalIp = '';
            // Split the input into IP and CIDR mask
            let [ip, mask] = ipAddress.split('/');
              //if , we change it to .
            ip = ip.replace(/,/g, '.');
            if (mask.length <= 0 ) {
                 if (!ipRegex.test(ip)) {
                    set({maskError: true});
                } else {
                    set({maskError: false});
                 }
                finalIp = `${ip}/${mask}`;
            } else {
                finalIp = mask ? `${ip}/${mask}` : ip;
            }
            set({ ipAddress: finalIp })
        } else {
            if (!ipRegex.test(ipAddress)) {
                set({maskError: true});
            } else {
                set({maskError: false});
            }
            set({ ipAddress: ipAddress.replace(/,/g, '.') })
        }
    },

    resetRange: () => {
        set({inputRangeValidated: false});
        set({highestIP: ''});
        set({lowestIP: ''});
        set({ipAddress: ''});
        set({maskError: false});
    },

    /*
    * This function sets the status selected and is used by Cidr and IpAddressInput and from the options
     */
    setStatusSelected: (statusSelected) => {
        set({statusSelected});
    },

    /*
    * This function sets the id selected and is used by Cidr and IpAddressInput and from the options
     */
    setId: (idSelected) => {
        set({idSelected});
    },

    /*
    * This function updates the row only changing the status
     */
    updateRow: async (id, status, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'ip_update_row',
                {id, status}
            );
            //now we set the EventLog
            if (response && response.request_success) {
                await get().fetchIpData('ip_list', dataActions);
            }
        } catch (e) {
            console.log(e);
        }  finally {
            set({processing: false});
        }
    },

    /*
    * This function add a new row to the table
     */
    addRow: async (ipAddress, status, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('ip_add_ip_address', {ipAddress, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchIpData('ip_list', dataActions);
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
                console.log("Failed to add IP address: ", response.message);
                //we also clear the form
                set({ipAddress: ''});
            }
        } catch (e) {
            console.log(e);
            // Notify the user of an error.
        } finally {
            set({processing: false});
            //we also clear the form
            set({ipAddress: ''});
        }
    },

    /**
     * This function validates the ip address string if it is a proper ip address
     * This checks ipv4 addresses
     *
     * @param ip
     * @returns {boolean}
     */
    validateIpv4: (ip) => {
        const parts = ip.split(".");
        if (parts.length !== 4) return false;
        for (let part of parts) {
            const num = parseInt(part, 10);
            console.log(num);
            if (isNaN(num) || num < 0 || num > 255) return false;
        }
        return true;
    },

    /**
     * This function validates the ip address string if it is a proper ip address
     * This checks ipv6 addresses
     *
     * @param ip
     * @returns {boolean}
     */
    validateIpv6: (ip) => {
        const parts = ip.split(":");
        if (parts.length !== 8) return false;

        for (let part of parts) {
            if (part.length > 4 || !/^[0-9a-fA-F]+$/.test(part)) return false;
        }

        return true;
    },

    extendIpV6: (ip) => {
        // Handle the special case of '::' at the start or end
        if (ip === '::') ip = '0::0';

        // Handle the '::' within the address
        if (ip.includes('::')) {
            const parts = ip.split('::');
            if (parts.length > 2) return false;

            const left = parts[0].split(':').filter(Boolean);
            const right = parts[1].split(':').filter(Boolean);

            // Calculate how many zeros are needed
            const zerosNeeded = 8 - (left.length + right.length);

            // Concatenate all parts with the appropriate number of zeros
            return [...left, ...Array(zerosNeeded).fill('0'), ...right].join(':');
        }
        return ip;
    },

    /**
     * This function converts the ip address to a number
     *
     * @param ip
     * @returns {*}
     */
    ipToNumber: (ip) => {
        if (get().validateIpv4(ip)) {
            return get().ipV4ToNumber(ip);
        } else if (get().validateIpv6(get().extendIpV6(ip))) {
            return get().ipV6ToNumber(get().extendIpV6(ip));
        }
    },

    /**
     * This function converts the ip address to a number if it is a ipv4 address
     * @param ip
     * @returns {*}
     */
    ipV4ToNumber: (ip) => {
        return ip.split(".").reduce((acc, cur) => (acc * 256 + parseInt(cur, 10)) >>> 0, 0);
    },

    /**
     * This function converts the ip address to a number if it is a ipv6 address
     * @param ip
     * @returns {*}
     */
    ipV6ToNumber: (ip) => {
        return ip.split(":").reduce((acc, cur) => {
            const segmentValue = parseInt(cur, 16);
            if (isNaN(segmentValue)) {
                console.warn(`Invalid segment in IPv6 address: ${oldIp}`);
                return acc;
            }
            return (acc << BigInt(16)) + BigInt(segmentValue);
        }, BigInt(0));
    },
    // ipV6ToNumber: (ip) => {
    //     return ip.split(":").reduce((acc, cur) => (acc << BigInt(16)) + BigInt(parseInt(cur, 16)), BigInt(0));
    // },

    /**
     * This function validates the ip range, if the lowest is lower than the highest
     * This checks ipv4 and ipv6 addresses
     *
     * @param lowest
     * @param highest
     */
    validateIpRange: (lowest, highest) => {
        set({inputRangeValidated: false});
        let from = '';
        let to = '';
        //first we determine if the IP is ipv4 or ipv6
        if (lowest && highest) {
            if (get().validateIpv4(lowest) && get().validateIpv4(highest)) {
                //now we check if the lowest is lower than the highest
                if (get().ipToNumber(lowest) > get().ipToNumber(highest)) {
                    console.warn('lowest is higher than highest');
                    set({inputRangeValidated: false});
                    return;
                }
                from = lowest;
                to = highest;
                set({inputRangeValidated: true});
            } else if (get().validateIpv6(get().extendIpV6(lowest)) && get().validateIpv6(get().extendIpV6(highest))) {
                //now we check if the lowest is lower than the highest
                if (get().ipToNumber(get().extendIpV6(lowest)) > get().ipToNumber(get().extendIpV6(highest))) {
                    console.warn('lowest is higher than highest');
                    set({inputRangeValidated: false});
                    return;
                }
                from = get().extendIpV6(lowest);
                to = get().extendIpV6(highest);
                set({inputRangeValidated: true});
            }
        }
        if (get().inputRangeValidated) {
            let lowest = from;
            let highest = to;
            set({ipRange: {lowest, highest}});
            get().fetchCidrData('get_mask_from_range');
        }
    },

    /**
     * This function fetches the cidr data from the server and sets the cidr and ip_count
     * This function is called from the Cidr class
     *
     * @param action
     * @returns {Promise<void>}
     */
    fetchCidrData: async (action) => {
        try {
            const response = await rsssl_api.doAction(
                action,
                get().ipRange
            );
            //now we set the EventLog
            if (response) {
               //we set the cidrFound and cidrCount
                set({cidr: response.cidr, ipAddress: response.cidr, ip_count: response.ip_count, canSetCidr: true});
                //we reload the event log

            }
        } catch (e) {
            console.log(e);
        }
    },

    updateMultiRow: async (ids, status, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'ip_update_multi_row',
                {ids, status}
            );
            //now we set the EventLog
            if (response && response.request_success) {
                await get().fetchIpData('ip_list', dataActions);
            }
        } catch (e) {
            console.log(e);
        } finally {
            set({processing: false});
        }
    },

    resetRow: async (id, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('delete_entry', {id} );
            //now we set the EventLog
            if (response && response.request_success) {
                await get().fetchIpData('ip_list', get().dataActions);
            } else {
                console.log("Failed to remove IP address: ", response.message);
            }
        } catch (e) {
            console.log(e);
        } finally {
            set({processing: false});
        }
    },

    resetMultiRow: async (ids, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_multi_entries',
                {ids}
            );
            //now we set the EventLog
            if (response && response.request_success) {
                await get().fetchIpData('ip_list', get().dataActions);
            }
        } catch (e) {
            console.log(e);
        } finally {
            set({processing: false});
        }
    }
}));

export default IpAddressDataTableStore;