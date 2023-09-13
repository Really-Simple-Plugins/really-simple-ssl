/** @jsx wp.element.createElement */
import './eventlistener.js';
import RssslModal from './components/RssslModal.jsx';
document.addEventListener('DOMContentLoaded', function() {
    const root = wp.element.createRoot(document.getElementById('rsssl-modal-root'));
    root.render(<RssslModal />);
});