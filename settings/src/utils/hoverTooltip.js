// src/utils/hoverTooltip.js
import {useEffect} from '@wordpress/element';

const tooltipStyles = {
    display: 'none',
    position: 'absolute',
    backgroundColor: 'rgba(0,0,0,0.8)',
    color: 'white',
    padding: '10px',
    borderRadius: '3px',
    fontSize: '13px',
    left: '0px',
    zIndex: 1000,
};

const allowedClasses = ['enable_firewall', '404_blocking_threshold', '404_blocking_lockout_duration'];

const hasAllowedClass = (element) => {
    if (!element) return false;

    // Check if the element itself has the allowed class
    const elementHasClass = allowedClasses.some((cls) => {
        const hasClass = element.classList.contains(cls);
        return hasClass;
    });

    if (elementHasClass) return true;

    // Check child elements for the allowed class
    const children = Array.from(element.querySelectorAll('*'));

    return children.some((child) =>
        allowedClasses.some((cls) => {
            const hasClass = child.classList.contains(cls);
            return hasClass;
        })
    );
};

const hoverTooltip = (ref, condition, tooltipText) => {
    useEffect(() => {
        const element = ref.current;
        if (!element) return;

        if (!hasAllowedClass(element)) return;

        let tooltip = document.getElementById('rsssl-hover-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'rsssl-hover-tooltip';
            Object.assign(tooltip.style, tooltipStyles);
        }

        tooltip.innerHTML = tooltipText;

        const parent = element.parentElement;
        if (parent.style.position !== 'relative') {
            parent.style.position = 'relative';
        }

        if (!parent.contains(tooltip)) {
            parent.appendChild(tooltip);
        }

        const showTooltip = () => {
            // Show tooltip when condition (originalDisabled) is true
            // This means show it when the field is disabled by PHP
            if (condition) {
                tooltip.style.display = 'block';
            }
        };

        const hideTooltip = () => {
            tooltip.style.display = 'none';
        };

        // Use the parent element as the hover target since the select
        // itself might not trigger hover events when disabled
        const target = element.parentElement;
        target.addEventListener('mouseover', showTooltip);
        target.addEventListener('mouseout', hideTooltip);

        return () => {
            target.removeEventListener('mouseover', showTooltip);
            target.removeEventListener('mouseout', hideTooltip);
            if (parent && parent.contains(tooltip) && !document.body.contains(element)) {
                parent.style.position = '';
                tooltip.remove();
            }
        };
    }, [ref.current, condition]); // Only re-run if ref or condition changes
};

export default hoverTooltip;