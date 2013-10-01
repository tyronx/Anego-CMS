$(document).ready(function() {
	$('#socialicons a.sb').hover(
		function() {
			$(this).animate({
				top: '4px'
			}, 150);
		}, 
		function() {
			$(this).animate({
				top: '19px'
			}, 150);
		}
	);
	
	setTimeout(makeWave, 30000);
	function makeWave() {
		$('#socialicons a.sb:nth-child(1)')
			.trigger('mouseover')
			.trigger('mouseout');
		
		setTimeout(function() {
			$('#socialicons a.sb:nth-child(2)')
				.trigger('mouseover')
				.trigger('mouseout');

		}, 160);
		setTimeout(function() {
			$('#socialicons a.sb:nth-child(3)')
				.trigger('mouseover')
				.trigger('mouseout');
		}, 320);
		
		setTimeout(makeWave, 30000);
	}
});