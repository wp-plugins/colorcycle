/*!
 * ColorCycle WordPress Plugin v1.5
 */
var $jwcc = jQuery.noConflict();

$jwcc(document).ready(function() {
	$jwcc('body').addClass( 'cc-theme-' + color_cycle.theme );
	$jwcc('.colorbox').colorbox( color_cycle.cb_opts );
    var $jwccss = $jwcc('.jw-colorcycle').cycle({
		fx: color_cycle.fx,
		speed: parseInt(color_cycle.speed),
		delay: parseInt(color_cycle.delay),
		pause: parseInt(color_cycle.pause),
		timeout: parseInt(color_cycle.timeout),
		height: color_cycle.height,
		width: color_cycle.width,
		pager: '.jw-cc-pages',
		prev: '.jw-cc-prev',
		next: '.jw-cc-next',
		fit: 1,
		before: verticalSlide
	});
	if( color_cycle.thumbs ) {
		$jwccss.children().each(function(i) { 
	        $jwcc('#goto'+(i+1)).click(function() { 
	                $jwccss.cycle(i); 
	                return false; 
	        });
	    });
	}
});

function verticalSlide(curr,next,opts) {
	if( color_cycle.forcevertical ) {
	    var $slide = $jwcc(next); 
	    var sh = $slide.outerHeight(); 
	    var h = $slide.parent().outerHeight(); 
	    $slide.css({ 
	        marginTop: ( h - sh ) / 2, 
	    }); 
	}
}; 