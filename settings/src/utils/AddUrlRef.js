export const addUrlRef = (url) => {
    const ref = rsssl_settings.ref;
    if ( parseInt(ref) > 0 ) {
        const [baseUrl, hash] = url.split('#');
        const separator = baseUrl.includes('?') ? '&' : '?';
        url = `${baseUrl}${separator}ref=${ref}${hash ? `#${hash}` : ''}`;
    }
    return url;
}