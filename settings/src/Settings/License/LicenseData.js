import {create} from 'zustand';
const UseLicenseData = create(( set, get ) => ({
    licenseStatus: rsssl_settings.licenseStatus,
    setLicenseStatus: (licenseStatus) => set(state => ({ licenseStatus })),

}));

export default UseLicenseData;

