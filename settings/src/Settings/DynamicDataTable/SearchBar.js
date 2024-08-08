import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const SearchBar = ({ handleSearch, searchableColumns }) => {
    const [debounceTimer, setDebounceTimer] = useState(null);

    const onKeyUp = (event) => {
        clearTimeout(debounceTimer);
        setDebounceTimer(setTimeout(() => {
            handleSearch(event.target.value, searchableColumns)
        }, 500));
    };

    return (
        <div className="rsssl-search-bar">
            <div className="rsssl-search-bar__inner">
                <div className="rsssl-search-bar__icon"></div>
                <input
                    type="text"
                    className="rsssl-search-bar__input"
                    placeholder={__("Search", "really-simple-ssl")}
                    onKeyUp={onKeyUp}
                />
            </div>
        </div>
    )
}

export default SearchBar;