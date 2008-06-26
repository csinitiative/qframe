var Cookies = {
  
  /**
   * Returns true if a cookie with a particular name exists
   *
   * @param string cookie name
   */
  exists: function(name) {
    var cookies = document.cookie.split(';');
    var regexp = new RegExp('^' + name + '=');
    for(var i = 0; i < cookies.length; i++) {
      var cookie = cookies[i].replace(/^\s+|\s+$/, '');
      if(regexp.test(cookie)) return true;
    }
    return false;
  }
}