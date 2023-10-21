import React, { Component } from 'react';
import PropTypes from 'prop-types';

class ErrorBoundary extends Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null, errorInfo: null };
        this.resetError = this.resetError.bind(this);
    }

    static getDerivedStateFromError(error) {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        this.setState({ error, errorInfo });
        // You can also log the error to an error reporting service
        console.log('ErrorBoundary', error, errorInfo);
    }

    resetError() {
        this.setState({ hasError: false, error: null, errorInfo: null });
    }

    render() {
        if (this.state.hasError) {
            return (
                <div>
                    <h1>Something went wrong.</h1>

                    {/* You can render any custom fallback UI */}
                    <p>{this.props.fallback}</p>
                    <button onClick={this.resetError}>Try Again</button>
                </div>
            );
        }

        return this.props.children;
    }
}

ErrorBoundary.propTypes = {
    children: PropTypes.node,
    fallback: PropTypes.node,
};

export default ErrorBoundary;