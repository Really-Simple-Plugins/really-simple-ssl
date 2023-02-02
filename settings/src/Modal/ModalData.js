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
    dropItemFromModal: false,

    /*
     * Handle instantiation of a modal window
     * @param showModal
     * @param data
     * @param dropItem
     */
    handleModal: (showModal, modalData, dropItemFromModal) => {
        set({showModal: showModal, modalData:modalData, dropItemFromModal:dropItemFromModal });
    },
    setModalData: (modalData) => {
        set({modalData:modalData });
    }
}));

export default useModalData;
