import Icon from "../utils/Icon";
import {memo} from "@wordpress/element";

const ListItem = ({item}) => {
    let { title, description,status } = item;

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

    const statusIcon = item.status!=='success' && item.is_plugin && item.current_action === 'none' ? 'empty' : statuses[status].icon;
    const statusColor = statuses[status].color;
    let showAsPlugin = item.status!=='success' && item.is_plugin && item.current_action === 'none';
    let isPluginClass = showAsPlugin ? 'rsssl-is-plugin' : '';
    title = showAsPlugin ? <b>{title}</b> : title;
    return (
        <li className={isPluginClass}>
            <Icon name = {statusIcon} color = {statusColor} />
            {title}{description && <>&nbsp;-&nbsp;{description}</>}
        </li>
    )
}
export default memo(ListItem)