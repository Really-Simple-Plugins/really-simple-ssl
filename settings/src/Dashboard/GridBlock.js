import { __ } from '@wordpress/i18n';
import ProgressBlock from "./Progress/ProgressBlock";
import ProgressHeader from "./Progress/ProgressBlockHeader";
import ProgressFooter from "./Progress/ProgressFooter";
import SslLabs from "./SslLabs/SslLabs";
import SslLabsFooter from "./SslLabs/SslLabsFooter";
import WPVul from "./Vulnerabilities/WPVul";
import WPVulFooter from "./Vulnerabilities/WPVulFooter";
import OtherPlugins from "./OtherPlugins/OtherPlugins";
import TipsTricks from "./TipsTricks/TipsTricks";
import TipsTricksFooter from "./TipsTricks/TipsTricksFooter";
import {useState} from "@wordpress/element";

/*
 * Mapping of components, for use in the config array
 */
var dynamicComponents = {
    "ProgressBlock": ProgressBlock,
    "ProgressHeader": ProgressHeader,
    "ProgressFooter": ProgressFooter,
    "TipsTricks": TipsTricks,
    "TipsTricksFooter": TipsTricksFooter,
    "SslLabs": SslLabs,
    "SslLabsFooter": SslLabsFooter,
    "OtherPlugins": OtherPlugins,
    "WPVul": WPVul,
    "WPVulFooter": WPVulFooter,
};

const GridBlock = (props) => {
    const content = props.block.content.data;
    const footer =props.block.footer ? props.block.footer.data : false;
    const blockData = props.block;
    const controls = props.block.controls ? props.block.controls : false;
    let className = "rsssl-grid-item "+blockData.class+" rsssl-"+blockData.id;
    return (
        <div className={className}>
            <div className="rsssl-grid-item-header">
                <h3 className="rsssl-grid-title rsssl-h4">{ blockData.title }</h3>
                <div className="rsssl-grid-item-controls">
                    {controls.type==='url' && <a href={controls.data}>{__("Instructions", "really-simple-ssl")}</a>}
                    {controls.type==='html' && <span className="rsssl-header-html" dangerouslySetInnerHTML={{__html: controls.data}}></span>}
                    {controls.type==='react' && wp.element.createElement(dynamicComponents[controls.data])}
                </div>
            </div>
            { <div className="rsssl-grid-item-content">{wp.element.createElement(dynamicComponents[content])}</div>}

            { !footer && <div className="rsssl-grid-item-footer"></div>}
            { footer && <div className="rsssl-grid-item-footer">{wp.element.createElement(dynamicComponents[footer])}</div>}
        </div>
    );
}

export default GridBlock;