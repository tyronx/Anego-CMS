alohatext = ContentElement.extend({
	onStartEdit: function(newlyCreated) {
		if (! newlyCreated)
			alert('Aloha settings not implemented yet. To edit the text with aloha, please leave the edit mode');
		return false;
	}
});