import Icon from "../utils/Icon";
import {__} from "@wordpress/i18n";
import {Button} from "@wordpress/components";
import useOnboardingData from "./OnboardingData";
import {memo} from "@wordpress/element";

const ListItem = ({item}) => {
    const {
        actionHandler,
        networkwide,
        networkProgress,
        networkActivationStatus,
    } = useOnboardingData();
    let { title, description, current_action, action, status, button, id } = item;
    const currentActions = {
        'activate_setting': __('Activating...',"really-simple-ssl"),
        'activate': __('Activating...',"really-simple-ssl"),
        'install_plugin': __('Installing...',"really-simple-ssl"),
        'error': __('Failed',"really-simple-ssl"),
        'completed': __('Finished',"really-simple-ssl"),
    };
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

    if ( id==='ssl_enabled' && networkwide ) {
        if ( networkProgress>=100) {
            status = 'success';
            title = __( "SSL has been activated network wide", "really-simple-ssl" );
        } else {
            status = 'processing';
            title = __( "Processing activation of subsites networkwide", "really-simple-ssl" );
        }
    }
    const statusIcon = item.status!=='success' && item.is_plugin && item.current_action === 'none' ? 'empty' : statuses[status].icon;
    const statusColor = statuses[status].color;

    let buttonTitle = '';
    if ( button ) {
        buttonTitle = button;
        if ( current_action!=='none' ) {
            buttonTitle = currentActions[current_action];
            if ( current_action==='failed' ) {
                buttonTitle = currentActions['error'];
            }
        }
    }
    let showLink = (button && button===buttonTitle);
    let showAsPlugin = item.status!=='success' && item.is_plugin && item.current_action === 'none';
    let isPluginClass = showAsPlugin ? 'rsssl-is-plugin' : '';
    title = showAsPlugin ? <b>{title}</b> : title;
    return (
        <li className={isPluginClass}>
            <Icon name = {statusIcon} color = {statusColor} />
            {title}{description && <>&nbsp;-&nbsp;{description}</>}
            {id==='ssl_enabled' && networkwide && networkActivationStatus==='main_site_activated' && <>
                &nbsp;-&nbsp;
                {networkProgress<100 && <>{__("working", "really-simple-ssl")}&nbsp;{networkProgress}%</>}
                {networkProgress>=100 && __("completed", "really-simple-ssl") }
            </>}
            {button && <>&nbsp;-&nbsp;
                {showLink && <Button isLink={true} onClick={(e) => actionHandler(id, action, e)}>{buttonTitle}</Button>}
                {!showLink && <>{buttonTitle}</>}
            </>}
        </li>
    )
}
export default memo(ListItem)