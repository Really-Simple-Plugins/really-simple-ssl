import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
const useOtherPlugins = create(( set, get ) => ({
    error:false,
    dataLoaded:false,
    pluginData:[],
    updatePluginData:(slug, newPluginItem) => {
        let pluginData = get().pluginData;
        pluginData.forEach(function(pluginItem, i) {
            if (pluginItem.slug===slug) {
                pluginData[i] = newPluginItem;
            }
        });
        set(state => ({
            dataLoaded:true,
            pluginData:pluginData,
        }))
    },
    getPluginData: (slug) => {
        let pluginData = get().pluginData;
        return pluginData.filter((pluginItem) => {
            return (pluginItem.slug===slug)
        })[0];
    },
    fetchOtherPluginsData: async () => {
        const {pluginData, error} = await rsssl_api.doAction('otherpluginsdata').then((response) => {
            let pluginData = [];
            pluginData = response.plugins;
            let error = response.error;
            if (!error) {
                pluginData.forEach(function (pluginItem, i) {
                    pluginData[i].pluginActionNice = pluginActionNice(pluginItem.pluginAction);
                });
            }

            return {pluginData, error};
        })
        set(state => ({
            dataLoaded:true,
            pluginData:pluginData,
            error:error,
        }))
    },
    pluginActions: (slug, pluginAction, e) => {
        if (e) e.preventDefault();
        let data = {};
        data.slug = slug;
        data.pluginAction = pluginAction;
        let pluginItem = get().getPluginData(slug);
        if ( pluginAction==='download' ) {
            pluginItem.pluginAction = "downloading";
        } else if (pluginAction==='activate') {
            pluginItem.pluginAction = "activating";
        }
        pluginItem.pluginActionNice = pluginActionNice(pluginItem.pluginAction);
        get().updatePluginData(slug, pluginItem);
        if (pluginAction==='installed' || pluginAction === 'upgrade-to-premium') {
            return;
        }
        rsssl_api.doAction('plugin_actions', data).then( ( response ) => {
            pluginItem = response;
            get().updatePluginData(slug, pluginItem);
            get().pluginActions(slug, pluginItem.pluginAction);
        })
    },
}));

export default useOtherPlugins;

const pluginActionNice = (pluginAction) => {
    const statuses = {
        'download': __("Install", "really-simple-ssl"),
        'activate': __("Activate", "really-simple-ssl"),
        'activating': __("Activating...", "really-simple-ssl"),
        'downloading': __("Downloading...", "really-simple-ssl"),
        'upgrade-to-premium': __("Downloading...", "really-simple-ssl"),
    };
    return statuses[pluginAction];
}

