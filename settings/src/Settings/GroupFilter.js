// GroupFilter.js
import { useState, useRef, useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

const GroupFilter = ({ groupFilter, filterId, selectedFilter, setSelectedFilter }) => {
    if (!groupFilter) {
        return null;
    }
    return (
        <div className="rsssl-grid-item-controls">
            <select
                className="rsssl-group-filter"
                id={filterId}
                name={filterId}
                value={selectedFilter[filterId]}
                onChange={(e) => {
                    const selectedValue = e.target.value;
                    setSelectedFilter(selectedValue, filterId);
                }}
            >
                {groupFilter.options.map((option) => (
                    <option key={`option-${option.id}`} value={option.id}>
                        {option.title}
                    </option>
                ))}
            </select>
        </div>
    );
};

export default GroupFilter;