Behaviour.register({
	'#ShowOpenIDdesc': {
		onclick: function() {
			if(Element.hasClassName('OpenIDDescription', 'hide')) {
				Element.removeClassName($('OpenIDDescription'), 'hide');
				Element.addClassName($('OpenIDDescription'), 'show');
			}

			return false;
		}
	},
	'#HideOpenIDdesc': {
		onclick: function() {
			if(Element.hasClassName('OpenIDDescription', 'show')) {
				Element.removeClassName($('OpenIDDescription'), 'show');
				Element.addClassName($('OpenIDDescription'), 'hide');
			}

			return false;
		}
	}
});