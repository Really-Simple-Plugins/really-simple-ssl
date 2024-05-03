/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
import {produce} from "immer";
import React from "react";

const UseRiskData = create((set, get) => ({

    dummyRiskData: [
        {id:'force_update',name:'Force Update',value:'l',description:__('Force update the plugin or theme','really-simple-ssl')},
        {id:'quarantine',name:'Quarantine',value:'m',description:__('Isolates the plugin or theme if no update can be performed','really-simple-ssl')},
    ],
    riskData:[],
    riskLevels: {
        l: 1,
        m: 2,
        h: 3,
        c: 4,
    },
    vulnerabilities: [],
    processing:false,
    dataLoaded: false,
    // Stuff we need for the WPVulData component
    updates: 0, //for letting the component know if there are updates available
    HighestRisk: false, //for storing the highest risk
    lastChecked: '', //for storing the last time the data was checked
    vulEnabled: false, //for storing the status of the vulnerability scan
    riskNaming: {}, //for storing the risk naming
    vulList: [], //for storing the list of vulnerabilities
    setDataLoaded: (value) => set({dataLoaded: value}),
    //update Risk Data
    updateRiskData: async (field, value) => {
        if (get().processing) return;
        set({processing:true});

        set(
            produce((state) => {
                let index = state.riskData.findIndex((item) => item.id === field);
                state.riskData[index].value = value;
                state.riskData = get().enforceCascadingRiskLevels(state.riskData);
            })
        );
        try {
            await rsssl_api.doAction('vulnerabilities_measures_set', {
                riskData: get().riskData,
            });

            set({dataLoaded: true, processing:false});
        } catch (e) {
            console.log(e);
        }
        set({processing:false})
    },
    enforceCascadingRiskLevels: (data) => {
        if (data.length===0) return data;
        //get risk levels for force_update
        let forceUpdateRiskLevel = data.filter((item) => item.id==='force_update')[0].value;
        let quarantineRiskLevel = data.filter((item) => item.id==='quarantine')[0].value;

        //get the integer value of the risk level
        forceUpdateRiskLevel = get().riskLevels.hasOwnProperty(forceUpdateRiskLevel) ? get().riskLevels[forceUpdateRiskLevel] : 5;
        quarantineRiskLevel = get().riskLevels.hasOwnProperty(quarantineRiskLevel) ? get().riskLevels[quarantineRiskLevel] : 5;
        let quarantineIndex = data.findIndex((item) => item.id==='quarantine');
        //if the quarantine risk level is lower than the force update risk level, we set it to the force update risk level
        if (quarantineRiskLevel<forceUpdateRiskLevel) {
            data[quarantineIndex].value = Object.keys(get().riskLevels).find(key => get().riskLevels[key] === forceUpdateRiskLevel);
        }
        //if the force update risk level is none, set quarantine also to none.
        if ( forceUpdateRiskLevel===5 ) {
            data[quarantineIndex].value = '*';
        }

        //disable all values below this value
        let disableUpTo = forceUpdateRiskLevel>0 ? forceUpdateRiskLevel : 0
        //create an array of integers up to the forceUpdateRiskLevel
        let disabledRiskLevels =  Array.from(Array(disableUpTo).keys()).map(x => x);
        disabledRiskLevels = disabledRiskLevels.map( (level) => {
            return Object.keys(get().riskLevels).find(key => get().riskLevels[key] === level  );
        });
        data[quarantineIndex].disabledRiskLevels = disabledRiskLevels;
        return data;
    },
    fetchFirstRun: async () => {
        if (get().processing) return;
        set({processing:true});
        await rsssl_api.doAction('vulnerabilities_scan_files');
        set({processing:false});
    },

    /*
    * Functions
     */
    fetchVulnerabilities: async () => {
        if (get().processing) return;
        set({processing:true});
        let data = {};
        try {
            const fetched = await rsssl_api.doAction('hardening_data', data);
            let vulList = [];
            let vulnerabilities = 0;
            if (fetched.data.vulList) {
                vulnerabilities = fetched.data.vulnerabilities;
                vulList = fetched.data.vulList;
                if (typeof vulList === 'object') {
                    //we make it an array
                    vulList = Object.values(vulList);
                }
                vulList.forEach(function (item, i) {
                    let updateUrl = item.update_available ? rsssl_settings.plugins_url + "?plugin_status=upgrade" : '#settings/vulnerabilities';
                    item.vulnerability_action = <div className="rsssl-action-buttons">
                        <a className="rsssl-button button-secondary"
                            href={"https://really-simple-ssl.com/vulnerability/" + item.rss_identifier}
                           target={"_blank"}  rel="noopener noreferrer">{__("Details", "really-simple-ssl")}</a>
                        <a disabled={!item.update_available} href={updateUrl}
                           className="rsssl-button button-primary"
                        >{__("Update", "really-simple-ssl")}</a>
                    </div>
                });
            }
            let riskData = fetched.data.riskData;
            if (!Array.isArray(riskData)) {riskData = []}
            riskData = get().enforceCascadingRiskLevels(riskData);
            set(
                produce((state) => {
                    state.vulnerabilities = vulnerabilities;
                    state.vulList = vulList;
                    state.updates = fetched.data.updates;
                    state.dataLoaded = true;
                    state.riskNaming = fetched.data.riskNaming;
                    state.lastChecked = fetched.data.lastChecked;
                    state.vulEnabled = fetched.data.vulEnabled;
                    state.riskData = riskData;
                    state.processing = false;
                })
            )
        } catch (e) {
            console.error(e);
        }
    },

    vulnerabilityCount: () => {
        let vuls = get().vulList;
        //we group the data by risk level
        //first we make vuls an array
        let vulsArray = [];
        Object.keys(vuls).forEach(function (key) {
            vulsArray.push(vuls[key]);
        });
        let riskLevels = ['c', 'h', 'm', 'l'];
        //we count the amount of vulnerabilities per risk level
        return riskLevels.map(function (level) {
            return {
                level: level,
                count: vulsArray.filter(function (vul) {
                    return vul.risk_level === level;
                }).length
            };
        });
    },

    vulnerabilityScore: () => {
        let score = 0;
        let vulnerabilitiesList = get().vulList;

        Object.keys(vulnerabilitiesList).forEach(function (key) {
            //if there are vulnerabilities with critical severity, score is 5
            if (vulnerabilitiesList[key].risk_level === 'c') {
                score = 5;
            } else if (score < 1) {
                score = 1;
            }
        });
        return score;
    },

    hardeningScore: () => {
        let score = 0;
        let vulnerabilitiesList = get().vulnerabilities;
        for (let i = 0; i < vulnerabilitiesList.length; i++) {
            score += vulnerabilitiesList[i].hardening_score;
        }
        return score;
    },

    activateVulnerabilityScanner: async () => {
        try {
            const fetched = await rsssl_api.doAction('rsssl_scan_files');
            if (fetched.request_success) {
                //we get the data again
                await get().fetchVulnerabilities();
            }

        } catch (e) {
            console.error(e);
        }
    }
}));

export default UseRiskData;
