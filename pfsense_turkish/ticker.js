var width="310px";

var speed=2;
var pauseit=1;

var divonclick=speed=(document.all)? speed : Math.max(1, speed-1);
var copyspeed=speed;
var pausespeed=(pauseit==0)? copyspeed: 0;
var iedom=document.all||document.getElementById;

if (iedom && content)
	document.write('<span id="marquee-container">'+content+'</span>');

var actualwidth='';
var scroller;

try {
	if (window.addEventListener)
		window.addEventListener("load", populatescroller, false);
	else if (window.attachEvent)
		window.attachEvent("onload", populatescroller);
	else if (document.all || document.getElementById)
		window.onload=populatescroller;
} catch(e){}

function populatescroller(){
	try {
		scroller=document.getElementById? document.getElementById("scroller") : document.all.scroller;
		scroller.style.left=parseInt(width)+8+"px";
		scroller.innerHTML=content;
		document.getElementById("marquee-text");
		actualwidth=document.all? document.getElementById("marquee-text").offsetWidth : document.getElementById("marquee-text").offsetWidth;
		lefttime=setInterval("scrollmarquee()",20);
	} catch(e){}
}

function scrollmarquee(){
	try {
		if (parseInt(scroller.style.left)>(actualwidth*(-1)+8))
			scroller.style.left=parseInt(scroller.style.left)-copyspeed+"px";
		else
			scroller.style.left=parseInt(width)+8+"px";
	} catch(e){}
}

if (iedom){
	document.write('<table id="marquee"><td>');
	document.write('<div id="container" onMouseover="copyspeed=pausespeed" onMouseout="copyspeed=speed">');
	document.write('<div id="scroller"></div>');
	document.write('</div>');
	document.write('</td></table>');
}
