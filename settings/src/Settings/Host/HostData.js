import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
const useHostData = create(( set, get ) => ({
    hosts: [],
    hostsLoaded:false,
    fetchHosts: async ( id ) => {
        try {
            const response = await rsssl_api.doAction('get_hosts', { id: id });

            // Handle the response
            if ( !response ) {
                console.error('No response received from the server.');
                return;
            }
            let hosts = response.hosts;
            // Set the roles state with formatted data
            set({hosts: hosts,hostsLoaded:true  });
        } catch (error) {
            console.error('Error:', error);
        }
    }
}));

export default useHostData;

