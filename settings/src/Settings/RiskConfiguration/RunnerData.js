import {create} from "zustand";

const useRunnerData = create((set, get) => ({
    // loadingState : false,
    // setLoadingState: (state) => set({loadingState: state}),
    // title: '',
    // setTitle: (title) => set({title: title}),
    // time: 0,
    // setTime: (time) => set({time: time}),
    // delay: 0,
    // setDelay: (delay) => set({delay: delay}),
    step:0,
    setStep: (step) => set({step: step}),
}));

export default useRunnerData;