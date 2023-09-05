const prepareErrorText = (e) => {
    let errorsAsText = '';
    let allErrors = e.response.data.errors;
    if (allErrors !== null) {
        for (let error in allErrors) {
            allErrors[error].forEach(function (errorDescription) {
                errorsAsText += errorDescription;
            })
        }
    }
    return `${e.response.data.message} ${errorsAsText}`;
}
const checkIfModelHasEmptyProperties = (model) => {
    for (const modelKey in model) {
        if ((model[modelKey] === '' || model[modelKey] === undefined || model[modelKey] === null) && model.dataStructure[modelKey] === 'required') {
            return true;
        }
    }
    return false;
}
const clearModelProperties = (model, setNull = false) => {
    for (const modelKey in model) {
        model[modelKey] = setNull === true ? null : '';
    }
}

const showSnackbar = (snackbar, text, type = 'success', timeout = 3000) => {

    snackbar.type = type;
    snackbar.timeout = timeout;
    snackbar.status = true;
    snackbar.text = text;
}

const toObjectRequest = (model, includeNulls = false) => {
    const skipKeys = ['dataStructure'];
    let object = {};
    for (const modelKey in model) {
        if (skipKeys.includes(modelKey)) continue;

        if (model[modelKey] !== null || includeNulls === true) {

            object[camelToUnderscore(modelKey)] = model[modelKey];
        }
    }
    return object;
}
const getCSRFToken = () => {
    let cookies = getPageCookies();
    return cookies['XSRF-TOKEN'];
}
const getPageCookies = () => {

    // cookie is a string containing a semicolon-separated list, this split puts it into an array
    let cookieArr = document.cookie.split(";");

    // This object will hold all of the key value pairs
    let cookieObj = {};

    // Iterate the array of flat cookies to get their key value pair
    for (let i = 0; i < cookieArr.length; i++) {

        // Remove the standardized whitespace
        let cookieSeg = cookieArr[i].trim();

        // Index of the split between key and value
        let firstEq = cookieSeg.indexOf("=");

        // Assignments
        let name = cookieSeg.substr(0, firstEq);
        cookieObj[name] = cookieSeg.substr(firstEq + 1);
    }
    return cookieObj;
}

const camelToUnderscore = (key) => {
    let result = key.replace(/([A-Z])/g, " $1");
    return result.split(' ').join('_').toLowerCase();
}

export {
    prepareErrorText,
    checkIfModelHasEmptyProperties,
    clearModelProperties,
    showSnackbar,
    toObjectRequest,
    getCSRFToken
}
