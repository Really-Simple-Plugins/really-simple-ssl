/**
 * Notice after saving was successfull
 */
import { SnackbarList } from '@wordpress/components';
import {
    useDispatch,
    useSelect,
} from '@wordpress/data';

import { store as noticesStore } from '@wordpress/notices';

const Notices = () => {
    const notices = useSelect(
        ( select ) =>
            select( noticesStore )
                .getNotices()
                .filter( ( notice ) => notice.type === 'snackbar' ),
        []
    );
    if ( typeof notices === 'undefined' ) {
        return (<></>)
    }
    const { removeNotice } = useDispatch( noticesStore );
    return (
        <SnackbarList
            className="edit-site-notices"
            notices={ notices }
            onRemove={ removeNotice }
        />
    );
};

export default Notices;