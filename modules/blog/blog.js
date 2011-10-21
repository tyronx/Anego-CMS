/* Functions for Edit mode */
blog = ContentElement.extend({
	onStartEdit: function(newlyCreated) {
		if (! newlyCreated)
			alert("Blog settings not implemented yet sorry :(\nTo add/edit blog entries, leave the edit mode");
		return false;
	}
});