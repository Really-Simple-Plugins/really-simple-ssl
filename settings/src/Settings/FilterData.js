// FilterData.js
import {create} from 'zustand';

const filterData = create((set, get) => ({
    selectedFilter: [],
    setSelectedFilter: (selectedFilter, activeGroupId) => {
        set((state) => ({
            //we make it an array, so we can have multiple filters
            selectedFilter: {...state.selectedFilter, [activeGroupId]: selectedFilter},
        }));
    },
    getCurrentFilter: (activeGroupId) => get().selectedFilter[activeGroupId],
}));

export default filterData;
