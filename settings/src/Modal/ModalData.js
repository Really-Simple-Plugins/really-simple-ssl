import {create} from 'zustand';
import produce from 'immer';
import * as rsssl_api from "../utils/api";
import sleeper from "../utils/sleeper.js";
import {__} from '@wordpress/i18n';
import {dispatch,} from '@wordpress/data';

const useModalData = create(( set, get ) => ({
    modalData: [],
    buttonsDisabled: false,
    showModal:false,
    handleModal: (showModal, modalData) => {
        set({showModal: showModal, modalData:modalData });
    },
    setModalData: (modalData) => {
        set({modalData:modalData });
    },
}));

export default useModalData;
