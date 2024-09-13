import {create} from 'zustand';
import useFields from "../FieldsData";
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";

const UseLicenseData = create(( set, get ) => ({

    licenseStatus: rsssl_settings.licenseStatus,
    setLicenseStatus: (licenseStatus) => set(state => ({ licenseStatus })),
    notices:[],
    setNotices: (notices) => set(state => ({ notices })),
    setLoadingState: () => {
        const disabledState = {output: {
                dismissible: false,
                icon: 'skeleton',
                label: __( 'Loading', 'burst-statistics' ),
                msg: false,
                plusone: false,
                url: false
            }
        };
        const skeletonNotices = [
            disabledState,
            disabledState,
            disabledState
        ];
        set({notices:skeletonNotices})
    },
    toggleActivation: async (licenseKey) => {
        get().setLoadingState();
        if (  get().licenseStatus==='valid' ) {
            await rsssl_api.runTest('deactivate_license').then( ( response ) => {
                set({
                    notices: response.notices,
                    licenseStatus: response.licenseStatus,
                })
            });
        } else {
            let data = {};
            data.license = licenseKey;
            await rsssl_api.doAction('activate_license', data).then( ( response ) => {
                set({
                    notices: response.notices,
                    licenseStatus: response.licenseStatus,
                })
            });

        }
    }
}));

export default UseLicenseData;