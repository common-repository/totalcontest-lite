<?php

namespace TotalContest\Shortcode;

use TotalContestVendors\TotalCore\Helpers\DateTime;

/**
 * Contest shortcode class
 *
 * @package TotalContest\Shortcode
 * @since   1.0.0
 */
class Contest extends Base {
	static $countdownjs = false;

	/**
	 * Handle shortcode.
	 *
	 * @return mixed
	 * @since 1.0.0
	 */
	public function handle() {
		$contest    = $this->getContest();
		$submission = $this->getSubmission();
		$screen     = $this->getAttribute( 'screen' );
		$menu       = wp_validate_boolean( $this->getAttribute( 'menu', ! (bool) $screen ) );
		$pageId     = $this->getAttribute( 'page-id' );
		$category   = $this->getAttribute( 'category' );

		if ( $contest ):
			if ( $contest->getAction() === 'submission' ):
				$submission = $this->getSubmission();
			endif;

			if ( $contest->getScreen() !== 'contest.thankyou' && $screen ):
				$contest->setScreen( $screen );
			endif;

			if ( $contest->getScreen() === 'contest.participate' ) {
				$submission = null;
			}

			if ( $contest->getScreen() === 'contest.countdown' ) {
				$type   = $this->getAttribute( 'type', 'contest' );
				$format = $this->getAttribute( 'format', '%a days and %h hours' );
				$until  = $this->getAttribute( 'until', 'start' );
				$date   = ( new DateTime() )->getFormattedDate( DATE_W3C );

				if ( $until === 'start' && $contest->getStartDate( $type ) ):
					$interval = $contest->getTimeLeftToStart( $type );
					$date = $contest->getStartDate( $type )->getFormattedDate( DATE_W3C );
				elseif ( $until === 'end' && $contest->getTimeLeftToEnd( $type ) ):
					$interval = $contest->getTimeLeftToEnd( $type );
					$date = $contest->getEndDate( $type )->getFormattedDate( DATE_W3C );
				endif;

				if ( isset( $interval ) && $interval instanceof \DateInterval ):
					return $this->countdown( $date );
				endif;
			}

			if ( $pageId ):
				$contest->setCustomPageId( $pageId );
			endif;

			if ( $category ):
				$contest->setFilter( 'category', $category );
			endif;

			$contest->setMenuVisibility( $menu );

		endif;

		return (string) ( $submission ?: $contest );
	}

	protected function countdown( $date ) {
		$output = '';
		if ( ! static::$countdownjs ) {
			static::$countdownjs = true;

			add_action( 'wp_footer', function () {
				echo <<<JS
<script>
/*
 countdown.js v2.6.1 http://countdownjs.org
 Copyright (c)2006-2014 Stephen M. McKamey.
 Licensed under The MIT License.
*/
var countdown=function(){function z(a,b){var c=a.getTime();a.setMonth(a.getMonth()+b);return Math.round((a.getTime()-c)/864E5)}function v(a){var b=a.getTime(),c=new Date(b);c.setMonth(a.getMonth()+1);return Math.round((c.getTime()-b)/864E5)}function w(a,b){b=b instanceof Date||null!==b&&isFinite(b)?new Date(+b):new Date;if(!a)return b;var c=+a.value||0;if(c)return b.setTime(b.getTime()+c),b;(c=+a.milliseconds||0)&&b.setMilliseconds(b.getMilliseconds()+c);(c=+a.seconds||0)&&b.setSeconds(b.getSeconds()+
c);(c=+a.minutes||0)&&b.setMinutes(b.getMinutes()+c);(c=+a.hours||0)&&b.setHours(b.getHours()+c);(c=+a.weeks||0)&&(c*=7);(c+=+a.days||0)&&b.setDate(b.getDate()+c);(c=+a.months||0)&&b.setMonth(b.getMonth()+c);(c=+a.millennia||0)&&(c*=10);(c+=+a.centuries||0)&&(c*=10);(c+=+a.decades||0)&&(c*=10);(c+=+a.years||0)&&b.setFullYear(b.getFullYear()+c);return b}function C(a,b){return x(a)+(1===a?p[b]:q[b])}function n(){}function k(a,b,c,e,l,d){0<=a[c]&&(b+=a[c],delete a[c]);b/=l;if(1>=b+1)return 0;if(0<=a[e]){a[e]=
+(a[e]+b).toFixed(d);switch(e){case "seconds":if(60!==a.seconds||isNaN(a.minutes))break;a.minutes++;a.seconds=0;case "minutes":if(60!==a.minutes||isNaN(a.hours))break;a.hours++;a.minutes=0;case "hours":if(24!==a.hours||isNaN(a.days))break;a.days++;a.hours=0;case "days":if(7!==a.days||isNaN(a.weeks))break;a.weeks++;a.days=0;case "weeks":if(a.weeks!==v(a.refMonth)/7||isNaN(a.months))break;a.months++;a.weeks=0;case "months":if(12!==a.months||isNaN(a.years))break;a.years++;a.months=0;case "years":if(10!==
a.years||isNaN(a.decades))break;a.decades++;a.years=0;case "decades":if(10!==a.decades||isNaN(a.centuries))break;a.centuries++;a.decades=0;case "centuries":if(10!==a.centuries||isNaN(a.millennia))break;a.millennia++;a.centuries=0}return 0}return b}function A(a,b,c,e,l,d){var f=new Date;a.start=b=b||f;a.end=c=c||f;a.units=e;a.value=c.getTime()-b.getTime();0>a.value&&(f=c,c=b,b=f);a.refMonth=new Date(b.getFullYear(),b.getMonth(),15,12,0,0);try{a.millennia=0;a.centuries=0;a.decades=0;a.years=c.getFullYear()-
b.getFullYear();a.months=c.getMonth()-b.getMonth();a.weeks=0;a.days=c.getDate()-b.getDate();a.hours=c.getHours()-b.getHours();a.minutes=c.getMinutes()-b.getMinutes();a.seconds=c.getSeconds()-b.getSeconds();a.milliseconds=c.getMilliseconds()-b.getMilliseconds();var g;0>a.milliseconds?(g=s(-a.milliseconds/1E3),a.seconds-=g,a.milliseconds+=1E3*g):1E3<=a.milliseconds&&(a.seconds+=m(a.milliseconds/1E3),a.milliseconds%=1E3);0>a.seconds?(g=s(-a.seconds/60),a.minutes-=g,a.seconds+=60*g):60<=a.seconds&&(a.minutes+=
m(a.seconds/60),a.seconds%=60);0>a.minutes?(g=s(-a.minutes/60),a.hours-=g,a.minutes+=60*g):60<=a.minutes&&(a.hours+=m(a.minutes/60),a.minutes%=60);0>a.hours?(g=s(-a.hours/24),a.days-=g,a.hours+=24*g):24<=a.hours&&(a.days+=m(a.hours/24),a.hours%=24);for(;0>a.days;)a.months--,a.days+=z(a.refMonth,1);7<=a.days&&(a.weeks+=m(a.days/7),a.days%=7);0>a.months?(g=s(-a.months/12),a.years-=g,a.months+=12*g):12<=a.months&&(a.years+=m(a.months/12),a.months%=12);10<=a.years&&(a.decades+=m(a.years/10),a.years%=
10,10<=a.decades&&(a.centuries+=m(a.decades/10),a.decades%=10,10<=a.centuries&&(a.millennia+=m(a.centuries/10),a.centuries%=10)));b=0;!(e&1024)||b>=l?(a.centuries+=10*a.millennia,delete a.millennia):a.millennia&&b++;!(e&512)||b>=l?(a.decades+=10*a.centuries,delete a.centuries):a.centuries&&b++;!(e&256)||b>=l?(a.years+=10*a.decades,delete a.decades):a.decades&&b++;!(e&128)||b>=l?(a.months+=12*a.years,delete a.years):a.years&&b++;!(e&64)||b>=l?(a.months&&(a.days+=z(a.refMonth,a.months)),delete a.months,
7<=a.days&&(a.weeks+=m(a.days/7),a.days%=7)):a.months&&b++;!(e&32)||b>=l?(a.days+=7*a.weeks,delete a.weeks):a.weeks&&b++;!(e&16)||b>=l?(a.hours+=24*a.days,delete a.days):a.days&&b++;!(e&8)||b>=l?(a.minutes+=60*a.hours,delete a.hours):a.hours&&b++;!(e&4)||b>=l?(a.seconds+=60*a.minutes,delete a.minutes):a.minutes&&b++;!(e&2)||b>=l?(a.milliseconds+=1E3*a.seconds,delete a.seconds):a.seconds&&b++;if(!(e&1)||b>=l){var h=k(a,0,"milliseconds","seconds",1E3,d);if(h&&(h=k(a,h,"seconds","minutes",60,d))&&(h=
k(a,h,"minutes","hours",60,d))&&(h=k(a,h,"hours","days",24,d))&&(h=k(a,h,"days","weeks",7,d))&&(h=k(a,h,"weeks","months",v(a.refMonth)/7,d))){e=h;var n,p=a.refMonth,q=p.getTime(),r=new Date(q);r.setFullYear(p.getFullYear()+1);n=Math.round((r.getTime()-q)/864E5);if(h=k(a,e,"months","years",n/v(a.refMonth),d))if(h=k(a,h,"years","decades",10,d))if(h=k(a,h,"decades","centuries",10,d))if(h=k(a,h,"centuries","millennia",10,d))throw Error("Fractional unit overflow");}}}finally{delete a.refMonth}return a}
function d(a,b,c,e,d){var f;c=+c||222;e=0<e?e:NaN;d=0<d?20>d?Math.round(d):20:0;var k=null;"function"===typeof a?(f=a,a=null):a instanceof Date||(null!==a&&isFinite(a)?a=new Date(+a):("object"===typeof k&&(k=a),a=null));var g=null;"function"===typeof b?(f=b,b=null):b instanceof Date||(null!==b&&isFinite(b)?b=new Date(+b):("object"===typeof b&&(g=b),b=null));k&&(a=w(k,b));g&&(b=w(g,a));if(!a&&!b)return new n;if(!f)return A(new n,a,b,c,e,d);var k=c&1?1E3/30:c&2?1E3:c&4?6E4:c&8?36E5:c&16?864E5:6048E5,
h,g=function(){f(A(new n,a,b,c,e,d),h)};g();return h=setInterval(g,k)}var s=Math.ceil,m=Math.floor,p,q,r,t,u,f,x,y;n.prototype.toString=function(a){var b=y(this),c=b.length;if(!c)return a?""+a:u;if(1===c)return b[0];a=r+b.pop();return b.join(t)+a};n.prototype.toHTML=function(a,b){a=a||"span";var c=y(this),e=c.length;if(!e)return(b=b||u)?"\x3c"+a+"\x3e"+b+"\x3c/"+a+"\x3e":b;for(var d=0;d<e;d++)c[d]="\x3c"+a+"\x3e"+c[d]+"\x3c/"+a+"\x3e";if(1===e)return c[0];e=r+c.pop();return c.join(t)+e};n.prototype.addTo=
function(a){return w(this,a)};y=function(a){var b=[],c=a.millennia;c&&b.push(f(c,10));(c=a.centuries)&&b.push(f(c,9));(c=a.decades)&&b.push(f(c,8));(c=a.years)&&b.push(f(c,7));(c=a.months)&&b.push(f(c,6));(c=a.weeks)&&b.push(f(c,5));(c=a.days)&&b.push(f(c,4));(c=a.hours)&&b.push(f(c,3));(c=a.minutes)&&b.push(f(c,2));(c=a.seconds)&&b.push(f(c,1));(c=a.milliseconds)&&b.push(f(c,0));return b};d.MILLISECONDS=1;d.SECONDS=2;d.MINUTES=4;d.HOURS=8;d.DAYS=16;d.WEEKS=32;d.MONTHS=64;d.YEARS=128;d.DECADES=256;
d.CENTURIES=512;d.MILLENNIA=1024;d.DEFAULTS=222;d.ALL=2047;var D=d.setFormat=function(a){if(a){if("singular"in a||"plural"in a){var b=a.singular||[];b.split&&(b=b.split("|"));var c=a.plural||[];c.split&&(c=c.split("|"));for(var d=0;10>=d;d++)p[d]=b[d]||p[d],q[d]=c[d]||q[d]}"string"===typeof a.last&&(r=a.last);"string"===typeof a.delim&&(t=a.delim);"string"===typeof a.empty&&(u=a.empty);"function"===typeof a.formatNumber&&(x=a.formatNumber);"function"===typeof a.formatter&&(f=a.formatter)}},B=d.resetFormat=
function(){p=" millisecond; second; minute; hour; day; week; month; year; decade; century; millennium".split(";");q=" milliseconds; seconds; minutes; hours; days; weeks; months; years; decades; centuries; millennia".split(";");r=" and ";t=", ";u="";x=function(a){return a};f=C};d.setLabels=function(a,b,c,d,f,k,m){D({singular:a,plural:b,last:c,delim:d,empty:f,formatNumber:k,formatter:m})};d.resetLabels=B;B();"undefined"!==typeof module&&module.exports?module.exports=d:"undefined"!==typeof window&&("function"===
typeof window.define&&"undefined"!==typeof window.define.amd)&&window.define("countdown",[],function(){return d});return d}();
</script>
JS;
			} );
		}

		$id     = md5( microtime() );
		$output .= <<<JS
<div class="contest-countdown" id="timer-{$id}">
	<div style="display: flex; gap: 15px;">
		<div style="display: flex; flex-direction: column;flex: 1;text-align: center;border: 2px solid black; padding: 15px;">
			<div class="contest-countdown-days"></div>
        	Days
        </div>
		<div style="display: flex; flex-direction: column;flex: 1;text-align: center;border: 2px solid black; padding: 15px;">
			<div class="contest-countdown-hours"></div>
        	Hours
        </div>
		<div style="display: flex; flex-direction: column;flex: 1;text-align: center;border: 2px solid black; padding: 15px;">
			<div class="contest-countdown-minutes"></div>
        	Minutes
        </div>
		<div style="display: flex; flex-direction: column;flex: 1;text-align: center;border: 2px solid black; padding: 15px;">
			<div class="contest-countdown-seconds"></div>
        	Seconds
        </div>
	</div>
</div>
<script type="text/javascript">
window.addEventListener('DOMContentLoaded', function(){
	countdown(
		new Date('{$date}'),
	    function(ts) {
	      var el = document.querySelector('#timer-{$id}');
	      for(p in ts){
	          var part = el.querySelector('.contest-countdown-' + p);
	          if(part){
	              part.innerText = ts[p];              
	          }
	      }
	    },
	    countdown.DAYS|countdown.HOURS|countdown.MINUTES|countdown.SECONDS
	);
});
</script>
JS;

		return $output;
	}
}
