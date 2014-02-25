
// Advances the slideshow to show the next image
function nextSlide() {
	// For smooth transition, fade image out
	var $slide = $('#slide');

	$slide.animate({
		opacity: 0,
	}, "slow", changeImage);
}

// Moves the slideshow backwards to display the previous image
function prevSlide() {
	// For smooth transition, fade current image out
	var $slide = $('#slide');
	
	$slide.animate({
		opacity: 0,
	}, "slow", changeImage);
}

// Changes the image in a slideshow to that of the given number
function changeImage() {
	var $slide = $('#slide');

	// Grab the source of the current image
	var currentImage = $slide.attr('src');

	// Select the next image
	var myArray = /(\d)/g.exec(currentImage);
	var nextImage = (parseInt(myArray[0]) + 2);
	if (nextImage > 3) {
		nextImage -= 3;
	} 


	$slide.attr('src', "/static/images/room" + nextImage + ".jpg");
	$slide.animate({
		opacity: 1
	});
}