import {create} from 'zustand';
import * as rsssl_api from "../utils/api";
const useOnboardingData = create(( set, get ) => ({
    showOnboardingModal: false,
    dismissModal: () => {
        let data={};
        data.dismiss = true;
        set((state) => ({showOnboardingModal: false}));
        rsssl_api.doAction('dismiss_modal', data).then(( response ) => { });
    },
    setShowOnBoardingModal: (showOnboardingModal) => set(state => ({ showOnboardingModal })),

}));

export default useOnboardingData;