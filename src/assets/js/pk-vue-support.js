/** Reusable Generic functions to help Vue/Axios
 * Attached to window - global functions
*/

//Try a default axios ajax catch 
//use: .catch(defaxerr);
function defaxerr(error) {
  console.error("Ajax Error:",error,error.response);
}