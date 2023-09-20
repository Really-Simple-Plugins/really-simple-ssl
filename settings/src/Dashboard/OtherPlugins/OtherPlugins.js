import { useEffect} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import Placeholder from '../../Placeholder/Placeholder';
import useOtherPlugins  from "./OtherPluginsData";
const OtherPlugins = () => {
    const {dataLoaded, pluginData, pluginActions, fetchOtherPluginsData, error} = useOtherPlugins();
    useEffect(() => {
        if (!dataLoaded) {
            fetchOtherPluginsData();
        }
    }, [] )

    const otherPluginElement = (plugin, i) => {
        return (
           <div key={"plugin"+i} className={"rsssl-other-plugins-element rsssl-"+plugin.slug}>
               <a href={plugin.wordpress_url} target="_blank" title={plugin.title}>
                   <div className="rsssl-bullet"></div>
                   <div className="rsssl-other-plugins-content">{plugin.title}</div>
               </a>
               <div className="rsssl-other-plugin-status">
                {plugin.pluginAction==='upgrade-to-premium' && <><a target="_blank" href={plugin.upgrade_url}>{__("Upgrade", "really-simple-ssl")}</a></>}
                {plugin.pluginAction!=='upgrade-to-premium' && plugin.pluginAction!=='installed' && <>
                    <a href="#" onClick={ (e) => pluginActions(plugin.slug, plugin.pluginAction, e) } >{plugin.pluginActionNice}</a></>}
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
