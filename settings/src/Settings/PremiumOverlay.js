import {__} from "@wordpress/i18n";
import Hyperlink from "../utils/Hyperlink";
import {Button} from "@wordpress/components";

const PremiumOverlay = (props) => {
    return (
        <div className="rsssl-locked">
            <div className="rsssl-locked-overlay">
                {/* header */}
                <div className="rsssl-locked-header">
                    <h5 className={'rsssl-locked-header-title'}>{props.title}</h5>
                </div>
                <div className="rsssl-locked-content">
                    {props.msg}
                </div>
                <div className="rsssl-locked-footer">
                    {/* We place a button on the left side */}
                    <div className="rsssl-grid-item-footer-buttons">
                        <a
                            target="_blank"
                            className="button button-primary left"
                            url="props.url"
                        >{__("Go Pro", "really-simple-ssl")}</a>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default PremiumOverlay;