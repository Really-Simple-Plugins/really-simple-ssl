import Icon from "../../utils/Icon";
import {memo} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import useOnboardingData from "../OnboardingData";

const ListItem = ({item}) => {
    let { title, status, id } = item;
    const {
        overrideSSL,
        setOverrideSSL,
        certificateValid,
    } = useOnboardingData();
    const statuses = {
        'inactive': {
            'icon': 'info',
            'color': 'grey',
        },
        'warning': {
            'icon': 'circle-times',
            'color': 'orange',
        },
        'error': {
            'icon': 'circle-times',
            'color': 'red',
        },
        'success': {
            'icon': 'circle-check',
            'color': 'green',
        },
        'processing': {
            'icon': 'loading',
            'color': 'black',
        },
    };

    const statusIcon = item.status!=='success' && item.current_action === 'none' ? 'empty' : statuses[status].icon;
    const statusColor = statuses[status].color;
    return (
        <>
            <li>
                <Icon name = {statusIcon} color = {statusColor} />
                {title}
                { id==='certificate' && !certificateValid &&
                    <>&nbsp;
                        <a href="#" onClick={ (e) => refreshSSLStatus(e)}>
                        { __("Check again", "really-simple-ssl")}
                        </a>
                    </>
                }
             </li>
            { id==='certificate' && !certificateValid &&
                <li>
                    <label className="rsssl-override-detection-toggle">
                        <input
                            onChange={ (e) => setOverrideSSL(e.target.checked)}
                            type="checkbox"
                            checked={overrideSSL} />
                        {__("Override SSL detection.","really-simple-ssl")}
                    </label>
                </li>
            }
        </>

    )
}
export default memo(ListItem)