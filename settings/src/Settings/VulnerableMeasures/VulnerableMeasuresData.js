import {create} from "zustand";
import {immer} from "zustand/middleware/immer";
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";

export const UseRiskDetection = create(immer((set, get) => ({
        riskDetectionData: [],
        //setting the data
        setRiskDetectionData: (data) => {
            set((state) => {
                state.riskDetectionData = data;
            });
        }

    })
));
