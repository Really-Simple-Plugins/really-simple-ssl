import {__} from "@wordpress/i18n";
import Hyperlink from "../utils/Hyperlink";
import {Button} from "@wordpress/components";

const PremiumOverlay = (props) => {
    return (
        <div className="rsssl-locked">
            <div className="rsssl-locked-overlay">
                {/* header */}
                <div className="rsssl-locked-header">
                    <h3>Titel</h3>
                </div>
                <div className="rsssl-locked-content">
                   text
                </div>
                <div className="rsssl-locked-footer">
                    {/* We place a button on the left side */}
                    <div className="rsssl-grid-item-footer-buttons">
                        <a
                            target="_blank"
                            className="button button-primary"
                            text={__("Upgrade", "really-simple-ssl")}
                            url="props.url"
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}

export default PremiumOverlay;