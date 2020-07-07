$(document).ready(function() {
	
	$('.slideshow').cycle({
		fx: 'fade' // choose your transition type, ex: fade, scrollUp, shuffle, etc...
	});
	
	//initialize overlay
 	$('#openclose').jqm({
    trigger: 'a.openchart',
	});
	
	$('#openclose').jqm({
	trigger: 'a.zoomchart',
	});
		
	//setup nav
	var killnav = false;
	var onePos = $("#header").position();
	var twoPos = $("#section2").position();
	var threePos = $("#section3").position();
	var fourPos = $("#section4").position();
	var fivePos = $("#section5").position();
	
	//nav function
	//subtract 172 because that is the height of fixed postion header
	 $('ul.nav a').bind('click',function(event){
		 killnav = true;
		var $anchor = $(this);
		$('ul.nav a').removeClass("active");
		$($anchor).addClass("active");
		$('html, body').stop().animate({
			scrollTop: $($anchor.attr('href')).offset().top-172}, 800, function(){ killnav = false;  });		
			event.preventDefault();
	});
	
		//scroll function		
function scrollcheck() {
		//see if scroll is complete
	var inproc = false;
	
	$(window).scroll(function() {
	
	//kill function if clicked
	//subtract 172 because that is the height of fixed postion header
	if (killnav == false) {
	  //set nav
	  if ($(this).scrollTop() > 0 && $(this).scrollTop() < (twoPos.top-172)) { 
		$('ul.nav a').removeClass("active");
		$('ul.nav li:nth-child(1) a').addClass("active");
	  }
	  //set nav
	  if ($(this).scrollTop() > (twoPos.top-172) && $(this).scrollTop() < (threePos.top-172)) { 
	  $('ul.nav a').removeClass("active");
	  $('ul.nav li:nth-child(2) a').addClass("active");
	  inproc = true;			
	  }
	  //set nav
	  if ($(this).scrollTop() > (threePos.top-172) && $(this).scrollTop() < (fourPos.top-172)) { 
	  $('ul.nav a').removeClass("active");
	  $('ul.nav li:nth-child(3) a').addClass("active");
	  inproc = true;			
	  }
	  //set nav
	  if ($(this).scrollTop() > (fourPos.top-172) && $(this).scrollTop() < (fivePos.top-172)) { 
	  $('ul.nav a').removeClass("active");
	  $('ul.nav li:nth-child(4) a').addClass("active");
	  inproc = true;			
	  }
	  //set nav
	  if ($(this).scrollTop() > (fivePos.top-172)) { 
	  $('ul.nav a').removeClass("active");
	  $('ul.nav li:nth-child(5) a').addClass("active");
	  inproc = true;			
	  }
	} else {
		//do nothing				
	}
	});		
		
};

function sectioncheck() {
	  if ($(this).scrollTop() > 0 && $(this).scrollTop() < (twoPos.top-172)) { 
		$('ul.nav a').removeClass("active");
		$('ul.nav li:nth-child(1) a').addClass("active");
	  }
	  //set nav
	  if ($(this).scrollTop() > (twoPos.top-172) && $(this).scrollTop() < (threePos.top-172)) { 
	  $('ul.nav a').removeClass("active");
	  $('ul.nav li:nth-child(2) a').addClass("active");
	  inproc = true;			
	  }
	  //set nav
	  if ($(this).scrollTop() > (threePos.top-172) && $(this).scrollTop() < (fourPos.top-172)) { 
	  $('ul.nav a').removeClass("active");
	  $('ul.nav li:nth-child(3) a').addClass("active");
	  inproc = true;			
	  }
	  //set nav
	  if ($(this).scrollTop() > (fourPos.top-172) && $(this).scrollTop() < (fivePos.top-172)) { 
	  $('ul.nav a').removeClass("active");
	  $('ul.nav li:nth-child(4) a').addClass("active");
	  inproc = true;			
	  }
	  //set nav
	  if ($(this).scrollTop() > (fivePos.top-172)) { 
	  $('ul.nav a').removeClass("active");
	  $('ul.nav li:nth-child(5) a').addClass("active");
	  inproc = true;			
	  }
	
}

	scrollcheck();
	sectioncheck();
});