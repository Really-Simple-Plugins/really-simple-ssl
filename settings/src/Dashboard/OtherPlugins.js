import {useState, useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import Placeholder from '../Placeholder/Placeholder';

const OtherPlugins = (props) => {
    const [dataLoaded, setDataLoaded] = useState(false);
    const [error, setError] = useState(false);
    const [dataUpdated, setDataUpdated] = useState('');
    const [pluginData, setPluginData] = useState(false);

    useEffect(() => {
        if (!dataLoaded) {
            rsssl_api.runTest('otherpluginsdata').then((response) => {
                let responseData = [];
                if (response.error) {
                    setError(response.error);
                } else {
                    responseData = response.plugins;
                    responseData.forEach(function (pluginItem, i) {
                        responseData[i].pluginActionNice = pluginActionNice(pluginItem.pluginAction);
                    });
                }
                setPluginData(responseData);
                setDataLoaded(true);
            })
        }
    }, [] )

    const PluginActions = (slug, pluginAction, e) => {
        if (e) e.preventDefault();
        let data = {};
        data.slug = slug;
        data.pluginAction = pluginAction;
        let pluginItem = getPluginData(slug);
        if ( pluginAction==='download' ) {
            pluginItem.pluginAction = "downloading";
        } else if (pluginAction==='activate') {
            pluginItem.pluginAction = "activating";
        }
        pluginItem.pluginActionNice = pluginActionNice(pluginItem.pluginAction);
        updatePluginData(slug, pluginItem);
        if (pluginAction==='installed' || pluginAction === 'upgrade-to-premium') {
            return;
        }
        rsssl_api.doAction('plugin_actions', data).then( ( response ) => {
            pluginItem = response;
            updatePluginData(slug, pluginItem);
            PluginActions(slug, pluginItem.pluginAction);
        })
    }

    const getPluginData = (slug) => {
        return pluginData.filter((pluginItem) => {
            return (pluginItem.slug===slug)
        })[0];
    }

    const updatePluginData = (slug, newPluginItem) => {

        pluginData.forEach(function(pluginItem, i) {
            if (pluginItem.slug===slug) {
                pluginData[i] = newPluginItem;
            }
        });
        setPluginData(pluginData);
        setDataUpdated(slug+newPluginItem.pluginAction);

    }

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

    const otherPluginElement = (plugin, i) => {
        return (
           <div key={i} className={"rsssl-other-plugins-element rsssl-"+plugin.slug}>
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

    if ( !dataLoaded || error) {
        return (<Placeholder lines="3" error={error}></Placeholder>)
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
