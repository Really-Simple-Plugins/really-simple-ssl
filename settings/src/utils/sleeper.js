/*
 * helper function to delay after a promise
 * @param ms
 * @returns {function(*): Promise<unknown>}
 */
const sleeper = (ms) => {
    return function(x) {
        return new Promise(resolve => setTimeout(() => resolve(x), ms));
    };
}
export default sleeper;