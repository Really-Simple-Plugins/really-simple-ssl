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
    ignoredItems:[],
    fixedItems:[],
    item:false,
    setIgnoredItemId: (ignoredItemId) => {
        let ignoredItems = get().ignoredItems;
        ignoredItems.push(ignoredItemId);
        set({ignoredItems: ignoredItems,  });
    },
    setFixedItemId: (fixedItemId) => {
        let fixedItems = get().fixedItems;
        fixedItems.push(fixedItemId);
        set({fixedItems: fixedItems,  });
    },
    handleModal: (showModal, modalData, item) => {
        set({showModal: showModal, modalData:modalData, item:item  });
    },
    setModalData: (modalData) => {
        set({modalData:modalData });
    },
}));

export default useModalData;
