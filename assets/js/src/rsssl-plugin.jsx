import React from 'react';
import ReactDOM from 'react-dom';
import RssslModal from './components/ModelComponent.jsx';
console.log("RssslModal component should be rendered now.");
document.addEventListener('DOMContentLoaded', function() {
    ReactDOM.render(<RssslModal />, document.getElementById('rsssl-modal-root'));
    console.log("RssslModal component should be rendered now.");
});