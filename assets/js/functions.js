var timelineCarousels 	=	new Array();
var carouselParams 		=	{
								dots:true,
								nav:true,
								loop:true,
								items: 1,
								autoplayTimeout:5000,
								autoplayHoverPause:false,
                                onChanged: function(e){
                                    var current     =   e.item.index;
                                    var currentitem = jQuery(e.target).find(".owl-item").eq(current);
                                    stopAllVideos();
                                    playBackground(currentitem);
                                }
							};

jQuery(document).ready(function($){
	initTimeline();

	$(".timeline-carousel.loaded").each(function(i,item){
		timelineCarousels[i]	=	$(item).owlCarousel(carouselParams);
	});

	//cropDates();
	$(".timeline-dots li#timeline-0").click();

	$('.timeline-dots li').each(function(){
		var $item = $(this);
		preloadItems($item.attr('id').replace('timeline-',''),$item.attr('term'));
	});


	$('.timeline-carrousel-buttons a').click(function(e){
		e.preventDefault();
		var $elem 	=	$(this);
		var $parent =	$elem.parent();
		var $class 	=	$elem.attr('class');
		var $key 	=	$(".timeline-dots li.active").attr('id').replace('timeline-','');

		if( $class == 'play' )
		{
			$parent.addClass('playing').removeClass('paused');
			timelineCarousels[$key].trigger('play.owl.autoplay',[5000]);
		}

		if( $class == 'pause' )
		{
			$parent.removeClass('playing').addClass('paused');
			timelineCarousels[$key].trigger('stop.owl.autoplay');
		}

		return false;
	});

	$('.timeline__buttons .timeline_open_popup').click(function(e){
		e.preventDefault();
		$('.popup__timeline').fadeIn();
		return false;
	});

	$('.popup__timeline .closeBtn').click(function(e){
		e.preventDefault();
		$(this).parent().fadeOut();
		return false;
	});

	$(window).on('resize', function(){
		//cropDates();
		moveTimelineMarker();
	});
});

/*function cropDates()
{
	var wi = jQuery(window).width();
	if (wi < 800) {
		jQuery(".timeline-dots li").each(function(){
			var title = jQuery(this).find('button').text();
			var shortText = jQuery.trim(title).substring(0, 3);
			jQuery(this).find('button').text(shortText);
		})
	}
}*/

function initTimeline()
{
	jQuery(".timeline-dots li").unbind('click');
	jQuery(".timeline-dots li").click(function(e){
		var $item 		=	jQuery(this);
		var element 	=	$item.attr('id');
		var $key 		=	element.replace('timeline-','');

		timelineCarousels[$key].trigger("to.owl.carousel", 0);
		jQuery('.timeline-carrousel-buttons').removeClass('playing').addClass('paused');
		jQuery('.timeline-carousel:not(.'+element+')').hide();
		jQuery('.'+element).show();

		jQuery(".timeline-dots li").removeClass('second-level').removeClass('third-level').removeClass('second');
		$item.siblings().removeClass('active').removeClass('second');
		$item.addClass('active').next().addClass('second-level').removeClass('second').next().addClass('second-level second');
		$item.next().next().next().addClass('third-level').removeClass('second').next().addClass('third-level second');
		$item.prev().addClass('second-level').removeClass('second').prev().addClass('second-level second');
		$item.prev().prev().prev().addClass('third-level').removeClass('second').prev().addClass('third-level second');

		moveTimelineMarker();
	});
}

function moveTimelineMarker()
{
	var $item 		=	jQuery(".timeline-dots li.active");
	var item_postion=	$item.offset().top;
	var lineitem 	=	jQuery('.timeline__line').offset().top;
	var $diff 		=	(lineitem-item_postion-15);

	jQuery( ".timeline-dots" ).animate({ "margin-top": "+="+$diff+"px" }, 300 );
}

function preloadItems($key, $term)
{
	if( !jQuery('.timeline-'+$key).hasClass('loaded') )
	{
		jQuery.ajax({
			type:       'POST',
			url:        TIMELINE.ajax_url,
			dataType:   'JSON',
			data:       { action: 'load_timeline_posts', term_id: $term, key: $key },
			success:    function(data){
							if( data.status == 'ok' )
							{
								jQuery(".timeline-"+$key).html(data.content).addClass(data.classes);

								timelineCarousels[$key]	=	jQuery(".timeline-"+$key).owlCarousel(carouselParams);

								initTimeline();
							}
						}
		});
	}
}

function stopAllVideos()
{
    jQuery('iframe').each(function(i,item){
        var iframeSrc = item.src;
        item.src = iframeSrc;
    });
        
    jQuery('video').each(function(i,item){
        item.pause();
    });
}

function playBackground(element)
{
    element.find('.background__cont video').each(function(i,item){
        jQuery(item).get(0).play();
    });
}