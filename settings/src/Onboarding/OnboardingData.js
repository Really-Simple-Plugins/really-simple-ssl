import create from 'zustand';
import produce from 'immer';
import * as rsssl_api from "../utils/api";
import sleeper from "../utils/sleeper.js";
import {__} from '@wordpress/i18n';
import {dispatch,} from '@wordpress/data';
const useOnboardingData = create(( set, get ) => ({
    showOnboardingModal: false,
    setShowOnBoardingModal: (showOnboardingModal) => set(state => ({ showOnboardingModal })),

}));

export default useOnboardingData;