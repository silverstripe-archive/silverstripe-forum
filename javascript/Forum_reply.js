Behaviour.register({
	'#BBCodeHint': {
		onclick: function() {
			if(Element.hasClassName('BBTagsHolder', 'hide')) {
				Element.removeClassName($('BBTagsHolder'), 'hide');
				Element.addClassName($('BBTagsHolder'), 'show');
				this.innerHTML = 'Hide Formatting Help';
			} else if(Element.hasClassName('BBTagsHolder', 'show')) {
				Element.removeClassName($('BBTagsHolder'), 'show');
				Element.addClassName($('BBTagsHolder'), 'hide');
				this.innerHTML = 'View Formatting Help';
			}
			
			return false;
		}
	}
});