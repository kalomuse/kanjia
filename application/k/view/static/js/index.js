/*  @description: 首页脚本
*   @author: wuxiaoxia  
*   @update: wuxiaoxia (2017-11-23) */

(function($){
$.fn.myScroll = function(options){
//默认配置
var defaults = {
	speed:40,  //滚动速度,值越大速度越慢
	rowHeight:30 //每行的高度
};

var opts = $.extend({}, defaults, options),intId = [];

function marquee(obj, step){

	obj.find("ul").animate({
		marginTop: '-=1'
	},0,function(){
			var s = Math.abs(parseInt($(this).css("margin-top")));
			if(s >= step){
				$(this).find("li").slice(0, 1).appendTo($(this));
				$(this).css("margin-top", 0);
			}
		});
	}
	
	this.each(function(i){
		var sh = opts["rowHeight"],speed = opts["speed"],_this = $(this);
		intId[i] = setInterval(function(){
			if(_this.find("ul").height()<=_this.height()){
				clearInterval(intId[i]);
			}else{
				marquee(_this, sh);
			}
		}, speed);

		_this.hover(function(){
			clearInterval(intId[i]);
		},function(){
			intId[i] = setInterval(function(){
				if(_this.find("ul").height()<=_this.height()){
					clearInterval(intId[i]);
				}else{
					marquee(_this, sh);
				}
			}, speed);
		});
	
	});

}

})(jQuery);

$(function(){
	var newsnum=$('.bargain-success li').length;
	if(newsnum>1){
	    $('.bargain-success').myScroll({
			speed: 80, //数值越大，速度越慢
			rowHeight: 24 //li的高度
		});
	}
	$('.share-btn').on('click',function(){
		$('.share-pop').fadeIn(300)
	});
	$('.share-close-btn').on('click',function(){
		$('.share-pop').fadeOut(300)
	});
	$('.bargain-pop-btn').on('click',function(){
		$('.participate-pop').fadeIn(300)
	});
	$('.participate-close-btn').on('click',function(){
		$('.participate-pop').fadeOut(300)
	});
})