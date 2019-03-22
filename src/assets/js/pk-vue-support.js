/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/** Reusable Generic functions to help Vue/Axios
 * Attached to window - global functions
*/

//Try a default axios ajax catch 
//use: .catch(defaxerr);
function defaxerr(error) {
  if (error && error.response && error.response.data && error.response.data.systemmsg === "error") {
    errorDlg(error.response.data.msg,error.response.data.title);
  }
  console.error("Ajax Error:",error,error.response);
}
