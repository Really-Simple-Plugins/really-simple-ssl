import {useState, useEffect, useRef} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";

const OtherPlugins = (props) => {
    const [dataLoaded, setDataLoaded] = useState(false);
    const [dataUpdated, setDataUpdated] = useState(false);
    const [pluginData, setPluginData] = useState(false);

    useEffect(()=>{
        if ( !dataLoaded ) {
               rsssl_api.runTest('otherpluginsdata').then( ( response ) => {
                response.data.forEach(function(pluginItem, i) {
                    response.data[i].pluginActionNice = pluginActionNice(pluginItem.pluginAction);

                });

                setPluginData(response.data);
                setDataLoaded(true);
            })
        }
    })

    const PluginActions = (slug, pluginAction, e) => {
        e.preventDefault();
        let data = {};
        data.slug = slug;
        data.pluginAction = pluginAction;

        let pluginItem = getPluginData(slug);
        pluginItem.pluginAction = pluginAction==='download' ? "downloading" : 'activating';
        pluginItem.pluginActionNice = pluginActionNice(pluginItem.pluginAction);
        updatePluginData(slug, pluginItem);

        rsssl_api.doAction('plugin_actions', data).then( ( response ) => {
            pluginItem = response.data;
            if ( pluginAction==='download' ) {
                pluginItem.pluginAction = 'activating';
                updatePluginData(slug, pluginItem);
                data.pluginAction = 'activate';
                rsssl_api.doAction('plugin_actions', data).then( ( response ) => {
                    pluginItem = response.data;
                    pluginItem.pluginAction = 'installed';
                    updatePluginData(slug, pluginItem);
                })
            } else {
                pluginItem.pluginAction = 'installed';
                updatePluginData(slug, pluginItem);
            }
        })
    }

    const getPluginData = (slug) => {
        return pluginData.filter((pluginItem) => {
            return (pluginItem.slug===slug)
        })[0];

    }

    const updatePluginData = (slug, newPluginItem) => {
        setDataUpdated(false);
        pluginData.forEach(function(pluginItem, i) {
            if (pluginItem.slug===slug) {
                pluginData[i] = newPluginItem;
            }
        });

        setPluginData(pluginData);
        setDataUpdated(true);
    }

    const pluginActionNice = (pluginAction) => {
        const statuses = {
            'download': __("Install", "really-simple-ssl"),
            'activate': __("Activate", "really-simple-ssl"),
            'activating': __("activating...", "really-simple-ssl"),
            'downloading': __("downloading...", "really-simple-ssl"),
        };
        return statuses[pluginAction];
    }

    const otherPluginElement = (plugin) => {

        return (
           <div className={"rsssl-other-plugins-element rsssl-"+plugin.slug}>
               <a href={plugin.wordpress_url} target="_blank" title={plugin.title}>
                   <div className="rsssl-bullet"></div>
                   <div className="rsssl-other-plugins-content">{plugin.title}</div>
               </a>
               <div className="rsssl-other-plugin-status">
                {plugin.pluginAction==='upgrade-to-premium' && <><a target="_blank" href={plugin.upgrade_url}>{__("Upgrade", "really-simple-ssl")}</a></>}
                {plugin.pluginAction!=='upgrade-to-premium' && plugin.pluginAction!=='installed' && <>
                    <a href="#" onClick={ (e) => PluginActions(plugin.slug, plugin.pluginAction, e) } >{plugin.pluginActionNice}</a></>}
                {plugin.pluginAction==='installed' && <>{__("Installed", "really-simple-ssl")}</>}
               </div>
           </div>
        )
    }

    if ( !dataLoaded ) {
        return (<></>)
    }
    return (
        <>
           <div className="rsssl-other-plugins-container">
               { pluginData.map((plugin, i) => otherPluginElement(plugin, i)) }
           </div>
        </>
    )
}

export default OtherPlugins;
