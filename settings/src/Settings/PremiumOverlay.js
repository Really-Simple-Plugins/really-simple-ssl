import {__} from "@wordpress/i18n";
import Hyperlink from "../utils/Hyperlink";
import {Button} from "@wordpress/components";

const PremiumOverlay = (props) => {
    return (
        <div className="rsssl-locked">
            <div className="rsssl-locked-overlay premium">
                {/* header */}
                <div className="rsssl-locked-header">
                    <h5 className={'rsssl-locked-header-title'}>{props.title}</h5>
                </div>
                <div className="rsssl-locked-content">
                    {props.pro_plugin_active && <span>{props.msg}&nbsp;<a className="rsssl-locked-link"
                                                                             href="#settings/license">{__("Check license", "really-simple-ssl")}</a></span>}
                    {!props.pro_plugin_active && <Hyperlink target="_blank" text={props.msg} url={props.upgrade}/>}
                </div>
                <div className="rsssl-locked-footer">
                    {/* We place a button on the left side */}
                    <div className="rsssl-grid-item-footer-buttons">
                        {!props.url &&
                            <a
                                target="_blank"
                                className="button button-primary left"
                                href="https://really-simple-ssl.com/pro/"
                            >{__("Go Pro", "really-simple-ssl")}</a>
                        }
                        {
                            props.url &&
                            <a
                                target="_blank"
                                className="button button-primary left"
                                href={props.url}
                            >{__("Go Pro", "really-simple-ssl")}</a>
                        }

                    </div>
                </div>
            </div>
        </div>
    );
}

export default PremiumOverlay;