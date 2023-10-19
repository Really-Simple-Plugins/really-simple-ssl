import {create} from "zustand";
import {produce} from "immer";
import {__} from "@wordpress/i18n";

const useRunnerData = create((set, get) => ({
    showIntro:false,
    setShowIntro: (value) => set({showIntro: value}),
    disabled:true,
    introCompleted: false, //for storing the status of the first run
    setIntroCompleted: (value) => {
        set({introCompleted: value});
    },
    setDisabled(disabled) {
        set(state => ({disabled}))
    },
    list:[
        {
            'id':'initialize',
            'icon':'loading',
            'color':'black',
            'text': __("Preparing vulnerability detection", "really-simple-ssl"),
        },
        {
            'id':'fetchVulnerabilities',
            'icon':'loading',
            'color':'black',
            'text': __("Collecting plugin, theme and core data", "really-simple-ssl"),
        },
        {
            'id':'scan',
            'icon':'loading',
            'color':'black',
            'text': __("Scanning your WordPress configuration", "really-simple-ssl"),
        },
        {
            'id':'enabled',
            'icon':'loading',
            'color':'black',
            'text': __("Reporting enabled", "really-simple-ssl"),
        },
    ],
    setItemCompleted: async (id) => {
        const stepIndex = get().list.findIndex(item => {
            return item.id===id;
        });
        set(
            produce((state) => {
                const item = state.list[stepIndex];
                item.icon = 'circle-check';
                item.color = 'green';
                state.list[stepIndex] = item;
            })
        )
    },

}));

export default useRunnerData;