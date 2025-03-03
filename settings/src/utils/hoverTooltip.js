// src/utils/hoverTooltip.js
import { useEffect } from '@wordpress/element';

const tooltipStyles = {
    display: 'none',
    position: 'fixed',
    backgroundColor: 'rgba(0,0,0,0.8)',
    color: 'white',
    padding: '10px',
    borderRadius: '3px',
    zIndex: 1000,
    fontSize: '13px',
};

const hoverTooltip = (ref, condition, tooltipText) => {
    useEffect(() => {
        let tooltip = document.getElementById('rsssl-hover-tooltip');

        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'rsssl-hover-tooltip';
            Object.assign(tooltip.style, tooltipStyles);
            document.body.appendChild(tooltip);
        }

        const showTooltip = () => {
            const element = ref.current;
            const target = element.disabled ? element.parentElement : element;
            const rect = target.getBoundingClientRect();
            tooltip.innerHTML = tooltipText;
            tooltip.style.display = 'block';
            tooltip.style.left = `${rect.left}px`;
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
        };

        const hideTooltip = () => {
            tooltip.style.display = 'none';
        };

        const element = ref.current;
        if (element && condition) {
            element.addEventListener('mouseover', showTooltip);
            element.addEventListener('mouseout', hideTooltip);
        }

        return () => {
            if (element && condition) {
                element.removeEventListener('mouseover', showTooltip);
                element.removeEventListener('mouseout', hideTooltip);
            }
        };
    }, [ref, condition, tooltipText]);
};

export default hoverTooltip;