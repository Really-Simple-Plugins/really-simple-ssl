/**
 Takes a relative date and an optional date parameter, and returns a human-readable string representing the difference between the two dates.
 @function
 @param {number|Date} relativeDate - The relative date (can be a number
      representing an UTC timestamp, or a Date object).
 @param {Date} [date=new Date()] - An optional date parameter to compare the
      relative date to. Defaults to the current date and time.
 @returns {string} A human-readable string representing the difference between
      the two dates (e.g., "in 3 months", "5 hours ago", etc.). Returns "-" if the input date is invalid or not yet loaded.
 @example
 const timestamp = Date.now() / 1000 + 60 * 60 * 24 * 3; // UTC timestamp 3 days from now
 console.log(getRelativeTime(timestamp)); // Output: "in 3 days"
 */
 export const getRelativeTime = (relativeDate, date = new Date()) => {
  // if relativeDate is a number, we assume it is an UTC timestamp
  if (typeof relativeDate === 'number') {
    // count charachters to check if in seconds or milliseconds
    if (relativeDate.toString().length < 13) {
      relativeDate = relativeDate * 1000;
    }
    // convert to date object
    relativeDate = new Date(relativeDate);
  }
  if (!(relativeDate instanceof Date)) {
    // invalid date, probably still loading
    return '-';
  }
  let units = {
    year  : 24 * 60 * 60 * 1000 * 365,
    month : 24 * 60 * 60 * 1000 * 365/12,
    day   : 24 * 60 * 60 * 1000,
    hour  : 60 * 60 * 1000,
    minute: 60 * 1000,
    second: 1000
  }
  let rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' })
  let elapsed = relativeDate - date
  // "Math.abs" accounts for both "past" & "future" scenarios
  for (let u in units) {
    if (Math.abs(elapsed) > units[u] || u === 'second') {
      return rtf.format(Math.round(elapsed/units[u]), u)
    }
  }
}