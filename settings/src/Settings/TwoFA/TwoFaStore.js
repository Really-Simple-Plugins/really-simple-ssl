import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
const TwoFaData = create(( set, get ) => ({
    roles: [],
    rolesLoaded:false,
    fetchRoles: async (fieldId) => {
        try {
            // Fetch the roles from the server using rsssl_api.getUserRoles()
            const response = await rsssl_api.getUserRoles(fieldId);

            // Handle the response
            if (!response) {
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
            const formattedData = dataArray.map((role, index) => ({ value: role, label: role }));

            // Set the roles state with formatted data
            set({roles: formattedData,rolesLoaded:true  });


        } catch (error) {
            console.error('Error:', error);
        }
    }
}));

export default TwoFaData;

