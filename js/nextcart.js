jQuery.fn.extend({
  nextCart: function(storeURL) {
    if(!storeURL.match(/\/$/)) {
        storeURL += '/';
    }
	var total = '';
    var vis = 'visibility:hidden;';
    var login = '<a class="cart-login-link" href="'+storeURL+'account.aspx">Login</a>';

	if(typeof cartProperties !== 'undefined' && cartProperties.QuantityTotal != 0) {
		total = cartProperties.QuantityTotal;
        vis = '';
	}
    if(typeof customerProperties !== 'undefined' && customerProperties.FirstName && customerProperties.LastName) {
        login = '<span class="cart-welcome-msg">Welcome, '+customerProperties.FirstName+' '+customerProperties.LastName+'<br/></span><a class="cart-account-link" href="'+storeURL+'account.aspx">My Account</a><span class="cart-link-sep"> | </span><a class="cart-logout-link" href="'+storeURL+'logout.aspx">Logout</a>'
    }
    return this.each(function() {
        jQuery(this).html('<div id="cartLogin" class="mini-cart"><div class="cart-inner"><strong class="cart-name hide-for-small">'+login+'</strong><a href="'+storeURL+'invoice.aspx" class="cart-link" id="cartDisplay"><div class="cart-icon"><strong class="cartTotalItems" style="'+vis+'">'+total+'</strong></div></a></div></div>');
    });
  }
});
