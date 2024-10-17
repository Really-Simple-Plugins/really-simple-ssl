/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";

const WhiteListTableStore = create((set, get) => ({

    processing: false,
    processing_block: false,
    dataLoaded: false,
    dataLoaded_block: false,
    pagination: {},
    dataActions: {},
    WhiteListTable: [],
    BlockListData: [],
    rowCleared: false,
    maskError: false,
    ipAddress: '',
    note: '',


    fetchWhiteListData: async (action) => {
        //we check if the processing is already true, if so we return
        set({processing: true});
        set({dataLoaded: false});
        set({rowCleared: true});

        try {
            const response = await rsssl_api.doAction(
                action
            );
            //now we set the EventLog
            if (response && response.request_success) {
                set({WhiteListTable: response, dataLoaded: true, processing: false, pagination: response.pagination});
            }
            set({ rowCleared: true });
        } catch (e) {
            console.error(e);
        } finally {
            set({processing: false});
            set({rowCleared: false});

        }
    },

    fetchData: async (action, filter) => {
//we check if the processing is already true, if so we return
        set({processing_block: true});
        set({rowCleared: true});

        try {
            const response = await rsssl_api.doAction(
                action,
                {
                    filterValue: filter
                }
            );
            //now we set the EventLog
            if (response && response.request_success) {
                set({BlockListData: response, dataLoaded: true, processing: false, pagination: response.pagination});
            }
            set({ rowCleared: true });
        } catch (e) {
            console.error(e);
        } finally {
            set({dataLoaded_block: true})
            set({processing_block: false});
            set({rowCleared: false});

        }
    },

    resetRow: async (id, dataActions) => {
        set({processing: true});
        let data = {
            id: id
        };
        try {
            const response = await rsssl_api.doAction('geo_block_reset_ip', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to reset Ip', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    }
    ,
    updateRow: async (ip, note, status, filter) => {
        set({processing: true});
        let data = {
            ip_address: ip,
            note: note,
            status: status
        };
        try {
            const response = await rsssl_api.doAction('geo_block_add_white_list_ip', data);
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                    await get().fetchWhiteListData('rsssl_geo_white_list');
                    return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to add Ip', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
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
                await get().fetchCountryData('rsssl_geo_white_list');
                await get().fetchData('rsssl_geo_block_list', {filterValue: 'all'});
                // Potentially notify the user of success, if needed.
                return { success: true, message: response.message, response };
            } else {
                // Handle any unsuccessful response if needed.
                return { success: false, message: response?.message || 'Failed to remove country', response };
            }
        } catch (e) {
            console.error(e);
            // Notify the user of an error.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
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

    setNote: (note) => {
        set({note});
    },

    resetRange: () => {
        set({inputRangeValidated: false});
        set({highestIP: ''});
        set({lowestIP: ''});
        set({ipAddress: ''});
        set({maskError: false});
    },

    setDataLoaded: (dataLoaded) => {
        set({dataLoaded});
        set({dataLoaded_block: dataLoaded});
    }

}));

export default WhiteListTableStore;