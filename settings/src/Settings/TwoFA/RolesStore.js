import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {produce} from "immer";
const useRolesData = create(( set, get ) => ({
    roles: [],
    rolesLoaded:false,
    fetchRoles: async ( id ) => {
        try {
            // Fetch the roles from the server using rsssl_api.getUserRoles()
            const response = await rsssl_api.doAction('get_roles', { id: id });

            // Handle the response
            if ( !response ) {
                console.error('No response received from the server.');
                return;
            }

            const data = response.roles;
            if (typeof data !== 'object') {
                console.error('Invalid data received in the server response. Expected an object.');
                return;
            }

            // Convert the object to an array
            const dataArray = Object.values(data);

            // Format the data into options array for react-select

            const formattedData = dataArray.map((role, index) => ({ value: role, label: role.charAt(0).toUpperCase() + role.slice(1) }));
            // Set the roles state with formatted data
            set({roles: formattedData,rolesLoaded:true  });


        } catch (error) {
            console.error('Error:', error);
        }
    }
}));

export default useRolesData;

