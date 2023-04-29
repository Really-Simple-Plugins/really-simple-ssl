import ProgressBlock from "./Progress/ProgressBlock";
import ProgressHeader from "./Progress/ProgressBlockHeader";
import ProgressFooter from "./Progress/ProgressFooter";
import SslLabs from "./SslLabs/SslLabs";
import SslLabsFooter from "./SslLabs/SslLabsFooter";
import SslLabsHeader from "./SslLabs/SslLabsHeader";
import WPVul from "./Vulnerabilities/WPVul";
import WPVulFooter from "./Vulnerabilities/WPVulFooter";
import VulnerabilitiesHeader from "./Vulnerabilities/VulnerabilitiesHeader";
import OtherPlugins from "./OtherPlugins/OtherPlugins";
import TipsTricks from "./TipsTricks/TipsTricks";
import TipsTricksFooter from "./TipsTricks/TipsTricksFooter";
import OtherPluginsHeader from "./OtherPlugins/OtherPluginsHeader";

/*
 * Mapping of components, for use in the config array
 */
const dynamicComponents = {
    "ProgressBlock": ProgressBlock,
    "ProgressHeader": ProgressHeader,
    "ProgressFooter": ProgressFooter,
    "TipsTricks": TipsTricks,
    "TipsTricksFooter": TipsTricksFooter,
    "SslLabs": SslLabs,
    "SslLabsFooter": SslLabsFooter,
    "SslLabsHeader": SslLabsHeader,
    "OtherPluginsHeader": OtherPluginsHeader,
    "OtherPlugins": OtherPlugins,
    "VulnerabilitiesHeader": VulnerabilitiesHeader,
    "WPVul": WPVul,
    "WPVulFooter": WPVulFooter,
};


const GridBlock = (props) => {
    const content = props.block.content;
    const footer =props.block.footer ? props.block.footer : false;
    const blockData = props.block;
    let className = "rsssl-grid-item "+blockData.class+" rsssl-"+blockData.id;
    return (
        <div key={"block-"+blockData.id} className={className}>
            <div key={"header-"+blockData.id} className="rsssl-grid-item-header">
                { blockData.header &&
                    wp.element.createElement(dynamicComponents[blockData.header])
                }
                {!blockData.header && <>
                        <h3 className="rsssl-grid-title rsssl-h4">{ blockData.title }</h3>
                        <div className="rsssl-grid-item-controls"></div>
                    </>
                }

            </div>
            <div key={"content-"+blockData.id} className="rsssl-grid-item-content">{wp.element.createElement(dynamicComponents[content])}</div>

            { !footer && <div className="rsssl-grid-item-footer"></div>}
            { footer && <div className="rsssl-grid-item-footer">{wp.element.createElement(dynamicComponents[footer])}</div>}
        </div>
    );
}

export default GridBlock;