import {create} from "zustand";
import * as rsssl_api from "../../utils/api";

const useWPVul = create((set, get) => ({
    // Stuff we need for the WPVulData component
    wpVulData: false, //for letting the component know if there is data
    vulnerabilities: [], //for storing the data
    HighestRisk: false, //for storing the highest risk

    /*
    * Setters
     */


    /*
    * Getters
     */

    /*
    * Functions
     */

}));
