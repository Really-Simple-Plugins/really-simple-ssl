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
 * @type {{SslLabs: JSX.Element}}
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

    const [footerHtml, setFooterHtml] = useState(props.block.footer.data);
    const [content, setContent] = useState(props.block.content.data);
    const [footer, setFooter] = useState(props.block.footer.data);
    const [blockProps, setBlockProps] = useState([]);
    const [blockData, setBlockData] = useState(props.block);

    /*
     * Allow child blocks to set data on the gridblock
     * @param key
     * @param value
     */
    const updateBlockProps = (key, value) => {
        if (!blockProps.hasOwnProperty(key) || blockProps[key]!==value) {
            blockProps[key] = value;
            setBlockProps(blockProps);
        }
    }

    let className = "rsssl-grid-item "+blockData.class+" rsssl-"+blockData.id;
    let DynamicBlockProps = { updateBlockProps: updateBlockProps, blockProps: blockProps, runTest: props.runTest };
    return (
        <div className={className}>
            <div className="rsssl-grid-item-header">
                <h3 className="rsssl-grid-title rsssl-h4">{ blockData.title }</h3>
                <div className="rsssl-grid-item-controls">
                    {blockData.controls && blockData.controls.type==='url' && <a href={blockData.controls.data}>{__("Instructions", "really-simple-ssl")}</a>}
                    {blockData.controls && blockData.controls.type==='html' && <span className="rsssl-header-html" dangerouslySetInnerHTML={{__html: blockData.controls.data}}></span>}
                    {blockData.controls && blockData.controls.type==='react' && wp.element.createElement(dynamicComponents[blockData.controls.data], DynamicBlockProps)}
                </div>
            </div>
            { blockData.content.type!=='react' && <div className="rsssl-grid-item-content" dangerouslySetInnerHTML={{__html: content}}></div>}
            { blockData.content.type==='react' && <div className="rsssl-grid-item-content">{wp.element.createElement(dynamicComponents[content], DynamicBlockProps)}</div>}

            { blockData.footer.type==='html' && <div className="rsssl-grid-item-footer" dangerouslySetInnerHTML={{__html: footerHtml}}></div>}
            { blockData.footer.type==='react' && <div className="rsssl-grid-item-footer">{wp.element.createElement(dynamicComponents[footer], DynamicBlockProps)}</div>}
        </div>
    );
}

export default GridBlock;