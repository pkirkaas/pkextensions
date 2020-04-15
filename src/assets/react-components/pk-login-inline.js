//import React, { Component } from '../../../../../../node-modules/react/umd/react.development.js';
//import React, { Component } from '../../../../QuantumProfilesReact/node-modules/react/umd/react.development.js';
import React, { Component } from 'react';
//import ReactDOM from '../../../../../../node-modules/react-dom/react-dom';
import ReactDOM from 'react-dom';

//export default 
class Example extends Component {
  render() {
    return (
      <div className="container">
        <h2>The login component</h2>
      </div>
    );
  }
}

if (document.getElementById('pk-login-inline')) {
    ReactDOM.render(<pk-login-inline />, document.getElementById('pk-login-inline'));
}